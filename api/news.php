<?php
// api/news.php - Versão Simplificada
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na saída

// Headers obrigatórios
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Função para retornar sucesso JSON
function returnSuccess($data, $meta = null) {
    $response = [
        'success' => true,
        'data' => $data
    ];
    
    if ($meta) {
        $response['meta'] = $meta;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Verificar se dependências existem
    if (!file_exists('../config/database.php')) {
        returnError('Arquivo database.php não encontrado');
    }
    
    if (!file_exists('../utils/helpers.php')) {
        returnError('Arquivo helpers.php não encontrado');
    }
    
    // Incluir dependências
    require_once '../config/database.php';
    require_once '../utils/helpers.php';
    
    // Testar conexão com banco
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        returnError('Falha na conexão com banco de dados');
    }
    
    // Processar apenas GET por enquanto
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        returnError('Apenas método GET suportado nesta versão', 405);
    }
    
    // Parâmetros da requisição
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = max(1, min(50, intval($_GET['per_page'] ?? 6)));
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    
    $offset = ($page - 1) * $per_page;
    
    // Query base
    $where_conditions = ["n.status = 'published'"];
    $params = [];
    
    // Filtro por categoria
    if (!empty($category)) {
        $where_conditions[] = "n.category = :category";
        $params['category'] = $category;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Query principal - simplificada
    $sql = "SELECT n.id, n.slug, n.title, n.excerpt, n.featured_image, 
                   n.category, n.views, n.likes, n.created_at, n.published_at,
                   u.username, u.display_name,
                   0 as comments_count
            FROM news n
            JOIN users u ON n.author_id = u.id
            WHERE {$where_clause}
            ORDER BY n.featured DESC, n.published_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    // Bind parâmetros
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query para contar total
    $count_sql = "SELECT COUNT(*) as total FROM news n WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue(":{$key}", $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Processar dados
    foreach ($news as &$item) {
        // Formatar data
        if ($item['published_at']) {
            $time_diff = time() - strtotime($item['published_at']);
            if ($time_diff < 3600) {
                $item['time_ago'] = 'há ' . floor($time_diff / 60) . ' min';
            } elseif ($time_diff < 86400) {
                $item['time_ago'] = 'há ' . floor($time_diff / 3600) . 'h';
            } else {
                $item['time_ago'] = 'há ' . floor($time_diff / 86400) . 'd';
            }
        } else {
            $item['time_ago'] = 'Recente';
        }
        
        // Formatar views
        $item['formatted_views'] = number_format($item['views']);
        
        // Nome do autor
        $item['author_name'] = $item['display_name'] ?: $item['username'];
        
        // Categoria formatada
        $categories = [
            'trailers' => 'Trailers',
            'analysis' => 'Análises',
            'theories' => 'Teorias',
            'maps' => 'Mapas',
            'characters' => 'Personagens'
        ];
        $item['category_formatted'] = $categories[$item['category']] ?? ucfirst($item['category']);
        
        // URL da notícia
        $item['url'] = "/noticia/{$item['slug']}";
        
        // Imagem padrão se não houver
        if (empty($item['featured_image'])) {
            $item['featured_image'] = 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg';
        }
    }
    
    // Metadados da paginação
    $total_pages = ceil($total / $per_page);
    $has_next = $page < $total_pages;
    $has_prev = $page > 1;
    
    $meta = [
        'current_page' => $page,
        'per_page' => $per_page,
        'total' => intval($total),
        'total_pages' => $total_pages,
        'has_next' => $has_next,
        'has_prev' => $has_prev,
        'next_page' => $has_next ? $page + 1 : null,
        'prev_page' => $has_prev ? $page - 1 : null
    ];
    
    returnSuccess($news, $meta);
    
} catch (PDOException $e) {
    error_log("API News PDO Error: " . $e->getMessage());
    returnError('Erro de banco de dados: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("API News Error: " . $e->getMessage());
    returnError('Erro interno: ' . $e->getMessage());
} catch (Error $e) {
    error_log("API News Fatal Error: " . $e->getMessage());
    returnError('Erro fatal: ' . $e->getMessage());
}
?>