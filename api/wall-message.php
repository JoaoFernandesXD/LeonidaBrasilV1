<?php
// api/wall-message.php
// Endpoint para enviar mensagens no mural

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $current_user_id = $_SESSION['user_id'];
    
    if ($target_user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Mensagem não pode estar vazia']);
        exit;
    }
    
    if (strlen($message) > 500) {
        echo json_encode(['success' => false, 'message' => 'Mensagem muito longa (máximo 500 caracteres)']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar se o usuário de destino existe
    $sql = "SELECT id, privacy_settings FROM user_profiles WHERE user_id = :user_id
            UNION SELECT u.id, NULL FROM users u WHERE u.id = :user_id AND NOT EXISTS (SELECT 1 FROM user_profiles WHERE user_id = u.id)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Verificar configurações de privacidade
    $privacy_settings = $target_user['privacy_settings'] ? json_decode($target_user['privacy_settings'], true) : [];
    $allow_messages = $privacy_settings['allow_messages'] ?? true;
    
    if (!$allow_messages) {
        echo json_encode(['success' => false, 'message' => 'Este usuário não permite mensagens no mural']);
        exit;
    }
    
    // Verificar se não é spam (limite de mensagens por minuto)
    $sql = "SELECT COUNT(*) FROM wall_messages 
            WHERE author_id = :author_id AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':author_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recent_messages = $stmt->fetchColumn();
    
    if ($recent_messages >= 3) {
        echo json_encode(['success' => false, 'message' => 'Muitas mensagens enviadas. Aguarde um momento.']);
        exit;
    }
    
    // Inserir mensagem
    $sql = "INSERT INTO wall_messages (user_id, author_id, content, created_at) 
            VALUES (:user_id, :author_id, :content, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $current_user_id, PDO::PARAM_INT);
    $stmt->bindValue(':content', $message);
    $stmt->execute();
    
    // Buscar dados do autor para retornar
    $sql = "SELECT username, display_name, avatar FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $author = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Recado enviado com sucesso',
        'author_name' => $author['display_name'] ?: $author['username'],
        'author_avatar' => $author['avatar'] ?: getDefaultAvatar()
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao enviar mensagem no mural: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro inesperado']);
}

