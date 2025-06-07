<?php
// controllers/UserController.php
// Controller completo para área do usuário

class UserController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Página de perfil do usuário
     */
    public function profile($username = null) {
        if (!$username && !is_logged_in()) {
            http_response_code(404);
            include 'views/pages/404.php';
            return;
        }
    
        // Determinar o user_id a ser exibido
        if ($username) {
            // Buscar user_id com base no username
            $user = $this->getUserByUsername($username);
            if (!$user) {
                set_flash_message('error', 'Usuário não encontrado.');
                http_response_code(404);
                include 'views/pages/404.php';
                return;
            }
            $user_id = $user['id'];
        } else {
            // Se não houver username, mostrar o perfil do usuário logado
            $user_id = $_SESSION['user_id'];
        }
    
        $user = $this->getUserProfile($user_id);
        if (!$user) {
            set_flash_message('error', 'Perfil não encontrado.');
            http_response_code(404);
            include 'views/pages/404.php';
            return;
        }
    
        // Verificar privacidade para perfis de terceiros
        if ($username && $user_id != ($_SESSION['user_id'] ?? 0)) {
            $privacy = $user['privacy_settings']['profile_visibility'] ?? 'public';
            if ($privacy === 'private') {
                set_flash_message('error', 'Este perfil é privado.');
                http_response_code(403);
                include 'views/pages/403.php';
                return;
            }
            if ($privacy === 'members' && !is_logged_in()) {
                set_flash_message('error', 'Você precisa estar logado para visualizar este perfil.');
                redirect(site_url('login'));
                return;
            }
        }
    
        $stats = $this->getUserStats($user_id);
        $recent_activity = $this->getRecentActivity($user_id);
        $achievements = $this->getUserAchievements($user_id);
        $topics = $this->getUserTopics($user_id, 1, 10);
    
        $data = [
            'user' => $user,
            'stats' => $stats,
            'recent_activity' => $recent_activity,
            'achievements' => $achievements,
            'topics' => $topics,
            'meta_title' => 'Perfil de ' . ($user['display_name'] ?? $user['username']) . ' - Leonida Brasil',
            'meta_description' => 'Perfil de ' . ($user['display_name'] ?? $user['username'])
        ];
    
        $this->loadView('profile', $data);
    }

    
// Métodos adicionais para o UserController.php
// Adicionar estes métodos ao arquivo UserController.php existente

/**
 * Upload de avatar via AJAX
 */
public function uploadAvatar() {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
            return;
        }

        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Verificar se é uma imagem válida
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            echo json_encode(['success' => false, 'message' => 'Arquivo não é uma imagem válida']);
            return;
        }

        // Validar tipo de arquivo
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Formato não permitido. Use JPG, PNG, GIF ou WebP']);
            return;
        }

        // Validar tamanho
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB']);
            return;
        }

        // Validar dimensões
        if ($image_info[0] > 2000 || $image_info[1] > 2000) {
            echo json_encode(['success' => false, 'message' => 'Imagem muito grande. Máximo 2000x2000 pixels']);
            return;
        }

        // Gerar nome único para o arquivo
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = 'jpg'; // padrão se não conseguir detectar
        }
        
        $filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $upload_dir = 'uploads/avatars/';
        $upload_path = $upload_dir . $filename;

        // Criar diretório se não existir
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Remover avatar anterior se existir
        $sql = "SELECT avatar FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $old_avatar = $stmt->fetchColumn();
        
        if ($old_avatar && file_exists($old_avatar) && strpos($old_avatar, 'avatar_') !== false) {
            unlink($old_avatar);
        }

        // Mover e redimensionar arquivo
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Redimensionar se necessário
            $this->resizeImage($upload_path, 200, 200);
            
            // Atualizar avatar no banco
            $sql = "UPDATE users SET avatar = :avatar WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':avatar', $upload_path);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();

            // Atualizar sessão se existir display_name
            $sql = "SELECT display_name FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $display_name = $stmt->fetchColumn();
            
            if ($display_name) {
                $_SESSION['display_name'] = $display_name;
            }

            echo json_encode([
                'success' => true,
                'avatar_url' => site_url($upload_path),
                'message' => 'Avatar atualizado com sucesso'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo']);
        }
    } catch (Exception $e) {
        error_log("Erro ao fazer upload de avatar: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

/**
 * Redimensionar imagem mantendo proporção
 */
private function resizeImage($file_path, $max_width, $max_height) {
    try {
        $image_info = getimagesize($file_path);
        if (!$image_info) return false;
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Se já está no tamanho correto, não fazer nada
        if ($original_width <= $max_width && $original_height <= $max_height) {
            return true;
        }
        
        // Calcular novas dimensões mantendo proporção
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        $new_width = round($original_width * $ratio);
        $new_height = round($original_height * $ratio);
        
        // Criar imagem baseada no tipo
        switch ($mime_type) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file_path);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($file_path);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        // Criar nova imagem redimensionada
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Preservar transparência para PNG e GIF
        if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        // Redimensionar
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
        
        // Salvar baseado no tipo original
        $success = false;
        switch ($mime_type) {
            case 'image/jpeg':
                $success = imagejpeg($resized, $file_path, 90);
                break;
            case 'image/png':
                $success = imagepng($resized, $file_path, 9);
                break;
            case 'image/gif':
                $success = imagegif($resized, $file_path);
                break;
            case 'image/webp':
                $success = imagewebp($resized, $file_path, 90);
                break;
        }
        
        // Limpar memória
        imagedestroy($source);
        imagedestroy($resized);
        
        return $success;
        
    } catch (Exception $e) {
        error_log("Erro ao redimensionar imagem: " . $e->getMessage());
        return false;
    }
}

/**
 * Seguir/Deseguir usuário
 */
public function followUser() {
    header('Content-Type: application/json');
    
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        $user_id = intval($_POST['user_id'] ?? 0);
        $follow = isset($_POST['follow']) ? (bool)$_POST['follow'] : false;
        $current_user_id = $_SESSION['user_id'];
        
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
            return;
        }
        
        if ($user_id == $current_user_id) {
            echo json_encode(['success' => false, 'message' => 'Você não pode seguir a si mesmo']);
            return;
        }
        
        // Verificar se o usuário existe
        $sql = "SELECT id, username, display_name FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            return;
        }
        
        if ($follow) {
            // Inserir relação de seguir (se não existir)
            $sql = "INSERT IGNORE INTO user_followers (user_id, follower_id) VALUES (:user_id, :follower_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':follower_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $message = 'Usuário seguido com sucesso';
        } else {
            // Remover relação de seguir
            $sql = "DELETE FROM user_followers WHERE user_id = :user_id AND follower_id = :follower_id";
            $stmt = $this->db->prepare($sql);
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
    }
}

/**
 * Enviar mensagem para o mural
 */
public function sendWallMessage() {
    header('Content-Type: application/json');
    
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        $target_user_id = intval($_POST['target_user_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $current_user_id = $_SESSION['user_id'];
        
        if ($target_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
            return;
        }
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Mensagem não pode estar vazia']);
            return;
        }
        
        if (strlen($message) > 500) {
            echo json_encode(['success' => false, 'message' => 'Mensagem muito longa (máximo 500 caracteres)']);
            return;
        }
        
        // Verificar se o usuário de destino existe e suas configurações
        $sql = "SELECT u.id, up.privacy_settings 
                FROM users u 
                LEFT JOIN user_profiles up ON u.id = up.user_id 
                WHERE u.id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            return;
        }
        
        // Verificar configurações de privacidade
        $privacy_settings = $target_user['privacy_settings'] ? json_decode($target_user['privacy_settings'], true) : [];
        $allow_messages = $privacy_settings['allow_messages'] ?? true;
        
        if (!$allow_messages) {
            echo json_encode(['success' => false, 'message' => 'Este usuário não permite mensagens no mural']);
            return;
        }
        
        // Verificar limite de spam (máximo 3 mensagens por minuto)
        $sql = "SELECT COUNT(*) FROM wall_messages 
                WHERE author_id = :author_id AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':author_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $recent_messages = $stmt->fetchColumn();
        
        if ($recent_messages >= 3) {
            echo json_encode(['success' => false, 'message' => 'Muitas mensagens enviadas. Aguarde um momento.']);
            return;
        }
        
        // Inserir mensagem
        $sql = "INSERT INTO wall_messages (user_id, author_id, content, created_at) 
                VALUES (:user_id, :author_id, :content, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->bindValue(':author_id', $current_user_id, PDO::PARAM_INT);
        $stmt->bindValue(':content', $message);
        $stmt->execute();
        
        // Buscar dados do autor para retornar
        $sql = "SELECT username, display_name, avatar FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Recado enviado com sucesso',
            'author_name' => $author['display_name'] ?: $author['username'],
            'author_avatar' => $author['avatar'] ? site_url($author['avatar']) : getDefaultAvatar()
        ]);
        
    } catch (PDOException $e) {
        error_log("Erro ao enviar mensagem no mural: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}

/**
 * Carregar mais tópicos do usuário via AJAX
 */
public function loadUserTopics() {
    header('Content-Type: application/json');
    
    try {
        $user_id = intval($_GET['user_id'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = 6;
        
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
            return;
        }
        
        // Verificar se o usuário existe
        $sql = "SELECT id, username, display_name, avatar FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            return;
        }
        
        // Buscar tópicos com paginação
        $topics = $this->getUserTopics($user_id, $page, $per_page);
        
        // Formatar dados dos tópicos para retorno JSON
        $formatted_topics = [];
        foreach ($topics as $topic) {
            $formatted_topics[] = [
                'id' => $topic['id'],
                'title' => $topic['title'],
                'url' => $topic['url'],
                'author_name' => $user['display_name'] ?: $user['username'],
                'author_avatar' => $user['avatar'] ? site_url($user['avatar']) : getDefaultAvatar(),
                'time_ago' => $topic['time_ago'],
                'replies_count' => $topic['replies_count'],
                'formatted_views' => $topic['formatted_views'],
                'is_pinned' => $topic['is_pinned'],
                'is_locked' => $topic['is_locked'],
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
    }
}

/**
 * Buscar estatísticas atualizadas do usuário
 */
public function getUserStatsAjax() {
    header('Content-Type: application/json');
    
    try {
        $user_id = intval($_GET['user_id'] ?? 0);
        
        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
            return;
        }
        
        // Buscar contadores de seguidores e seguindo
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM user_followers WHERE user_id = :user_id) as followers_count,
                    (SELECT COUNT(*) FROM user_followers WHERE follower_id = :user_id) as following_count";
        
        $stmt = $this->db->prepare($sql);
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
    }
}

/**
 * Atualização do método getUserProfile para incluir contadores de seguidores
 */
private function getUserProfileWithFollowers($user_id) {
    try {
        $sql = "SELECT u.*, up.*,
                       COALESCE(follower_stats.followers_count, 0) as followers_count,
                       COALESCE(following_stats.following_count, 0) as following_count
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                LEFT JOIN (
                    SELECT user_id, COUNT(*) as followers_count
                    FROM user_followers
                    GROUP BY user_id
                ) follower_stats ON u.id = follower_stats.user_id
                LEFT JOIN (
                    SELECT follower_id, COUNT(*) as following_count
                    FROM user_followers
                    GROUP BY follower_id
                ) following_stats ON u.id = following_stats.follower_id
                WHERE u.id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Formatar dados
            $user['member_since'] = format_date($user['registration_date'], 'm/Y');
            $user['last_activity'] = time_ago($user['last_login']);
            $user['avatar_url'] = $user['avatar'] ? site_url($user['avatar']) : getDefaultAvatar();
            $user['social_links'] = $user['social_links'] ? json_decode($user['social_links'], true) : [];
            $user['gaming_platforms'] = $user['gaming_platforms'] ? json_decode($user['gaming_platforms'], true) : [];
            $user['privacy_settings'] = $user['privacy_settings'] ? json_decode($user['privacy_settings'], true) : [];
            $user['notification_settings'] = $user['notification_settings'] ? json_decode($user['notification_settings'], true) : [];
        }
        
        return $user;
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar perfil do usuário: " . $e->getMessage());
        return null;
    }
}

/**
 * Atualização do método profile para usar o novo método com seguidores
 */
public function profileUpdated($username = null) {
    if (!$username && !is_logged_in()) {
        http_response_code(404);
        include 'views/pages/404.php';
        return;
    }

    // Determinar o user_id a ser exibido
    if ($username) {
        // Buscar user_id com base no username
        $user = $this->getUserByUsername($username);
        if (!$user) {
            set_flash_message('error', 'Usuário não encontrado.');
            http_response_code(404);
            include 'views/pages/404.php';
            return;
        }
        $user_id = $user['id'];
    } else {
        // Se não houver username, mostrar o perfil do usuário logado
        $user_id = $_SESSION['user_id'];
    }

    $user = $this->getUserProfileWithFollowers($user_id);
    if (!$user) {
        set_flash_message('error', 'Perfil não encontrado.');
        http_response_code(404);
        include 'views/pages/404.php';
        return;
    }

    // Verificar privacidade para perfis de terceiros
    if ($username && $user_id != ($_SESSION['user_id'] ?? 0)) {
        $privacy = $user['privacy_settings']['profile_visibility'] ?? 'public';
        if ($privacy === 'private') {
            set_flash_message('error', 'Este perfil é privado.');
            http_response_code(403);
            include 'views/pages/403.php';
            return;
        }
        if ($privacy === 'members' && !is_logged_in()) {
            set_flash_message('error', 'Você precisa estar logado para visualizar este perfil.');
            redirect(site_url('login'));
            return;
        }
    }

    $stats = $this->getUserStats($user_id);
    $recent_activity = $this->getRecentActivity($user_id);
    $achievements = $this->getUserAchievements($user_id);
    $topics = $this->getUserTopics($user_id, 1, 6);

    $data = [
        'user' => $user,
        'stats' => $stats,
        'recent_activity' => $recent_activity,
        'achievements' => $achievements,
        'topics' => $topics,
        'meta_title' => 'Perfil de ' . ($user['display_name'] ?? $user['username']) . ' - Leonida Brasil',
        'meta_description' => 'Perfil de ' . ($user['display_name'] ?? $user['username'])
    ];

    $this->loadView('profile', $data);
}

/**
 * Buscar mensagens do mural do usuário
 */
private function getWallMessages($user_id, $limit = 10) {
    try {
        $sql = "SELECT wm.*, u.username, u.display_name, u.avatar
                FROM wall_messages wm
                LEFT JOIN users u ON wm.author_id = u.id
                WHERE wm.user_id = :user_id AND wm.status = 'active'
                ORDER BY wm.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($messages as &$message) {
            $message['time_ago'] = time_ago($message['created_at']);
            $message['author_avatar'] = $message['avatar'] ? site_url($message['avatar']) : getDefaultAvatar();
            $message['author_display_name'] = $message['display_name'] ?: $message['username'];
        }
        
        return $messages;
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar mensagens do mural: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar se usuário está seguindo outro
 */
private function isFollowing($follower_id, $user_id) {
    try {
        $sql = "SELECT COUNT(*) FROM user_followers WHERE user_id = :user_id AND follower_id = :follower_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':follower_id', $follower_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
        
    } catch (PDOException $e) {
        error_log("Erro ao verificar seguidor: " . $e->getMessage());
        return false;
    }
}

/**
 * Buscar conquistas reais do usuário do banco
 */
private function getUserAchievementsFromDB($user_id) {
    try {
        $sql = "SELECT a.*, ua.earned_at
                FROM user_achievements ua
                JOIN achievements a ON ua.achievement_id = a.id
                WHERE ua.user_id = :user_id AND a.active = TRUE
                ORDER BY ua.earned_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($achievements as &$achievement) {
            $achievement['earned_date'] = time_ago($achievement['earned_at']);
        }
        
        return $achievements;
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar conquistas do usuário: " . $e->getMessage());
        return [];
    }
}

    /**
     * Buscar usuário por username
     */
    private function getUserByUsername($username) {
        try {
            $sql = "SELECT id, username, display_name FROM users WHERE username = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário por username: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Página de configurações do usuário
     */
    public function settings() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        // Processar formulário se for POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSettingsUpdate();
            return;
        }
        
        $user = $this->getUserProfile($_SESSION['user_id']);
        $preferences = $this->getUserPreferences($_SESSION['user_id']);
        
        $data = [
            'user' => $user,
            'preferences' => $preferences,
            'meta_title' => 'Configurações - Leonida Brasil',
            'meta_description' => 'Configurações da sua conta'
        ];
        
        $this->loadView('settings', $data);
    }
    
    /**
     * Página de favoritos do usuário
     */
    public function favorites() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? 'all'; // all, character, location, vehicle, mission, news
        
        $favorites = $this->getUserFavorites($_SESSION['user_id'], $page, 12, $type);
        
        $data = [
            'favorites' => $favorites,
            'current_page' => $page,
            'current_type' => $type,
            'meta_title' => 'Meus Favoritos - Leonida Brasil'
        ];
        
        $this->loadView('favorites', $data);
    }
    
    /**
     * Meus tópicos do fórum
     */
    public function myTopics() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $topics = $this->getUserTopics($_SESSION['user_id'], $page, 10);
        
        $data = [
            'topics' => $topics,
            'current_page' => $page,
            'meta_title' => 'Meus Tópicos - Leonida Brasil'
        ];
        
        $this->loadView('my-topics', $data);
    }
    
    /**
     * Mensagens do usuário
     */
    public function messages() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $messages = $this->getUserMessages($_SESSION['user_id'], $page, 15);
        
        $data = [
            'messages' => $messages,
            'current_page' => $page,
            'meta_title' => 'Mensagens - Leonida Brasil'
        ];
        
        $this->loadView('messages', $data);
    }
    
    /**
     * Notificações do usuário
     */
    public function notifications() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $notifications = $this->getUserNotifications($_SESSION['user_id']);
        
        // Marcar como lidas
        $this->markNotificationsAsRead($_SESSION['user_id']);
        
        $data = [
            'notifications' => $notifications,
            'meta_title' => 'Notificações - Leonida Brasil'
        ];
        
        $this->loadView('notifications', $data);
    }
    
    /**
     * Página de atividade do usuário
     */
    public function activity() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $activity = $this->getDetailedActivity($_SESSION['user_id'], $page, 20);
        
        $data = [
            'activity' => $activity,
            'current_page' => $page,
            'meta_title' => 'Minha Atividade - Leonida Brasil'
        ];
        
        $this->loadView('activity', $data);
    }
    
    /**
     * Página de conquistas do usuário
     */
    public function achievements() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        $achievements = $this->getAllAchievements($_SESSION['user_id']);
        $progress = $this->getAchievementProgress($_SESSION['user_id']);
        
        $data = [
            'achievements' => $achievements,
            'progress' => $progress,
            'meta_title' => 'Minhas Conquistas - Leonida Brasil'
        ];
        
        $this->loadView('achievements', $data);
    }
    
    /**
     * Página de login
     */
    public function login() {
        if (is_logged_in()) {
            redirect(site_url(''));
            return;
        }
        
        $data = [
            'meta_title' => 'Login - Leonida Brasil',
            'meta_description' => 'Faça login na sua conta Leonida Brasil'
        ];
        
        $this->loadView('login', $data);
    }
    
    /**
     * Página de registro
     */
    public function register() {
        if (is_logged_in()) {
            redirect(site_url(''));
            return;
        }
        
        $data = [
            'meta_title' => 'Criar Conta - Leonida Brasil',
            'meta_description' => 'Crie sua conta na comunidade Leonida Brasil'
        ];
        
        $this->loadView('register', $data);
    }
    
    /**
     * Logout do usuário
     */
    public function logout() {
        if (is_logged_in()) {
            // Limpar sessão
            $_SESSION = [];
            
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
            
            set_flash_message('success', 'Logout realizado com sucesso!');
        }
        
        redirect(site_url(''));
    }
    
    /**
     * Recuperação de senha
     */
    public function recover() {
        $data = [
            'meta_title' => 'Recuperar Senha - Leonida Brasil',
            'meta_description' => 'Recupere sua senha da conta Leonida Brasil'
        ];
        
        $this->loadView('recover', $data);
    }
    
    /**
     * Buscar dados completos do perfil do usuário
     */
    private function getUserProfile($user_id) {
        try {
            $sql = "SELECT u.*, up.*
                    FROM users u
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    WHERE u.id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Formatar dados
                $user['member_since'] = format_date($user['registration_date'], 'm/Y');
                $user['last_activity'] = time_ago($user['last_login']);
                $user['avatar_url'] = $user['avatar'] ?: getDefaultAvatar();
                $user['social_links'] = $user['social_links'] ? json_decode($user['social_links'], true) : [];
                $user['gaming_platforms'] = $user['gaming_platforms'] ? json_decode($user['gaming_platforms'], true) : [];
                $user['privacy_settings'] = $user['privacy_settings'] ? json_decode($user['privacy_settings'], true) : [];
                $user['notification_settings'] = $user['notification_settings'] ? json_decode($user['notification_settings'], true) : [];
            }
            
            return $user;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar perfil do usuário: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Estatísticas do usuário
     */
    private function getUserStats($user_id) {
        try {
            $sql = "SELECT 
                        u.experience_points,
                        u.level,
                        (SELECT COUNT(*) FROM forum_topics WHERE author_id = u.id) as topics_created,
                        (SELECT COUNT(*) FROM forum_replies WHERE author_id = u.id) as replies_posted,
                        (SELECT COUNT(*) FROM news WHERE author_id = u.id AND status = 'published') as news_written,
                        (SELECT COUNT(*) FROM comments WHERE author_id = u.id AND status = 'active') as comments_made,
                        (SELECT COUNT(*) FROM user_favorites WHERE user_id = u.id) as favorites_count
                    FROM users u
                    WHERE u.id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                // Calcular nível seguinte
                $stats['next_level_xp'] = ($stats['level'] * 1000) + 500;
                $stats['xp_progress'] = ($stats['experience_points'] % 1000) / 10; // Porcentagem para próximo nível
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'experience_points' => 0,
                'level' => 1,
                'topics_created' => 0,
                'replies_posted' => 0,
                'news_written' => 0,
                'comments_made' => 0,
                'favorites_count' => 0,
                'next_level_xp' => 1500,
                'xp_progress' => 0
            ];
        }
    }
    
    /**
     * Atividade recente do usuário
     */
    private function getRecentActivity($user_id) {
        try {
            $sql = "SELECT 'topic' as type, ft.title as content, ft.created_at, fc.name as category
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    WHERE ft.author_id = :user_id
                    
                    UNION ALL
                    
                    SELECT 'reply' as type, 
                           CONCAT('Respondeu em: ', ft.title) as content, 
                           fr.created_at,
                           fc.name as category
                    FROM forum_replies fr
                    JOIN forum_topics ft ON fr.topic_id = ft.id
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    WHERE fr.author_id = :user_id
                    
                    UNION ALL
                    
                    SELECT 'comment' as type,
                           CONCAT('Comentou em: ', n.title) as content,
                           c.created_at,
                           'Notícias' as category
                    FROM comments c
                    JOIN news n ON c.content_id = n.id
                    WHERE c.author_id = :user_id AND c.content_type = 'news'
                    
                    ORDER BY created_at DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activities as &$activity) {
                $activity['time_ago'] = time_ago($activity['created_at']);
                $activity['icon'] = $this->getActivityIcon($activity['type']);
            }
            
            return $activities;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar atividade recente: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conquistas do usuário
     */
    private function getUserAchievements($user_id) {
        try {
            // Por enquanto, gerar conquistas baseadas nas estatísticas
            $stats = $this->getUserStats($user_id);
            $achievements = [];
            
            // Conquistas baseadas em posts
            if ($stats['topics_created'] >= 1) {
                $achievements[] = [
                    'name' => 'Primeiro Tópico',
                    'description' => 'Criou seu primeiro tópico no fórum',
                    'icon' => 'fa fa-comment',
                    'earned_at' => 'Conquistado'
                ];
            }
            
            if ($stats['topics_created'] >= 10) {
                $achievements[] = [
                    'name' => 'Discussões Ativas',
                    'description' => 'Criou 10 tópicos no fórum',
                    'icon' => 'fa fa-comments',
                    'earned_at' => 'Conquistado'
                ];
            }
            
            // Conquistas baseadas em nível
            if ($stats['level'] >= 2) {
                $achievements[] = [
                    'name' => 'Membro Ativo',
                    'description' => 'Alcançou o nível 2',
                    'icon' => 'fa fa-star',
                    'earned_at' => 'Conquistado'
                ];
            }
            
            if ($stats['level'] >= 5) {
                $achievements[] = [
                    'name' => 'Veterano',
                    'description' => 'Alcançou o nível 5',
                    'icon' => 'fa fa-trophy',
                    'earned_at' => 'Conquistado'
                ];
            }
            
            // Conquistas baseadas em favoritos
            if ($stats['favorites_count'] >= 10) {
                $achievements[] = [
                    'name' => 'Colecionador',
                    'description' => 'Favoritou 10 itens',
                    'icon' => 'fa fa-heart',
                    'earned_at' => 'Conquistado'
                ];
            }
            
            return $achievements;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar conquistas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Preferências do usuário
     */
    private function getUserPreferences($user_id) {
        try {
            $sql = "SELECT privacy_settings, notification_settings 
                    FROM user_profiles 
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Padrões caso não existam
            $defaults = [
                'privacy_settings' => [
                    'profile_visibility' => 'public',
                    'show_email' => false,
                    'show_activity' => true,
                    'allow_messages' => true
                ],
                'notification_settings' => [
                    'email_replies' => true,
                    'email_mentions' => true,
                    'email_news' => false,
                    'browser_notifications' => true
                ]
            ];
            
            if ($prefs) {
                $prefs['privacy_settings'] = $prefs['privacy_settings'] ? 
                    json_decode($prefs['privacy_settings'], true) : $defaults['privacy_settings'];
                $prefs['notification_settings'] = $prefs['notification_settings'] ? 
                    json_decode($prefs['notification_settings'], true) : $defaults['notification_settings'];
            } else {
                $prefs = $defaults;
            }
            
            return $prefs;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar preferências: " . $e->getMessage());
            return [
                'privacy_settings' => [
                    'profile_visibility' => 'public',
                    'show_email' => false,
                    'show_activity' => true,
                    'allow_messages' => true
                ],
                'notification_settings' => [
                    'email_replies' => true,
                    'email_mentions' => true,
                    'email_news' => false,
                    'browser_notifications' => true
                ]
            ];
        }
    }
    
    /**
     * Favoritos do usuário
     */
    private function getUserFavorites($user_id, $page = 1, $per_page = 12, $type = 'all') {
        try {
            $offset = ($page - 1) * $per_page;
            
            $where_clause = $type !== 'all' ? "AND uf.item_type = :type" : "";
            
            $sql = "SELECT uf.item_type, uf.item_id, uf.created_at,
                           CASE 
                               WHEN uf.item_type = 'character' THEN c.name
                               WHEN uf.item_type = 'location' THEN l.name
                               WHEN uf.item_type = 'vehicle' THEN v.name
                               WHEN uf.item_type = 'mission' THEN m.title
                               WHEN uf.item_type = 'news' THEN n.title
                           END as title,
                           CASE 
                               WHEN uf.item_type = 'character' THEN c.slug
                               WHEN uf.item_type = 'location' THEN l.slug
                               WHEN uf.item_type = 'vehicle' THEN v.slug
                               WHEN uf.item_type = 'mission' THEN m.slug
                               WHEN uf.item_type = 'news' THEN n.slug
                           END as slug,
                           CASE 
                               WHEN uf.item_type = 'character' THEN c.image_main
                               WHEN uf.item_type = 'location' THEN l.image_main
                               WHEN uf.item_type = 'vehicle' THEN v.image_main
                               WHEN uf.item_type = 'mission' THEN m.image_main
                               WHEN uf.item_type = 'news' THEN n.featured_image
                           END as image
                    FROM user_favorites uf
                    LEFT JOIN characters c ON uf.item_type = 'character' AND uf.item_id = c.id
                    LEFT JOIN locations l ON uf.item_type = 'location' AND uf.item_id = l.id
                    LEFT JOIN vehicles v ON uf.item_type = 'vehicle' AND uf.item_id = v.id
                    LEFT JOIN missions m ON uf.item_type = 'mission' AND uf.item_id = m.id
                    LEFT JOIN news n ON uf.item_type = 'news' AND uf.item_id = n.id
                    WHERE uf.user_id = :user_id {$where_clause}
                    ORDER BY uf.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            if ($type !== 'all') {
                $stmt->bindValue(':type', $type);
            }
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($favorites as &$favorite) {
                $favorite['time_ago'] = time_ago($favorite['created_at']);
                $favorite['url'] = $this->getFavoriteUrl($favorite['item_type'], $favorite['slug']);
                $favorite['type_formatted'] = $this->formatFavoriteType($favorite['item_type']);
            }
            
            return $favorites;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar favoritos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar tópicos do usuário
     */
    private function getUserTopics($user_id, $page = 1, $per_page = 10) {
        try {
            $offset = ($page - 1) * $per_page;
            
            $sql = "SELECT ft.id, ft.title, ft.slug, ft.views, ft.replies_count, 
                           ft.status, ft.created_at, ft.last_reply_at,
                           fc.name as category_name, fc.icon as category_icon
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    WHERE ft.author_id = :user_id
                    ORDER BY ft.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($topics as &$topic) {
                $topic['time_ago'] = time_ago($topic['created_at']);
                $topic['url'] = site_url("forum/topico/{$topic['slug']}");
                $topic['is_pinned'] = ($topic['status'] === 'pinned');
                $topic['is_locked'] = ($topic['status'] === 'locked');
                $topic['formatted_views'] = number_format($topic['views']);
            }
            
            return $topics;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar tópicos do usuário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar mensagens do usuário
     */
    private function getUserMessages($user_id, $page = 1, $per_page = 15) {
        try {
            // Por enquanto, retornar array vazio já que não temos sistema de mensagens privadas implementado
            // Em produção, isso buscaria mensagens privadas entre usuários
            return [];
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar mensagens: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar notificações do usuário
     */
    private function getUserNotifications($user_id) {
        try {
            // Por enquanto, gerar notificações simuladas
            // Em produção, isso buscaria de uma tabela de notificações
            $notifications = [
                [
                    'id' => 1,
                    'type' => 'reply',
                    'title' => 'Nova resposta no seu tópico',
                    'message' => 'Alguém respondeu ao seu tópico "Teorias sobre Jason"',
                    'url' => site_url('forum/topico/teorias-sobre-jason'),
                    'read' => false,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                ],
                [
                    'id' => 2,
                    'type' => 'like',
                    'title' => 'Seu comentário foi curtido',
                    'message' => 'Seu comentário na notícia "Trailer 2" recebeu uma curtida',
                    'url' => site_url('noticia/trailer-2-gta-vi'),
                    'read' => false,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
                ],
                [
                    'id' => 3,
                    'type' => 'system',
                    'title' => 'Bem-vindo ao Leonida Brasil!',
                    'message' => 'Obrigado por se juntar à nossa comunidade',
                    'url' => site_url(''),
                    'read' => true,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
            
            foreach ($notifications as &$notification) {
                $notification['time_ago'] = time_ago($notification['created_at']);
                $notification['icon'] = $this->getNotificationIcon($notification['type']);
            }
            
            return $notifications;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar notificações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atividade detalhada com paginação
     */
    private function getDetailedActivity($user_id, $page = 1, $per_page = 20) {
        try {
            $offset = ($page - 1) * $per_page;
            
            $sql = "SELECT 'topic' as type, ft.title as content, ft.created_at, 
                           fc.name as category, ft.slug, ft.views, ft.replies_count
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    WHERE ft.author_id = :user_id
                    
                    UNION ALL
                    
                    SELECT 'reply' as type, 
                           CONCAT('Respondeu em: ', ft.title) as content, 
                           fr.created_at,
                           fc.name as category, ft.slug, 0 as views, 0 as replies_count
                    FROM forum_replies fr
                    JOIN forum_topics ft ON fr.topic_id = ft.id
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    WHERE fr.author_id = :user_id
                    
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($activities as &$activity) {
                $activity['time_ago'] = time_ago($activity['created_at']);
                $activity['formatted_date'] = format_date($activity['created_at'], 'd/m/Y H:i');
                $activity['icon'] = $this->getActivityIcon($activity['type']);
                $activity['url'] = $this->getActivityUrl($activity['type'], $activity['slug']);
            }
            
            return $activities;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar atividade detalhada: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Todas as conquistas disponíveis
     */
    private function getAllAchievements($user_id) {
        $stats = $this->getUserStats($user_id);
        $achievements = [];
        
        // Definir todas as conquistas possíveis
        $all_achievements = [
            [
                'id' => 'first_topic',
                'name' => 'Primeiro Tópico',
                'description' => 'Crie seu primeiro tópico no fórum',
                'icon' => 'fa fa-comment',
                'requirement' => 1,
                'current' => $stats['topics_created'],
                'type' => 'topics'
            ],
            [
                'id' => 'active_discusser',
                'name' => 'Discussões Ativas',
                'description' => 'Crie 10 tópicos no fórum',
                'icon' => 'fa fa-comments',
                'requirement' => 10,
                'current' => $stats['topics_created'],
                'type' => 'topics'
            ],
            [
                'id' => 'topic_master',
                'name' => 'Mestre das Discussões',
                'description' => 'Crie 50 tópicos no fórum',
                'icon' => 'fa fa-crown',
                'requirement' => 50,
                'current' => $stats['topics_created'],
                'type' => 'topics'
            ],
            [
                'id' => 'first_reply',
                'name' => 'Primeira Resposta',
                'description' => 'Responda a um tópico pela primeira vez',
                'icon' => 'fa fa-reply',
                'requirement' => 1,
                'current' => $stats['replies_posted'],
                'type' => 'replies'
            ],
            [
                'id' => 'helpful_member',
                'name' => 'Membro Prestativo',
                'description' => 'Poste 50 respostas no fórum',
                'icon' => 'fa fa-hands-helping',
                'requirement' => 50,
                'current' => $stats['replies_posted'],
                'type' => 'replies'
            ],
            [
                'id' => 'collector',
                'name' => 'Colecionador',
                'description' => 'Favorite 10 itens',
                'icon' => 'fa fa-heart',
                'requirement' => 10,
                'current' => $stats['favorites_count'],
                'type' => 'favorites'
            ],
            [
                'id' => 'level_2',
                'name' => 'Membro Ativo',
                'description' => 'Alcance o nível 2',
                'icon' => 'fa fa-star',
                'requirement' => 2,
                'current' => $stats['level'],
                'type' => 'level'
            ],
            [
                'id' => 'level_5',
                'name' => 'Veterano',
                'description' => 'Alcance o nível 5',
                'icon' => 'fa fa-trophy',
                'requirement' => 5,
                'current' => $stats['level'],
                'type' => 'level'
            ],
            [
                'id' => 'commentator',
                'name' => 'Comentarista',
                'description' => 'Faça 25 comentários em notícias',
                'icon' => 'fa fa-comment-dots',
                'requirement' => 25,
                'current' => $stats['comments_made'],
                'type' => 'comments'
            ]
        ];
        
        // Processar conquistas
        foreach ($all_achievements as $achievement) {
            $achievement['earned'] = $achievement['current'] >= $achievement['requirement'];
            $achievement['progress'] = min(100, ($achievement['current'] / $achievement['requirement']) * 100);
            
            if ($achievement['earned']) {
                $achievement['earned_date'] = 'Conquistado'; // Em produção, buscar data real
            }
            
            $achievements[] = $achievement;
        }
        
        return $achievements;
    }
    
    /**
     * Progresso geral das conquistas
     */
    private function getAchievementProgress($user_id) {
        $achievements = $this->getAllAchievements($user_id);
        $total = count($achievements);
        $earned = count(array_filter($achievements, function($a) { return $a['earned']; }));
        
        return [
            'total' => $total,
            'earned' => $earned,
            'percentage' => $total > 0 ? round(($earned / $total) * 100) : 0
        ];
    }
    
    /**
     * Processar atualização das configurações
     */
    private function handleSettingsUpdate() {
        try {
            $user_id = $_SESSION['user_id'];
            
            // Dados básicos do perfil
            $display_name = sanitize_input($_POST['display_name'] ?? '');
            $bio = sanitize_input($_POST['bio'] ?? '');
            $website = sanitize_input($_POST['website'] ?? '');
            $location = sanitize_input($_POST['location'] ?? '');
            
            // Configurações de privacidade
            $privacy_settings = [
                'profile_visibility' => $_POST['profile_visibility'] ?? 'public',
                'show_email' => isset($_POST['show_email']),
                'show_activity' => isset($_POST['show_activity']),
                'allow_messages' => isset($_POST['allow_messages'])
            ];
            
            // Configurações de notificação
            $notification_settings = [
                'email_replies' => isset($_POST['email_replies']),
                'email_mentions' => isset($_POST['email_mentions']),
                'email_news' => isset($_POST['email_news']),
                'browser_notifications' => isset($_POST['browser_notifications'])
            ];
            
            // Atualizar dados básicos
            $update_user_sql = "UPDATE users SET display_name = :display_name WHERE id = :user_id";
            $stmt = $this->db->prepare($update_user_sql);
            $stmt->bindValue(':display_name', $display_name);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Atualizar ou inserir perfil
            $profile_sql = "INSERT INTO user_profiles 
                           (user_id, website, location, bio, privacy_settings, notification_settings) 
                           VALUES (:user_id, :website, :location, :bio, :privacy_settings, :notification_settings)
                           ON DUPLICATE KEY UPDATE 
                           website = VALUES(website),
                           location = VALUES(location), 
                           bio = VALUES(bio),
                           privacy_settings = VALUES(privacy_settings),
                           notification_settings = VALUES(notification_settings)";
            
            $stmt = $this->db->prepare($profile_sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':website', $website);
            $stmt->bindValue(':location', $location);
            $stmt->bindValue(':bio', $bio);
            $stmt->bindValue(':privacy_settings', json_encode($privacy_settings));
            $stmt->bindValue(':notification_settings', json_encode($notification_settings));
            $stmt->execute();
            
            // Atualizar sessão
            $_SESSION['display_name'] = $display_name;
            
            set_flash_message('success', 'Configurações atualizadas com sucesso!');
            redirect(site_url('perfil/configuracao'));
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar configurações: " . $e->getMessage());
            set_flash_message('error', 'Erro ao salvar configurações. Tente novamente.');
            redirect(site_url('perfil/configuracao'));
        }
    }
    
    /**
     * Marcar notificações como lidas
     */
    private function markNotificationsAsRead($user_id) {
        try {
            // Em produção, atualizar tabela de notificações
            // UPDATE notifications SET read = 1 WHERE user_id = :user_id AND read = 0
            return true;
            
        } catch (PDOException $e) {
            error_log("Erro ao marcar notificações como lidas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ícone da atividade
     */
    private function getActivityIcon($type) {
        $icons = [
            'topic' => 'fa fa-comment',
            'reply' => 'fa fa-reply',
            'comment' => 'fa fa-message',
            'like' => 'fa fa-heart',
            'favorite' => 'fa fa-star'
        ];
        
        return $icons[$type] ?? 'fa fa-circle';
    }
    
    /**
     * URL do favorito baseado no tipo
     */
    private function getFavoriteUrl($type, $slug) {
        $urls = [
            'character' => "personagem/{$slug}",
            'location' => "local/{$slug}",
            'vehicle' => "veiculo/{$slug}",
            'mission' => "missao/{$slug}",
            'news' => "noticia/{$slug}"
        ];
        
        return site_url($urls[$type] ?? '');
    }
    
    /**
     * Formato do tipo de favorito
     */
    private function formatFavoriteType($type) {
        $types = [
            'character' => 'Personagem',
            'location' => 'Localização',
            'vehicle' => 'Veículo',
            'mission' => 'Missão',
            'news' => 'Notícia'
        ];
        
        return $types[$type] ?? ucfirst($type);
    }
    
    /**
     * URL da atividade baseada no tipo
     */
    private function getActivityUrl($type, $slug) {
        switch ($type) {
            case 'topic':
            case 'reply':
                return site_url("forum/topico/{$slug}");
            case 'favorite':
                return $slug ? site_url($slug) : '#';
            default:
                return '#';
        }
    }
    
    /**
     * Ícone da notificação baseado no tipo
     */
    private function getNotificationIcon($type) {
        $icons = [
            'reply' => 'fa fa-reply',
            'like' => 'fa fa-heart',
            'mention' => 'fa fa-at',
            'system' => 'fa fa-info-circle',
            'achievement' => 'fa fa-trophy'
        ];
        
        return $icons[$type] ?? 'fa fa-bell';
    }

    /**
 * Seguir/Deseguir usuário
 */
public function follow($target_user_id) {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        $current_user_id = $_SESSION['user_id'];
        $follow = $_POST['follow'] ?? false;

        if ($follow) {
            // Inserir relação de seguir
            $sql = "INSERT INTO user_followers (user_id, follower_id) VALUES (:user_id, :follower_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
            $stmt->bindValue(':follower_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Remover relação de seguir
            $sql = "DELETE FROM user_followers WHERE user_id = :user_id AND follower_id = :follower_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
            $stmt->bindValue(':follower_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
        }

        echo json_encode([
            'success' => true,
            'author_name' => $_SESSION['display_name'] ?? $_SESSION['username'],
            'author_avatar' => getDefaultAvatar()
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao seguir/deseguir usuário: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação']);
    }
}

/**
 * Carregar mais tópicos via AJAX
 */
public function topics($user_id) {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $topics = $this->getUserTopics($user_id, $page, 10);

        $response = [
            'success' => true,
            'topics' => array_map(function($topic) use ($user) {
                return [
                    'title' => $topic['title'],
                    'author_name' => $user['display_name'] ?? $user['username'],
                    'author_avatar' => $user['avatar_url'],
                    'time_ago' => $topic['time_ago'],
                    'replies_count' => $topic['replies_count'],
                    'formatted_views' => $topic['formatted_views']
                ];
            }, $topics)
        ];

        echo json_encode($response);
    } catch (PDOException $e) {
        error_log("Erro ao carregar tópicos: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao carregar tópicos']);
    }
}

/**
 * Enviar mensagem para o mural
 */
public function message($target_user_id) {
    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }

    try {
        $message = sanitize_input($_POST['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Mensagem vazia']);
            return;
        }

        // Verificar se o usuário permite mensagens
        $user = $this->getUserProfile($target_user_id);
        if (!($user['privacy_settings']['allow_messages'] ?? true)) {
            echo json_encode(['success' => false, 'message' => 'Este usuário não permite mensagens no mural']);
            return;
        }

        // Inserir mensagem no banco
        $sql = "INSERT INTO wall_messages (user_id, author_id, content, created_at) 
                VALUES (:user_id, :author_id, :content, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->bindValue(':author_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':content', $message);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'author_name' => $_SESSION['display_name'] ?? $_SESSION['username'],
            'author_avatar' => getDefaultAvatar()
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao enviar mensagem: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem']);
    }
}
    
    /**
     * Carregar view com layout
     */
    private function loadView($view, $data = []) {
        // Extrair dados para variáveis
        extract($data);
        
        // Incluir header
        include 'views/includes/header.php';
        
        // Incluir a view específica
        include "views/pages/{$view}.php";
        
        // Incluir footer
        include 'views/includes/footer.php';
    }
}
?>