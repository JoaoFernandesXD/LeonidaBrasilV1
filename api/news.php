<?php
// api/news_complete.php - Versão Completa da API de Notícias
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers obrigatórios
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Funções de resposta
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

function returnSuccess($data, $meta = null) {
    $response = ['success' => true, 'data' => $data];
    if ($meta) $response['meta'] = $meta;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Verificar dependências
    if (!file_exists('../config/database.php')) returnError('database.php não encontrado');
    if (!file_exists('../utils/helpers.php')) returnError('helpers.php não encontrado');
    
    require_once '../config/database.php';
    require_once '../utils/helpers.php';
    
    $db = Database::getInstance()->getConnection();
    if (!$db) returnError('Falha na conexão com banco');
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Roteamento baseado em ação
    if (isset($_GET['action'])) {
        handleSpecialActions($db);
    }
    
    // Roteamento baseado em método HTTP
    switch ($method) {
        case 'GET':
            handleGetNews($db);
            break;
        case 'POST':
            handleCreateNews($db);
            break;
        case 'PUT':
            handleUpdateNews($db);
            break;
        case 'DELETE':
            handleDeleteNews($db);
            break;
        default:
            returnError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("API News Error: " . $e->getMessage());
    returnError('Erro interno: ' . $e->getMessage());
}

/**
 * Buscar notícias com paginação e filtros
 */
function handleGetNews($db) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = max(1, min(50, intval($_GET['per_page'] ?? 6)));
    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null;
    
    $offset = ($page - 1) * $per_page;
    
    // Construir query
    $where_conditions = ["n.status = 'published'"];
    $params = [];
    
    if (!empty($category)) {
        $where_conditions[] = "n.category = :category";
        $params['category'] = $category;
    }
    
    if ($featured !== null) {
        $where_conditions[] = "n.featured = :featured";
        $params['featured'] = $featured ? 1 : 0;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(n.title LIKE :search OR n.content LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Query principal com contagem de comentários
    $sql = "SELECT n.id, n.slug, n.title, n.subtitle, n.excerpt, n.featured_image, 
                   n.category, n.views, n.likes, n.featured, n.published_at, n.created_at,
                   u.username, u.display_name,
                   (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id) as comments_count
            FROM news n
            JOIN users u ON n.author_id = u.id
            WHERE {$where_clause}
            ORDER BY n.featured DESC, n.published_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM news n JOIN users u ON n.author_id = u.id WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue(":{$key}", $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Processar dados
    foreach ($news as &$item) {
        $item['time_ago'] = timeAgo($item['published_at'] ?: $item['created_at']);
        $item['formatted_views'] = number_format($item['views']);
        $item['category_formatted'] = formatCategoryName($item['category']);
        $item['author_name'] = $item['display_name'] ?: $item['username'];
        $item['url'] = "/noticia/{$item['slug']}";
        
        if (empty($item['featured_image'])) {
            $item['featured_image'] = 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg';
        }
    }
    
    // Metadados
    $total_pages = ceil($total / $per_page);
    $meta = [
        'current_page' => $page,
        'per_page' => $per_page,
        'total' => intval($total),
        'total_pages' => $total_pages,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1,
        'next_page' => $page < $total_pages ? $page + 1 : null,
        'prev_page' => $page > 1 ? $page - 1 : null
    ];
    
    returnSuccess($news, $meta);
}

/**
 * Ações especiais (like, view, etc.)
 */
function handleSpecialActions($db) {
    $action = trim($_GET['action'] ?? '');
    
    switch ($action) {
        case 'like':
            handleLike($db);
            break;
        case 'view':
            handleView($db);
            break;
        case 'related':
            handleRelated($db);
            break;
        case 'stats':
            handleStats($db);
            break;
        default:
            returnError('Ação não reconhecida');
    }
}

/**
 * Sistema de curtidas
 */
function handleLike($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $news_id = intval($_GET['id'] ?? 0);
    if (!$news_id) returnError('ID da notícia obrigatório');
    
    $user_id = $_SESSION['user_id'];
    
    // Verificar se já curtiu
    $check_sql = "SELECT id FROM user_favorites WHERE user_id = :user_id AND item_type = 'news' AND item_id = :news_id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        // Remover curtida
        $delete_sql = "DELETE FROM user_favorites WHERE user_id = :user_id AND item_type = 'news' AND item_id = :news_id";
        $delete_stmt = $db->prepare($delete_sql);
        $delete_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $delete_stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
        $delete_stmt->execute();
        
        $update_sql = "UPDATE news SET likes = GREATEST(0, likes - 1) WHERE id = :news_id";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
        $update_stmt->execute();
        
        returnSuccess(['liked' => false, 'message' => 'Curtida removida']);
    } else {
        // Adicionar curtida
        $insert_sql = "INSERT INTO user_favorites (user_id, item_type, item_id, created_at) VALUES (:user_id, 'news', :news_id, NOW())";
        $insert_stmt = $db->prepare($insert_sql);
        $insert_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $insert_stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
        $insert_stmt->execute();
        
        $update_sql = "UPDATE news SET likes = likes + 1 WHERE id = :news_id";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
        $update_stmt->execute();
        
        returnSuccess(['liked' => true, 'message' => 'Notícia curtida!']);
    }
}

/**
 * Visualizar notícia específica
 */
function handleView($db) {
    $news_id = intval($_GET['id'] ?? 0);
    if (!$news_id) returnError('ID da notícia obrigatório');
    
    $sql = "SELECT n.*, u.username, u.display_name,
                   (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id) as comments_count
            FROM news n
            JOIN users u ON n.author_id = u.id
            WHERE n.id = :id AND n.status = 'published'";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$news) returnError('Notícia não encontrada', 404);
    
    // Incrementar visualização
    $view_sql = "UPDATE news SET views = views + 1 WHERE id = :id";
    $view_stmt = $db->prepare($view_sql);
    $view_stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $view_stmt->execute();
    
    // Formatar dados
    $news['time_ago'] = timeAgo($news['published_at'] ?: $news['created_at']);
    $news['formatted_views'] = number_format($news['views'] + 1);
    $news['category_formatted'] = formatCategoryName($news['category']);
    $news['author_name'] = $news['display_name'] ?: $news['username'];
    $news['url'] = "/noticia/{$news['slug']}";
    
    returnSuccess($news);
}

/**
 * Buscar notícias relacionadas
 */
function handleRelated($db) {
    $news_id = intval($_GET['id'] ?? 0);
    $category = trim($_GET['category'] ?? '');
    $limit = max(1, min(10, intval($_GET['limit'] ?? 4)));
    
    if (!$news_id || !$category) returnError('ID e categoria obrigatórios');
    
    $sql = "SELECT id, slug, title, featured_image, category, views, created_at
            FROM news 
            WHERE id != :news_id AND category = :category AND status = 'published'
            ORDER BY views DESC, created_at DESC
            LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
    $stmt->bindValue(':category', $category);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($related as &$item) {
        $item['time_ago'] = timeAgo($item['created_at']);
        $item['formatted_views'] = number_format($item['views']);
        $item['url'] = "/noticia/{$item['slug']}";
    }
    
    returnSuccess($related);
}

/**
 * Estatísticas das notícias
 */
function handleStats($db) {
    $stats = [];
    
    // Total por categoria
    $cat_sql = "SELECT category, COUNT(*) as count FROM news WHERE status = 'published' GROUP BY category";
    $cat_stmt = $db->prepare($cat_sql);
    $cat_stmt->execute();
    $stats['by_category'] = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mais populares
    $pop_sql = "SELECT id, title, views FROM news WHERE status = 'published' ORDER BY views DESC LIMIT 5";
    $pop_stmt = $db->prepare($pop_sql);
    $pop_stmt->execute();
    $stats['most_popular'] = $pop_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas gerais
    $gen_sql = "SELECT COUNT(*) as total_news, SUM(views) as total_views, SUM(likes) as total_likes 
                FROM news WHERE status = 'published'";
    $gen_stmt = $db->prepare($gen_sql);
    $gen_stmt->execute();
    $stats['general'] = $gen_stmt->fetch(PDO::FETCH_ASSOC);
    
    returnSuccess($stats);
}

/**
 * Criar nova notícia (requer admin)
 */
function handleCreateNews($db) {
    session_start();
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? 0) < 4) {
        returnError('Acesso negado - Admin necessário', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['title']) || empty($input['content'])) {
        returnError('Título e conteúdo são obrigatórios');
    }
    
    $slug = generateSlug($input['title']);
    
    // Verificar slug único
    $check_sql = "SELECT id FROM news WHERE slug = :slug";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':slug', $slug);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        $slug .= '-' . time();
    }
    
    $sql = "INSERT INTO news (slug, title, subtitle, content, excerpt, featured_image, 
                              category, author_id, status, featured, published_at) 
            VALUES (:slug, :title, :subtitle, :content, :excerpt, :featured_image, 
                    :category, :author_id, :status, :featured, :published_at)";
    
    $stmt = $db->prepare($sql);
    
    $published_at = ($input['status'] === 'published') ? date('Y-m-d H:i:s') : null;
    $excerpt = $input['excerpt'] ?: substr(strip_tags($input['content']), 0, 200);
    
    $stmt->bindValue(':slug', $slug);
    $stmt->bindValue(':title', sanitizeInput($input['title']));
    $stmt->bindValue(':subtitle', sanitizeInput($input['subtitle'] ?? ''));
    $stmt->bindValue(':content', $input['content']);
    $stmt->bindValue(':excerpt', $excerpt);
    $stmt->bindValue(':featured_image', sanitizeInput($input['featured_image'] ?? ''));
    $stmt->bindValue(':category', sanitizeInput($input['category'] ?? 'analysis'));
    $stmt->bindValue(':author_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':status', sanitizeInput($input['status'] ?? 'draft'));
    $stmt->bindValue(':featured', $input['featured'] ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':published_at', $published_at);
    
    $stmt->execute();
    $news_id = $db->lastInsertId();
    
    returnSuccess(['id' => $news_id, 'slug' => $slug, 'message' => 'Notícia criada!']);
}

/**
 * Atualizar notícia existente
 */
function handleUpdateNews($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $news_id = intval($_GET['id'] ?? 0);
    if (!$news_id) returnError('ID da notícia obrigatório');
    
    // Verificar permissão
    $check_sql = "SELECT author_id FROM news WHERE id = :id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $news = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$news) returnError('Notícia não encontrada', 404);
    
    if ($news['author_id'] != $_SESSION['user_id'] && ($_SESSION['user_level'] ?? 0) < 3) {
        returnError('Sem permissão para editar', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sql = "UPDATE news SET title = :title, content = :content, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', sanitizeInput($input['title']));
    $stmt->bindValue(':content', $input['content']);
    $stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $stmt->execute();
    
    returnSuccess(['message' => 'Notícia atualizada!']);
}

/**
 * Deletar notícia
 */
function handleDeleteNews($db) {
    session_start();
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? 0) < 3) {
        returnError('Acesso negado', 403);
    }
    
    $news_id = intval($_GET['id'] ?? 0);
    if (!$news_id) returnError('ID da notícia obrigatório');
    
    $sql = "DELETE FROM news WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) returnError('Notícia não encontrada', 404);
    
    returnSuccess(['message' => 'Notícia deletada!']);
}

// Funções auxiliares
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . 'h atrás';
    return floor($time/86400) . 'd atrás';
}

function formatCategoryName($category) {
    $categories = [
        'trailers' => 'Trailers',
        'analysis' => 'Análises',
        'theories' => 'Teorias',
        'maps' => 'Mapas',
        'characters' => 'Personagens'
    ];
    return $categories[$category] ?? ucfirst($category);
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[áàâãä]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòôõö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    return trim(preg_replace('/[\s-]+/', '-', $text), '-');
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>