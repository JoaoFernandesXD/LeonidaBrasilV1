<?php
// api/user-stats.php
// Endpoint para buscar estatísticas atualizadas do usuário

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Buscar contadores de seguidores
    $sql = "SELECT 
                (SELECT COUNT(*) FROM user_followers WHERE user_id = :user_id) as followers_count,
                (SELECT COUNT(*) FROM user_followers WHERE follower_id = :user_id) as following_count";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $follow_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'followers_count' => intval($follow_stats['followers_count']),
        'following_count' => intval($follow_stats['following_count'])
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas do usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro inesperado']);
}