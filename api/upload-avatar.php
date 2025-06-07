<?php
// api/upload-avatar.php
// Endpoint para upload de avatar

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

require_once '../controllers/UserController.php';

$userController = new UserController();
$userController->uploadAvatar();

?><?php
// api/follow.php
// Endpoint para seguir/deseguir usuários

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
    $user_id = intval($_POST['user_id'] ?? 0);
    $follow = isset($_POST['follow']) ? (bool)$_POST['follow'] : false;
    $current_user_id = $_SESSION['user_id'];
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
        exit;
    }
    
    if ($user_id == $current_user_id) {
        echo json_encode(['success' => false, 'message' => 'Você não pode seguir a si mesmo']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verificar se o usuário existe
    $sql = "SELECT id, username, display_name FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    if ($follow) {
        // Inserir relação de seguir (se não existir)
        $sql = "INSERT IGNORE INTO user_followers (user_id, follower_id) VALUES (:user_id, :follower_id)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':follower_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $message = 'Usuário seguido com sucesso';
    } else {
        // Remover relação de seguir
        $sql = "DELETE FROM user_followers WHERE user_id = :user_id AND follower_id = :follower_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':follower_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $message = 'Usuário desseguido com sucesso';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'following' => $follow
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao seguir/deseguir usuário: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro inesperado']);
}

?><