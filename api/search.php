<?php
// api/search.php
// API para sistema de busca global

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once '../config/database.php';
require_once '../utils/helpers.php';

try {
    $db = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleSearch($db);
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Processar busca global
 */
function handleSearch($db) {
    try {
        // Parâmetros da busca
        $query = sanitize_input($_GET['q'] ?? '');
        $type = sanitize_input($_GET['type'] ?? 'all'); // all, news, forum, hub
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, min(20, intval($_GET['per_page'] ?? 10)));
        $sort = sanitize_input($_GET['sort'] ?? 'relevance'); // relevance, date, views
        
        if (empty($query)) {
            throw new Exception('Termo de busca é obrigatório');
        }
        
        $offset = ($page - 1) * $per_page;
        $results = [];
        $total = 0;
        
        switch ($type) {
            case 'news':
                $results = searchNews($db, $query, $sort, $per_page, $offset);
                $total = countNewsResults($db, $query);
                break;
                
            case 'forum':
                $results = searchForum($db, $query, $sort, $per_page, $offset);
                $total = countForumResults($db, $query);
                break;
                
            case 'hub':
                $results = searchHub($db, $query, $sort, $per_page, $offset);
                $total = countHubResults($db, $query);
                break;
                
            case 'all':
            default:
                $results = searchAll($db, $query, $sort, $per_page, $offset);
                $total = countAllResults($db, $query);
                break;
        }
        
        // Metadados da paginação
        $total_pages = ceil($total / $per_page);
        $has_next = $page < $total_pages;
        $has_prev = $page > 1;
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'meta' => [
                'query' => $query,
                'type' => $type,
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => intval($total),
                'total_pages' => $total_pages,
                'has_next' => $has_next,
                'has_prev' => $has_prev,
                'next_page' => $has_next ? $page + 1 : null,
                'prev_page' => $has_prev ? $page - 1 : null
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Erro na busca: " . $e->getMessage());
    }
}

/**
 * Buscar nas notícias
 */
function searchNews($db, $query, $sort, $limit, $offset) {
    try {
        $order_clause = getSortClause($sort, 'n');
        
        $sql = "SELECT n.id, n.slug, n.title, n.excerpt, n.featured_image, n.category, 
                       n.views, n.likes, n.created_at, n.published_at,
                       u.username, u.display_name,
                       'news' as result_type,
                       MATCH(n.title, n.content) AGAINST(:query IN NATURAL LANGUAGE MODE) as relevance
                FROM news n
                JOIN users u ON n.author_id = u.id
                WHERE n.status = 'published' 
                AND (n.title LIKE :search OR n.content LIKE :search OR MATCH(n.title, n.content) AGAINST(:query IN NATURAL LANGUAGE MODE))
                ORDER BY {$order_clause}
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $search_term = "%{$query}%";
        $stmt->bindValue(':query', $query);
        $stmt->bindValue(':search', $search_term);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Formatar resultados
        foreach ($results as &$result) {
            $result['url'] = site_url("noticia/{$result['slug']}");
            $result['time_ago'] = time_ago($result['published_at'] ?: $result['created_at']);
            $result['highlight'] = highlightSearchTerm($result['title'], $query);
            $result['excerpt_highlight'] = highlightSearchTerm($result['excerpt'], $query);
            $result['author_name'] = $result['display_name'] ?: $result['username'];
        }
        
        return $results;
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar notícias: " . $e->getMessage());
    }
}

/**
 * Buscar no fórum
 */
function searchForum($db, $query, $sort, $limit, $offset) {
    try {
        $order_clause = getSortClause($sort, 'ft');
        
        $sql = "SELECT ft.id, ft.title, ft.views, ft.replies_count, ft.created_at,
                       fc.name as category_name, fc.icon as category_icon,
                       u.username, u.display_name,
                       'forum' as result_type,
                       SUBSTRING(ft.content, 1, 200) as excerpt
                FROM forum_topics ft
                JOIN forum_categories fc ON ft.category_id = fc.id
                JOIN users u ON ft.author_id = u.id
                WHERE ft.status IN ('open', 'pinned', 'closed')
                AND (ft.title LIKE :search OR ft.content LIKE :search)
                ORDER BY {$order_clause}
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $search_term = "%{$query}%";
        $stmt->bindValue(':search', $search_term);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        
        // Formatar resultados
        foreach ($results as &$result) {
            $result['url'] = site_url("forum/topico/{$result['id']}");
            $result['time_ago'] = time_ago($result['created_at']);
            $result['highlight'] = highlightSearchTerm($result['title'], $query);
            $result['excerpt_highlight'] = highlightSearchTerm($result['excerpt'], $query);
            $result['author_name'] = $result['display_name'] ?: $result['username'];
        }
        
        return $results;
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar no fórum: " . $e->getMessage());
    }
}

/**
 * Buscar no HUB (personagens, locais, veículos, missões)
 */
function searchHub($db, $query, $sort, $limit, $offset) {
    try {
        $search_term = "%{$query}%";
        $results = [];
        
        // Buscar personagens
        $char_sql = "SELECT id, slug, name as title, description as excerpt, image_main as featured_image,
                            'character' as result_type, views, created_at
                     FROM characters 
                     WHERE name LIKE :search OR description LIKE :search
                     ORDER BY views DESC";
        
        $char_stmt = $db->prepare($char_sql);
        $char_stmt->bindValue(':search', $search_term);
        $char_stmt->execute();
        $characters = $char_stmt->fetchAll();
        
        // Buscar localizações
        $loc_sql = "SELECT id, slug, name as title, description as excerpt, image_main as featured_image,
                           'location' as result_type, views, created_at
                    FROM locations 
                    WHERE name LIKE :search OR description LIKE :search
                    ORDER BY views DESC";
        
        $loc_stmt = $db->prepare($loc_sql);
        $loc_stmt->bindValue(':search', $search_term);
        $loc_stmt->execute();
        $locations = $loc_stmt->fetchAll();
        
        // Buscar veículos
        $veh_sql = "SELECT id, slug, name as title, description as excerpt, image_main as featured_image,
                           'vehicle' as result_type, views, created_at
                    FROM vehicles 
                    WHERE name LIKE :search OR description LIKE :search
                    ORDER BY views DESC";
        
        $veh_stmt = $db->prepare($veh_sql);
        $veh_stmt->bindValue(':search', $search_term);
        $veh_stmt->execute();
        $vehicles = $veh_stmt->fetchAll();
        
        // Buscar missões
        $miss_sql = "SELECT id, slug, title, description as excerpt, image_main as featured_image,
                            'mission' as result_type, views, created_at
                     FROM missions 
                     WHERE title LIKE :search OR description LIKE :search
                     ORDER BY views DESC";
        
        $miss_stmt = $db->prepare($miss_sql);
        $miss_stmt->bindValue(':search', $search_term);
        $miss_stmt->execute();
        $missions = $miss_stmt->fetchAll();
        
        // Combinar todos os resultados
        $all_results = array_merge($characters, $locations, $vehicles, $missions);
        
        // Ordenar por relevância/views
        usort($all_results, function($a, $b) use ($sort) {
            if ($sort === 'date') {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            }
            return $b['views'] - $a['views'];
        });
        
        // Aplicar paginação
        $results = array_slice($all_results, $offset, $limit);
        
        // Formatar resultados
        foreach ($results as &$result) {
            $base_url = '';
            switch ($result['result_type']) {
                case 'character':
                    $base_url = 'personagem';
                    break;
                case 'location':
                    $base_url = 'local';
                    break;
                case 'vehicle':
                    $base_url = 'veiculo';
                    break;
                case 'mission':
                    $base_url = 'missao';
                    break;
            }
            
            $result['url'] = site_url("{$base_url}/{$result['slug']}");
            $result['time_ago'] = time_ago($result['created_at']);
            $result['highlight'] = highlightSearchTerm($result['title'], $query);
            $result['excerpt_highlight'] = highlightSearchTerm($result['excerpt'], $query);
            $result['formatted_views'] = number_format($result['views']);
        }
        
        return $results;
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar no HUB: " . $e->getMessage());
    }
}

/**
 * Busca global (todos os tipos)
 */
function searchAll($db, $query, $sort, $limit, $offset) {
    try {
        $results = [];
        
        // Buscar em todas as categorias com limite menor para cada uma
        $category_limit = ceil($limit / 4);
        
        $news_results = searchNews($db, $query, $sort, $category_limit, 0);
        $forum_results = searchForum($db, $query, $sort, $category_limit, 0);
        $hub_results = searchHub($db, $query, $sort, $category_limit, 0);
        
        // Combinar resultados
        $all_results = array_merge($news_results, $forum_results, $hub_results);
        
        // Ordenar por relevância global
        usort($all_results, function($a, $b) use ($sort) {
            if ($sort === 'date') {
                $date_a = $a['published_at'] ?? $a['created_at'];
                $date_b = $b['published_at'] ?? $b['created_at'];
                return strtotime($date_b) - strtotime($date_a);
            } elseif ($sort === 'views') {
                return ($b['views'] ?? 0) - ($a['views'] ?? 0);
            } else {
                // Relevância por tipo (news > hub > forum)
                $type_priority = ['news' => 3, 'character' => 2, 'location' => 2, 'vehicle' => 2, 'mission' => 2, 'forum' => 1];
                $priority_a = $type_priority[$a['result_type']] ?? 0;
                $priority_b = $type_priority[$b['result_type']] ?? 0;
                
                if ($priority_a !== $priority_b) {
                    return $priority_b - $priority_a;
                }
                
                return ($b['views'] ?? 0) - ($a['views'] ?? 0);
            }
        });
        
        // Aplicar paginação
        $results = array_slice($all_results, $offset, $limit);
        
        return $results;
    } catch (Exception $e) {
        throw new Exception("Erro na busca global: " . $e->getMessage());
    }
}

/**
 * Contar resultados de notícias
 */
function countNewsResults($db, $query) {
    try {
        $sql = "SELECT COUNT(*) as total FROM news n
                WHERE n.status = 'published' 
                AND (n.title LIKE :search OR n.content LIKE :search)";
        
        $stmt = $db->prepare($sql);
        $search_term = "%{$query}%";
        $stmt->bindValue(':search', $search_term);
        $stmt->execute();
        
        return $stmt->fetch()['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Contar resultados do fórum
 */
function countForumResults($db, $query) {
    try {
        $sql = "SELECT COUNT(*) as total FROM forum_topics ft
                WHERE ft.status IN ('open', 'pinned', 'closed')
                AND (ft.title LIKE :search OR ft.content LIKE :search)";
        
        $stmt = $db->prepare($sql);
        $search_term = "%{$query}%";
        $stmt->bindValue(':search', $search_term);
        $stmt->execute();
        
        return $stmt->fetch()['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Contar resultados do HUB
 */
function countHubResults($db, $query) {
    try {
        $search_term = "%{$query}%";
        $total = 0;
        
        // Contar em cada tabela do HUB
        $tables = [
            'characters' => 'name',
            'locations' => 'name', 
            'vehicles' => 'name',
            'missions' => 'title'
        ];
        
        foreach ($tables as $table => $field) {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$field} LIKE :search OR description LIKE :search";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':search', $search_term);
            $stmt->execute();
            $total += $stmt->fetch()['count'];
        }
        
        return $total;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Contar todos os resultados
 */
function countAllResults($db, $query) {
    try {
        $news_count = countNewsResults($db, $query);
        $forum_count = countForumResults($db, $query);
        $hub_count = countHubResults($db, $query);
        
        return $news_count + $forum_count + $hub_count;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Obter cláusula de ordenação
 */
function getSortClause($sort, $table_alias) {
    switch ($sort) {
        case 'date':
            return "{$table_alias}.created_at DESC";
        case 'views':
            return "{$table_alias}.views DESC";
        case 'relevance':
        default:
            if ($table_alias === 'n') {
                return "relevance DESC, {$table_alias}.views DESC";
            }
            return "{$table_alias}.views DESC, {$table_alias}.created_at DESC";
    }
}

/**
 * Destacar termo de busca no texto
 */
function highlightSearchTerm($text, $term) {
    if (empty($text) || empty($term)) {
        return $text;
    }
    
    $highlighted = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $text);
    return $highlighted;
}

/**
 * Obter sugestões de busca
 */
function getSearchSuggestions($db, $query, $limit = 5) {
    try {
        $suggestions = [];
        $search_term = "%{$query}%";
        
        // Sugestões baseadas em títulos de notícias populares
        $news_sql = "SELECT DISTINCT title FROM news 
                     WHERE status = 'published' AND title LIKE :search 
                     ORDER BY views DESC 
                     LIMIT :limit";
        
        $news_stmt = $db->prepare($news_sql);
        $news_stmt->bindValue(':search', $search_term);
        $news_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $news_stmt->execute();
        $news_suggestions = $news_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Sugestões baseadas em personagens
        $char_sql = "SELECT DISTINCT name FROM characters 
                     WHERE name LIKE :search 
                     ORDER BY views DESC 
                     LIMIT :limit";
        
        $char_stmt = $db->prepare($char_sql);
        $char_stmt->bindValue(':search', $search_term);
        $char_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $char_stmt->execute();
        $char_suggestions = $char_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Combinar e limitar sugestões
        $all_suggestions = array_merge($news_suggestions, $char_suggestions);
        $suggestions = array_slice(array_unique($all_suggestions), 0, $limit);
        
        return $suggestions;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obter termos de busca populares
 */
function getPopularSearchTerms($db, $limit = 10) {
    try {
        // Como não temos tabela de histórico de buscas, vamos simular com dados populares
        $popular_terms = [
            'GTA 6 trailer',
            'Jason Lucia',
            'Vice City',
            'Leonida mapa',
            'Data lançamento',
            'Teorias GTA VI',
            'Easter eggs',
            'Personagens',
            'Veículos',
            'Missões'
        ];
        
        return array_slice($popular_terms, 0, $limit);
    } catch (Exception $e) {
        return [];
    }
}

// Endpoints especiais
if (isset($_GET['action'])) {
    $action = sanitize_input($_GET['action']);
    
    switch ($action) {
        case 'suggestions':
            $query = sanitize_input($_GET['q'] ?? '');
            if ($query) {
                $suggestions = getSearchSuggestions($db, $query);
                echo json_encode([
                    'success' => true,
                    'data' => $suggestions
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Query é obrigatória'
                ]);
            }
            break;
            
        case 'popular':
            $popular = getPopularSearchTerms($db);
            echo json_encode([
                'success' => true,
                'data' => $popular
            ]);
            break;
            
        case 'autocomplete':
            $query = sanitize_input($_GET['q'] ?? '');
            if (strlen($query) >= 2) {
                $suggestions = getSearchSuggestions($db, $query, 8);
                echo json_encode([
                    'success' => true,
                    'data' => $suggestions
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
            break;
            
        default:
            throw new Exception("Ação não reconhecida");
    }
    exit;
}
?>