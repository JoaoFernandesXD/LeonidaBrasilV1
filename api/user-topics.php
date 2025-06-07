<?php
// api/user-topics.php
// Endpoint para carregar mais tópicos do usuário

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $user_id = intval($_GET['user_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 6;
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar se o usuário existe
    $sql = "SELECT id, username, display_name, avatar FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Buscar tópicos com paginação
    $offset = ($page - 1) * $per_page;
    
    $sql = "SELECT ft.id, ft.title, ft.slug, ft.views, ft.replies_count, 
                   ft.status, ft.created_at, ft.last_reply_at,
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
    
    // Formatar dados dos tópicos
    $formatted_topics = [];
    foreach ($topics as $topic) {
        $formatted_topics[] = [
            'id' => $topic['id'],
            'title' => $topic['title'],
            'url' => site_url("forum/topico/{$topic['slug']}"),
            'author_name' => $user['display_name'] ?: $user['username'],
            'author_avatar' => $user['avatar'] ?: getDefaultAvatar(),
            'time_ago' => time_ago($topic['created_at']),
            'replies_count' => number_format($topic['replies_count']),
            'formatted_views' => number_format($topic['views']),
            'is_pinned' => ($topic['status'] === 'pinned'),
            'is_locked' => ($topic['status'] === 'locked'),
            'category_name' => $topic['category_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'topics' => $formatted_topics,
        'has_more' => count($topics) === $per_page
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar tópicos do usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro inesperado']);
}

?>