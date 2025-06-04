<?php
// api/forum_complete.php - Versão Completa da API do Fórum
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
    
    // Roteamento baseado em ação especial
    if (isset($_GET['action'])) {
        handleSpecialActions($db);
    }
    
    // Roteamento baseado em tipo de conteúdo
    if (isset($_GET['type'])) {
        handleContentType($db);
    }
    
    // Roteamento baseado em método HTTP
    switch ($method) {
        case 'GET':
            handleGetForum($db);
            break;
        case 'POST':
            handleCreateContent($db);
            break;
        case 'PUT':
            handleUpdateContent($db);
            break;
        case 'DELETE':
            handleDeleteContent($db);
            break;
        default:
            returnError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("API Forum Error: " . $e->getMessage());
    returnError('Erro interno: ' . $e->getMessage());
}

/**
 * Buscar conteúdo do fórum (tópicos ou respostas)
 */
function handleGetForum($db) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = max(1, min(50, intval($_GET['per_page'] ?? 8)));
    $category_id = intval($_GET['category'] ?? 0);
    $search = trim($_GET['search'] ?? '');
    $sort = trim($_GET['sort'] ?? 'recent'); // recent, popular, replies
    $topic_id = intval($_GET['topic'] ?? 0);
    
    // Se topic_id foi fornecido, buscar respostas do tópico
    if ($topic_id > 0) {
        handleGetReplies($db, $topic_id, $page, $per_page);
        return;
    }
    
    // Senão, buscar tópicos
    handleGetTopics($db, $page, $per_page, $category_id, $search, $sort);
}

/**
 * Buscar tópicos do fórum
 */
function handleGetTopics($db, $page, $per_page, $category_id, $search, $sort) {
    $offset = ($page - 1) * $per_page;
    
    // Construir query com filtros
    $where_conditions = ["ft.status IN ('open', 'pinned', 'closed')"];
    $params = [];
    
    if ($category_id > 0) {
        $where_conditions[] = "ft.category_id = :category_id";
        $params['category_id'] = $category_id;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(ft.title LIKE :search OR ft.content LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Definir ordenação
    $order_clause = "ft.status = 'pinned' DESC, ";
    switch ($sort) {
        case 'popular':
            $order_clause .= "ft.views DESC, ft.replies_count DESC";
            break;
        case 'replies':
            $order_clause .= "ft.replies_count DESC, ft.created_at DESC";
            break;
        case 'recent':
        default:
            $order_clause .= "ft.last_reply_at DESC, ft.created_at DESC";
            break;
    }
    
    // Query principal
    $sql = "SELECT ft.id, ft.title, ft.content, ft.views, ft.replies_count, ft.status,
                   ft.created_at, ft.last_reply_at,
                   fc.name as category_name, fc.icon as category_icon,
                   u.username, u.display_name, u.avatar,
                   lu.username as last_reply_username, lu.display_name as last_reply_display_name
            FROM forum_topics ft
            JOIN forum_categories fc ON ft.category_id = fc.id
            JOIN users u ON ft.author_id = u.id
            LEFT JOIN users lu ON ft.last_reply_by = lu.id
            WHERE {$where_clause}
            ORDER BY {$order_clause}
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total 
                  FROM forum_topics ft 
                  JOIN forum_categories fc ON ft.category_id = fc.id 
                  WHERE {$where_clause}";
    
    $count_stmt = $db->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue(":{$key}", $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Processar dados dos tópicos
    foreach ($topics as &$topic) {
        $topic['time_ago'] = timeAgo($topic['last_reply_at'] ?: $topic['created_at']);
        $topic['formatted_views'] = number_format($topic['views']);
        $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
        $topic['is_pinned'] = ($topic['status'] === 'pinned');
        $topic['is_locked'] = ($topic['status'] === 'locked');
        
        // Avatar padrão
        if (empty($topic['avatar'])) {
            $topic['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
        }
        
        // URL do tópico
        $topic['url'] = "/forum/topico/{$topic['id']}";
        
        // Informações da última resposta
        if ($topic['last_reply_at'] && $topic['last_reply_username']) {
            $topic['last_reply_author'] = $topic['last_reply_display_name'] ?: $topic['last_reply_username'];
            $topic['last_reply_time_ago'] = timeAgo($topic['last_reply_at']);
        }
        
        // Excerpt do conteúdo
        $topic['excerpt'] = truncateText(strip_tags($topic['content']), 150);
    }
    
    // Metadados da paginação
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
    
    returnSuccess($topics, $meta);
}

/**
 * Buscar respostas de um tópico
 */
function handleGetReplies($db, $topic_id, $page, $per_page) {
    $offset = ($page - 1) * $per_page;
    
    // Primeiro, buscar dados do tópico
    $topic_sql = "SELECT ft.id, ft.title, ft.content, ft.views, ft.replies_count, ft.status,
                         ft.created_at, fc.name as category_name,
                         u.username, u.display_name, u.avatar
                  FROM forum_topics ft
                  JOIN forum_categories fc ON ft.category_id = fc.id
                  JOIN users u ON ft.author_id = u.id
                  WHERE ft.id = :topic_id";
    
    $topic_stmt = $db->prepare($topic_sql);
    $topic_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $topic_stmt->execute();
    $topic = $topic_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) returnError('Tópico não encontrado', 404);
    
    // Incrementar visualização do tópico
    $view_sql = "UPDATE forum_topics SET views = views + 1 WHERE id = :topic_id";
    $view_stmt = $db->prepare($view_sql);
    $view_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $view_stmt->execute();
    
    // Buscar respostas
    $replies_sql = "SELECT fr.id, fr.content, fr.likes, fr.created_at,
                           u.username, u.display_name, u.avatar
                    FROM forum_replies fr
                    JOIN users u ON fr.author_id = u.id
                    WHERE fr.topic_id = :topic_id AND fr.status = 'active'
                    ORDER BY fr.created_at ASC
                    LIMIT :limit OFFSET :offset";
    
    $replies_stmt = $db->prepare($replies_sql);
    $replies_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $replies_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $replies_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $replies_stmt->execute();
    
    $replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total de respostas
    $count_sql = "SELECT COUNT(*) as total FROM forum_replies WHERE topic_id = :topic_id AND status = 'active'";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $total_replies = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Processar dados das respostas
    foreach ($replies as &$reply) {
        $reply['time_ago'] = timeAgo($reply['created_at']);
        $reply['author_name'] = $reply['display_name'] ?: $reply['username'];
        $reply['formatted_likes'] = number_format($reply['likes']);
        
        if (empty($reply['avatar'])) {
            $reply['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
        }
    }
    
    // Processar dados do tópico
    $topic['time_ago'] = timeAgo($topic['created_at']);
    $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
    $topic['formatted_views'] = number_format($topic['views'] + 1);
    
    if (empty($topic['avatar'])) {
        $topic['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
    }
    
    // Metadados
    $total_pages = ceil($total_replies / $per_page);
    $meta = [
        'current_page' => $page,
        'per_page' => $per_page,
        'total_replies' => intval($total_replies),
        'total_pages' => $total_pages,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ];
    
    returnSuccess([
        'topic' => $topic,
        'replies' => $replies
    ], $meta);
}

/**
 * Ações especiais do fórum
 */
function handleSpecialActions($db) {
    $action = trim($_GET['action'] ?? '');
    
    switch ($action) {
        case 'categories':
            handleGetCategories($db);
            break;
        case 'stats':
            handleForumStats($db);
            break;
        case 'search':
            handleForumSearch($db);
            break;
        case 'like_reply':
            handleLikeReply($db);
            break;
        case 'mark_read':
            handleMarkAsRead($db);
            break;
        default:
            returnError('Ação não reconhecida');
    }
}

/**
 * Buscar categorias do fórum
 */
function handleGetCategories($db) {
    $sql = "SELECT fc.id, fc.name, fc.description, fc.icon, fc.order_position,
                   COUNT(ft.id) as topics_count,
                   MAX(ft.last_reply_at) as last_activity
            FROM forum_categories fc
            LEFT JOIN forum_topics ft ON fc.id = ft.category_id
            WHERE fc.status = 'active'
            GROUP BY fc.id
            ORDER BY fc.order_position ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as &$category) {
        $category['topics_count'] = intval($category['topics_count']);
        $category['last_activity_ago'] = $category['last_activity'] ? timeAgo($category['last_activity']) : 'Nunca';
    }
    
    returnSuccess($categories);
}

/**
 * Estatísticas do fórum
 */
function handleForumStats($db) {
    $stats = [];
    
    // Estatísticas gerais
    $general_sql = "SELECT 
                        (SELECT COUNT(*) FROM forum_topics) as total_topics,
                        (SELECT COUNT(*) FROM forum_replies) as total_replies,
                        (SELECT COUNT(DISTINCT author_id) FROM forum_topics) as active_users,
                        (SELECT SUM(views) FROM forum_topics) as total_views";
    
    $general_stmt = $db->prepare($general_sql);
    $general_stmt->execute();
    $stats['general'] = $general_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tópicos mais populares
    $popular_sql = "SELECT id, title, views, replies_count FROM forum_topics ORDER BY views DESC LIMIT 5";
    $popular_stmt = $db->prepare($popular_sql);
    $popular_stmt->execute();
    $stats['most_popular'] = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Usuários mais ativos
    $users_sql = "SELECT u.username, u.display_name, COUNT(ft.id) as topics_count
                  FROM users u
                  JOIN forum_topics ft ON u.id = ft.author_id
                  GROUP BY u.id
                  ORDER BY topics_count DESC
                  LIMIT 5";
    
    $users_stmt = $db->prepare($users_sql);
    $users_stmt->execute();
    $stats['top_users'] = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    returnSuccess($stats);
}

/**
 * Busca no fórum
 */
function handleForumSearch($db) {
    $query = trim($_GET['q'] ?? '');
    $limit = max(1, min(20, intval($_GET['limit'] ?? 10)));
    
    if (empty($query)) returnError('Termo de busca obrigatório');
    
    $search_term = "%{$query}%";
    
    // Buscar em tópicos
    $topics_sql = "SELECT 'topic' as type, ft.id, ft.title as title, 
                          SUBSTRING(ft.content, 1, 200) as excerpt,
                          ft.views, ft.replies_count, ft.created_at,
                          u.username, u.display_name
                   FROM forum_topics ft
                   JOIN users u ON ft.author_id = u.id
                   WHERE (ft.title LIKE :search OR ft.content LIKE :search)
                   AND ft.status IN ('open', 'pinned', 'closed')
                   ORDER BY ft.views DESC
                   LIMIT :limit";
    
    $topics_stmt = $db->prepare($topics_sql);
    $topics_stmt->bindValue(':search', $search_term);
    $topics_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $topics_stmt->execute();
    
    $results = $topics_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar resultados
    foreach ($results as &$result) {
        $result['time_ago'] = timeAgo($result['created_at']);
        $result['author_name'] = $result['display_name'] ?: $result['username'];
        $result['highlight'] = highlightSearchTerm($result['title'], $query);
        $result['url'] = "/forum/topico/{$result['id']}";
    }
    
    returnSuccess($results);
}

/**
 * Curtir resposta do fórum
 */
function handleLikeReply($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $reply_id = intval($_GET['id'] ?? 0);
    if (!$reply_id) returnError('ID da resposta obrigatório');
    
    // Verificar se resposta existe
    $check_sql = "SELECT id FROM forum_replies WHERE id = :reply_id AND status = 'active'";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) returnError('Resposta não encontrada', 404);
    
    // Incrementar likes
    $like_sql = "UPDATE forum_replies SET likes = likes + 1 WHERE id = :reply_id";
    $like_stmt = $db->prepare($like_sql);
    $like_stmt->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
    $like_stmt->execute();
    
    returnSuccess(['message' => 'Resposta curtida!']);
}

/**
 * Marcar tópico como lido
 */
function handleMarkAsRead($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $topic_id = intval($_GET['id'] ?? 0);
    if (!$topic_id) returnError('ID do tópico obrigatório');
    
    // Por enquanto, apenas retornar sucesso (implementar tabela de leitura depois)
    returnSuccess(['message' => 'Tópico marcado como lido']);
}

/**
 * Criar novo tópico
 */
function handleCreateContent($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'topic';
    
    if ($type === 'topic') {
        handleCreateTopic($db, $input);
    } elseif ($type === 'reply') {
        handleCreateReply($db, $input);
    } else {
        returnError('Tipo de conteúdo inválido');
    }
}

/**
 * Criar novo tópico
 */
function handleCreateTopic($db, $input) {
    if (empty($input['title']) || empty($input['content']) || empty($input['category_id'])) {
        returnError('Título, conteúdo e categoria são obrigatórios');
    }
    
    // Verificar se categoria existe
    $cat_sql = "SELECT id FROM forum_categories WHERE id = :category_id AND status = 'active'";
    $cat_stmt = $db->prepare($cat_sql);
    $cat_stmt->bindValue(':category_id', intval($input['category_id']), PDO::PARAM_INT);
    $cat_stmt->execute();
    
    if (!$cat_stmt->fetch()) returnError('Categoria não encontrada');
    
    $sql = "INSERT INTO forum_topics (category_id, author_id, title, content, status, created_at) 
            VALUES (:category_id, :author_id, :title, :content, 'open', NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':category_id', intval($input['category_id']), PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':title', sanitizeInput($input['title']));
    $stmt->bindValue(':content', $input['content']);
    $stmt->execute();
    
    $topic_id = $db->lastInsertId();
    
    // Dar XP ao usuário
    $xp_sql = "UPDATE users SET experience_points = experience_points + 10 WHERE id = :user_id";
    $xp_stmt = $db->prepare($xp_sql);
    $xp_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $xp_stmt->execute();
    
    returnSuccess(['id' => $topic_id, 'message' => 'Tópico criado com sucesso!']);
}

/**
 * Criar nova resposta
 */
function handleCreateReply($db, $input) {
    if (empty($input['content']) || empty($input['topic_id'])) {
        returnError('Conteúdo e ID do tópico são obrigatórios');
    }
    
    $topic_id = intval($input['topic_id']);
    
    // Verificar se tópico existe e não está fechado
    $topic_sql = "SELECT id, status FROM forum_topics WHERE id = :topic_id";
    $topic_stmt = $db->prepare($topic_sql);
    $topic_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $topic_stmt->execute();
    $topic = $topic_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) returnError('Tópico não encontrado', 404);
    if ($topic['status'] === 'locked') returnError('Tópico está fechado para respostas', 403);
    
    // Inserir resposta
    $sql = "INSERT INTO forum_replies (topic_id, author_id, content, created_at) 
            VALUES (:topic_id, :author_id, :content, NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':content', $input['content']);
    $stmt->execute();
    
    $reply_id = $db->lastInsertId();
    
    // Atualizar contador do tópico
    $update_sql = "UPDATE forum_topics 
                   SET replies_count = replies_count + 1, 
                       last_reply_at = NOW(), 
                       last_reply_by = :user_id 
                   WHERE id = :topic_id";
    
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $update_stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $update_stmt->execute();
    
    // Dar XP ao usuário
    $xp_sql = "UPDATE users SET experience_points = experience_points + 5 WHERE id = :user_id";
    $xp_stmt = $db->prepare($xp_sql);
    $xp_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $xp_stmt->execute();
    
    returnSuccess(['id' => $reply_id, 'message' => 'Resposta adicionada com sucesso!']);
}

/**
 * Atualizar tópico ou resposta
 */
function handleUpdateContent($db) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        returnError('Login necessário', 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'topic';
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) returnError('ID obrigatório');
    
    if ($type === 'topic') {
        handleUpdateTopic($db, $id, $input);
    } elseif ($type === 'reply') {
        handleUpdateReply($db, $id, $input);
    } else {
        returnError('Tipo inválido');
    }
}

/**
 * Atualizar tópico
 */
function handleUpdateTopic($db, $topic_id, $input) {
    // Verificar permissão
    $check_sql = "SELECT author_id FROM forum_topics WHERE id = :id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $topic = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) returnError('Tópico não encontrado', 404);
    
    if ($topic['author_id'] != $_SESSION['user_id'] && ($_SESSION['user_level'] ?? 0) < 3) {
        returnError('Sem permissão para editar', 403);
    }
    
    $sql = "UPDATE forum_topics SET title = :title, content = :content, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', sanitizeInput($input['title']));
    $stmt->bindValue(':content', $input['content']);
    $stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);
    $stmt->execute();
    
    returnSuccess(['message' => 'Tópico atualizado!']);
}

/**
 * Atualizar resposta
 */
function handleUpdateReply($db, $reply_id, $input) {
    // Verificar permissão
    $check_sql = "SELECT author_id FROM forum_replies WHERE id = :id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindValue(':id', $reply_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $reply = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reply) returnError('Resposta não encontrada', 404);
    
    if ($reply['author_id'] != $_SESSION['user_id'] && ($_SESSION['user_level'] ?? 0) < 3) {
        returnError('Sem permissão para editar', 403);
    }
    
    $sql = "UPDATE forum_replies SET content = :content, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':content', $input['content']);
    $stmt->bindValue(':id', $reply_id, PDO::PARAM_INT);
    $stmt->execute();
    
    returnSuccess(['message' => 'Resposta atualizada!']);
}

/**
 * Deletar tópico ou resposta
 */
function handleDeleteContent($db) {
    session_start();
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? 0) < 3) {
        returnError('Acesso negado', 403);
    }
    
    $type = $_GET['type'] ?? 'topic';
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) returnError('ID obrigatório');
    
    if ($type === 'topic') {
        // Deletar tópico e suas respostas
        $delete_replies_sql = "DELETE FROM forum_replies WHERE topic_id = :id";
        $delete_replies_stmt = $db->prepare($delete_replies_sql);
        $delete_replies_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $delete_replies_stmt->execute();
        
        $delete_topic_sql = "DELETE FROM forum_topics WHERE id = :id";
        $delete_topic_stmt = $db->prepare($delete_topic_sql);
        $delete_topic_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $delete_topic_stmt->execute();
        
        returnSuccess(['message' => 'Tópico deletado com sucesso!']);
        
    } elseif ($type === 'reply') {
        // Deletar resposta
        $delete_reply_sql = "UPDATE forum_replies SET status = 'deleted' WHERE id = :id";
        $delete_reply_stmt = $db->prepare($delete_reply_sql);
        $delete_reply_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $delete_reply_stmt->execute();
        
        returnSuccess(['message' => 'Resposta deletada com sucesso!']);
    } else {
        returnError('Tipo inválido');
    }
}

/**
 * Lidar com tipos de conteúdo específicos
 */
function handleContentType($db) {
    $type = $_GET['type'] ?? '';
    
    switch ($type) {
        case 'hot_topics':
            handleHotTopics($db);
            break;
        case 'recent_activity':
            handleRecentActivity($db);
            break;
        case 'user_topics':
            handleUserTopics($db);
            break;
        case 'trending':
            handleTrendingTopics($db);
            break;
        default:
            returnError('Tipo de conteúdo não reconhecido');
    }
}

/**
 * Buscar tópicos em alta (hot topics)
 */
function handleHotTopics($db) {
    $limit = max(1, min(20, intval($_GET['limit'] ?? 5)));
    
    // Tópicos com mais atividade nas últimas 24 horas
    $sql = "SELECT ft.id, ft.title, ft.views, ft.replies_count, ft.created_at,
                   fc.name as category_name, fc.icon as category_icon,
                   u.username, u.display_name, u.avatar
            FROM forum_topics ft
            JOIN forum_categories fc ON ft.category_id = fc.id
            JOIN users u ON ft.author_id = u.id
            WHERE ft.status IN ('open', 'pinned')
            AND ft.last_reply_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ft.replies_count DESC, ft.views DESC
            LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topics as &$topic) {
        $topic['time_ago'] = timeAgo($topic['created_at']);
        $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
        $topic['url'] = "/forum/topico/{$topic['id']}";
        
        if (empty($topic['avatar'])) {
            $topic['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
        }
    }
    
    returnSuccess($topics);
}

/**
 * Atividade recente do fórum
 */
function handleRecentActivity($db) {
    $limit = max(1, min(20, intval($_GET['limit'] ?? 10)));
    
    $sql = "SELECT 'topic' as type, ft.id, ft.title as content, ft.created_at,
                   u.username, u.display_name, u.avatar,
                   fc.name as category_name
            FROM forum_topics ft
            JOIN users u ON ft.author_id = u.id
            JOIN forum_categories fc ON ft.category_id = fc.id
            WHERE ft.status IN ('open', 'pinned', 'closed')
            
            UNION ALL
            
            SELECT 'reply' as type, fr.id, 
                   CONCAT('Respondeu em: ', ft.title) as content, 
                   fr.created_at,
                   u.username, u.display_name, u.avatar,
                   fc.name as category_name
            FROM forum_replies fr
            JOIN forum_topics ft ON fr.topic_id = ft.id
            JOIN users u ON fr.author_id = u.id
            JOIN forum_categories fc ON ft.category_id = fc.id
            WHERE fr.status = 'active'
            
            ORDER BY created_at DESC
            LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($activities as &$activity) {
        $activity['time_ago'] = timeAgo($activity['created_at']);
        $activity['author_name'] = $activity['display_name'] ?: $activity['username'];
        
        if (empty($activity['avatar'])) {
            $activity['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
        }
        
        $activity['url'] = $activity['type'] === 'topic' 
            ? "/forum/topico/{$activity['id']}"
            : "/forum/topico/{$activity['id']}#reply-{$activity['id']}";
    }
    
    returnSuccess($activities);
}

/**
 * Tópicos de um usuário específico
 */
function handleUserTopics($db) {
    $user_id = intval($_GET['user_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = max(1, min(20, intval($_GET['per_page'] ?? 10)));
    
    if (!$user_id) returnError('ID do usuário obrigatório');
    
    $offset = ($page - 1) * $per_page;
    
    // Verificar se usuário existe
    $user_sql = "SELECT username, display_name FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_sql);
    $user_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) returnError('Usuário não encontrado', 404);
    
    // Buscar tópicos do usuário
    $sql = "SELECT ft.id, ft.title, ft.views, ft.replies_count, ft.status, ft.created_at,
                   fc.name as category_name, fc.icon as category_icon
            FROM forum_topics ft
            JOIN forum_categories fc ON ft.category_id = fc.id
            WHERE ft.author_id = :user_id
            ORDER BY ft.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM forum_topics WHERE author_id = :user_id";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    foreach ($topics as &$topic) {
        $topic['time_ago'] = timeAgo($topic['created_at']);
        $topic['url'] = "/forum/topico/{$topic['id']}";
        $topic['is_pinned'] = ($topic['status'] === 'pinned');
        $topic['is_locked'] = ($topic['status'] === 'locked');
    }
    
    $meta = [
        'user' => [
            'id' => $user_id,
            'name' => $user['display_name'] ?: $user['username']
        ],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => intval($total),
            'total_pages' => ceil($total / $per_page)
        ]
    ];
    
    returnSuccess($topics, $meta);
}

/**
 * Tópicos em tendência (trending)
 */
function handleTrendingTopics($db) {
    $limit = max(1, min(10, intval($_GET['limit'] ?? 5)));
    
    // Algoritmo simples de trending: tópicos com mais atividade recente
    $sql = "SELECT ft.id, ft.title, ft.views, ft.replies_count, ft.created_at,
                   fc.name as category_name,
                   u.username, u.display_name,
                   (ft.views * 0.3 + ft.replies_count * 0.7) as trend_score
            FROM forum_topics ft
            JOIN forum_categories fc ON ft.category_id = fc.id
            JOIN users u ON ft.author_id = u.id
            WHERE ft.status IN ('open', 'pinned')
            AND ft.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY trend_score DESC, ft.last_reply_at DESC
            LIMIT :limit";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topics as &$topic) {
        $topic['time_ago'] = timeAgo($topic['created_at']);
        $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
        $topic['url'] = "/forum/topico/{$topic['id']}";
        $topic['trend_score'] = round($topic['trend_score'], 1);
    }
    
    returnSuccess($topics);
}

/**
 * Função para destacar termo de busca no texto
 */
function highlightSearchTerm($text, $term) {
    return str_ireplace($term, "<mark>{$term}</mark>", $text);
}

/**
 * Função para truncar texto
 */
function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Função para calcular tempo decorrido
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . 'h atrás';
    if ($time < 2592000) return floor($time/86400) . 'd atrás';
    if ($time < 31536000) return floor($time/2592000) . ' meses atrás';
    
    return floor($time/31536000) . ' anos atrás';
}

/**
 * Função para sanitizar input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para log de atividades
 */
function logActivity($db, $user_id, $action, $details = []) {
    try {
        $sql = "INSERT INTO activity_log (user_id, action, details, created_at) 
                VALUES (:user_id, :action, :details, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':details', json_encode($details));
        $stmt->execute();
    } catch (Exception $e) {
        // Log silencioso - não quebrar a aplicação se log falhar
        error_log("Activity log error: " . $e->getMessage());
    }
}

?>

<!-- 
EXEMPLOS DE USO DA API:

1. Buscar tópicos de uma categoria:
GET /api/forum.php?category=1&page=1&per_page=10

2. Buscar respostas de um tópico:
GET /api/forum.php?topic=123&page=1

3. Buscar categorias:
GET /api/forum.php?action=categories

4. Buscar tópicos em alta:
GET /api/forum.php?type=hot_topics&limit=5

5. Criar novo tópico:
POST /api/forum.php
{
    "type": "topic",
    "title": "Novo tópico sobre GTA VI",
    "content": "Conteúdo do tópico...",
    "category_id": 1
}

6. Responder tópico:
POST /api/forum.php
{
    "type": "reply",
    "content": "Minha resposta...",
    "topic_id": 123
}

7. Buscar no fórum:
GET /api/forum.php?action=search&q=jason&limit=10

8. Curtir resposta:
GET /api/forum.php?action=like_reply&id=456

9. Estatísticas do fórum:
GET /api/forum.php?action=stats

10. Atividade recente:
GET /api/forum.php?type=recent_activity&limit=10

11. Tópicos de um usuário:
GET /api/forum.php?type=user_topics&user_id=123&page=1

12. Tópicos em tendência:
GET /api/forum.php?type=trending&limit=5

-->