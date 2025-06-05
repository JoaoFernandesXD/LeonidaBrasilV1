<?php
// api/comments.php 
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Buffer de saída
ob_start();

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função de log
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] COMMENTS: $message";
    if ($data) {
        $log .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($log);
}

// Função de resposta de erro
function sendError($message, $code = 400, $details = null) {
    ob_clean();
    http_response_code($code);
    logDebug("ERROR $code", ['message' => $message, 'details' => $details]);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Função de resposta de sucesso
function sendSuccess($data, $message = null, $meta = null) {
    ob_clean();
    http_response_code(200);
    logDebug("SUCCESS", ['message' => $message, 'data_type' => gettype($data)]);
    
    $response = [
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($message) $response['message'] = $message;
    if ($meta) $response['meta'] = $meta;
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

// Funções auxiliares
function cleanInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . 'h atrás';
    if ($time < 2592000) return floor($time/86400) . 'd atrás';
    return floor($time/2592000) . ' meses atrás';
}

function canUserComment($pdo, $user_id) {
    try {
        // Verificar último comentário do usuário (anti-flood)
        $sql = "SELECT created_at FROM comments 
                WHERE author_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $lastComment = $stmt->fetch();
        
        if ($lastComment) {
            $lastCommentTime = strtotime($lastComment['created_at']);
            $currentTime = time();
            $timeDiff = $currentTime - $lastCommentTime;
            
            // 1 minuto = 60 segundos
            if ($timeDiff < 60) {
                $remainingTime = 60 - $timeDiff;
                return [
                    'can_comment' => false,
                    'remaining_time' => $remainingTime,
                    'message' => "Aguarde {$remainingTime} segundos para comentar novamente"
                ];
            }
        }
        
        return ['can_comment' => true];
        
    } catch (Exception $e) {
        logDebug("Error checking user comment permission", $e->getMessage());
        return ['can_comment' => true]; // Em caso de erro, permitir comentar
    }
}

try {
    logDebug("=== API Comments Started ===", [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
    
    // Conectar ao banco usando arquivo de configuração
    try {
        if (file_exists('../config/database.php')) {
            require_once '../config/database.php';
            $pdo = Database::getInstance()->getConnection();
            logDebug("Database loaded from config file");
        } else {
            // Fallback para conexão direta
            $host = 'localhost';
            $dbname = 'leonidab_staging';
            $username = 'leonidab_staging';
            $password = 'JHsf200699@';
            
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            logDebug("Database connected directly");
        }
        
        if (!$pdo) {
            throw new Exception('Falha na conexão com banco de dados');
        }
        
    } catch (Exception $e) {
        logDebug("Database connection failed", $e->getMessage());
        sendError("Erro de conexão com banco de dados");
    }
    
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    logDebug("Session info", [
        'user_logged' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    // Roteamento
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;
    
    // Roteamento por ação especial
    if ($action) {
        switch ($action) {
            case 'like':
                handleLikeComment($pdo);
                break;
            case 'report':
                handleReportComment($pdo);
                break;
            case 'delete':
                handleDeleteComment($pdo);
                break;
            default:
                sendError('Ação não reconhecida', 400);
        }
    }
    
    // Roteamento por método HTTP
    switch ($method) {
        case 'GET':
            handleGetComments($pdo);
            break;
        case 'POST':
            handleCreateComment($pdo);
            break;
        case 'PUT':
            handleUpdateComment($pdo);
            break;
        default:
            sendError("Método não permitido", 405);
    }
    
} catch (Exception $e) {
    logDebug("FATAL ERROR", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    sendError("Erro interno do servidor");
}

/**
 * Buscar comentários
 */
function handleGetComments($pdo) {
    try {
        logDebug("Getting comments");
        
        $content_type = cleanInput($_GET['type'] ?? 'news');
        $content_id = intval($_GET['id'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(50, max(1, intval($_GET['per_page'] ?? 10)));
        $sort = cleanInput($_GET['sort'] ?? 'recent');
        
        if ($content_id <= 0) {
            sendError("ID do conteúdo é obrigatório");
        }
        
        logDebug("Get params", compact('content_type', 'content_id', 'page', 'per_page', 'sort'));
        
        $offset = ($page - 1) * $per_page;
        
        // Definir ordenação
        $order_clause = 'c.created_at DESC';
        switch ($sort) {
            case 'popular':
                $order_clause = 'c.likes DESC, c.created_at DESC';
                break;
            case 'oldest':
                $order_clause = 'c.created_at ASC';
                break;
        }
        
        // Query principal
        $sql = "SELECT 
                    c.id,
                    c.content,
                    c.likes,
                    c.created_at,
                    c.author_id,
                    u.username,
                    u.display_name,
                    u.avatar,
                    u.level
                FROM comments c
                JOIN users u ON c.author_id = u.id
                WHERE c.content_type = :content_type 
                AND c.content_id = :content_id 
                AND c.status = 'active'
                AND c.parent_comment_id IS NULL
                ORDER BY {$order_clause}
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':content_type', $content_type);
        $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $comments = $stmt->fetchAll();
        
        // Processar comentários
        $current_user_id = $_SESSION['user_id'] ?? null;
        
        foreach ($comments as &$comment) {
            $comment['time_ago'] = timeAgo($comment['created_at']);
            $comment['author_name'] = $comment['display_name'] ?: $comment['username'];
            $comment['can_edit'] = $current_user_id && ($current_user_id == $comment['author_id'] || ($_SESSION['user_level'] ?? 0) >= 3);
            $comment['is_author'] = $current_user_id && $current_user_id == $comment['author_id'];
            $comment['is_liked'] = false; // TODO: Implementar verificação de like
            
            if (empty($comment['avatar'])) {
                $comment['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
            }
            
            // Remover dados sensíveis
            unset($comment['author_id']);
        }
        
        // Contar total
        $count_sql = "SELECT COUNT(*) as total FROM comments 
                      WHERE content_type = :content_type 
                      AND content_id = :content_id 
                      AND status = 'active'
                      AND parent_comment_id IS NULL";
        
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->bindValue(':content_type', $content_type);
        $count_stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
        $count_stmt->execute();
        $total = intval($count_stmt->fetch()['total']);
        
        $meta = [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'has_next' => $page < ceil($total / $per_page),
            'has_prev' => $page > 1
        ];
        
        logDebug("Comments retrieved", ['count' => count($comments), 'total' => $total]);
        sendSuccess($comments, null, $meta);
        
    } catch (Exception $e) {
        logDebug("Error getting comments", $e->getMessage());
        sendError("Erro ao buscar comentários");
    }
}

/**
 * Criar comentário com anti-flood
 */
function handleCreateComment($pdo) {
    try {
        logDebug("Creating comment");
        
        // Verificar autenticação
        if (!isset($_SESSION['user_id'])) {
            sendError("Login necessário para comentar", 403);
        }
        
        $user_id = intval($_SESSION['user_id']);
        
        // Verificar anti-flood
        $floodCheck = canUserComment($pdo, $user_id);
        if (!$floodCheck['can_comment']) {
            sendError($floodCheck['message'], 429); // Too Many Requests
        }
        
        // Capturar input
        $input_raw = file_get_contents('php://input');
        if (empty($input_raw)) {
            sendError("Dados não recebidos");
        }
        
        $input = json_decode($input_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError("JSON inválido");
        }
        
        logDebug("Input received", $input);
        
        // Validar campos
        if (empty($input['content'])) {
            sendError("Conteúdo é obrigatório");
        }
        
        if (empty($input['content_type'])) {
            sendError("Tipo de conteúdo é obrigatório");
        }
        
        if (empty($input['content_id'])) {
            sendError("ID do conteúdo é obrigatório");
        }
        
        $content_type = cleanInput($input['content_type']);
        $content_id = intval($input['content_id']);
        $content = trim($input['content']);
        $parent_comment_id = !empty($input['parent_comment_id']) ? intval($input['parent_comment_id']) : null;
        
        // Validações
        if (strlen($content) > 1000) {
            sendError("Comentário muito longo (máximo 1000 caracteres)");
        }
        
        if (!in_array($content_type, ['news', 'gallery', 'character', 'location', 'vehicle', 'mission'])) {
            sendError("Tipo de conteúdo inválido");
        }
        
        if ($content_id <= 0) {
            sendError("ID de conteúdo inválido");
        }
        
        // Verificar se conteúdo existe
        $table_map = [
            'news' => 'news',
            'gallery' => 'gallery_items',
            'character' => 'characters',
            'location' => 'locations',
            'vehicle' => 'vehicles',
            'mission' => 'missions'
        ];
        
        $table = $table_map[$content_type];
        $check_sql = "SELECT 1 FROM {$table} WHERE id = :id LIMIT 1";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindValue(':id', $content_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            sendError("Conteúdo não encontrado", 404);
        }
        
        logDebug("Creating comment", [
            'user_id' => $user_id,
            'content_type' => $content_type,
            'content_id' => $content_id,
            'content_length' => strlen($content)
        ]);
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        try {
            // Inserir comentário
            $insert_sql = "INSERT INTO comments (content_type, content_id, author_id, content, parent_comment_id, status, created_at) 
                           VALUES (:content_type, :content_id, :author_id, :content, :parent_comment_id, 'active', NOW())";
            
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->bindValue(':content_type', $content_type);
            $insert_stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(':author_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(':content', $content);
            $insert_stmt->bindValue(':parent_comment_id', $parent_comment_id, PDO::PARAM_INT);
            
            $result = $insert_stmt->execute();
            
            if (!$result) {
                throw new Exception('Falha na inserção');
            }
            
            $comment_id = $pdo->lastInsertId();
            
            // Dar XP ao usuário (opcional)
            try {
                $xp_sql = "UPDATE users SET experience_points = experience_points + 5 WHERE id = :user_id";
                $xp_stmt = $pdo->prepare($xp_sql);
                $xp_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $xp_stmt->execute();
            } catch (Exception $e) {
                logDebug("Failed to award XP", $e->getMessage());
            }
            
            // Commit
            $pdo->commit();
            
            // Buscar comentário criado
            $select_sql = "SELECT 
                               c.id,
                               c.content,
                               c.likes,
                               c.created_at,
                               u.username,
                               u.display_name,
                               u.avatar,
                               u.level
                           FROM comments c
                           JOIN users u ON c.author_id = u.id
                           WHERE c.id = :id";
            
            $select_stmt = $pdo->prepare($select_sql);
            $select_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
            $select_stmt->execute();
            
            $new_comment = $select_stmt->fetch();
            
            if (!$new_comment) {
                sendError("Comentário criado mas não foi possível recuperar");
            }
            
            // Processar comentário
            $new_comment['time_ago'] = timeAgo($new_comment['created_at']);
            $new_comment['author_name'] = $new_comment['display_name'] ?: $new_comment['username'];
            $new_comment['can_edit'] = true;
            $new_comment['is_author'] = true;
            $new_comment['is_liked'] = false;
            
            if (empty($new_comment['avatar'])) {
                $new_comment['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
            }
            
            logDebug("Comment created successfully", ['comment_id' => $comment_id]);
            sendSuccess($new_comment, "Comentário adicionado com sucesso!");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        logDebug("Error creating comment", $e->getMessage());
        sendError("Erro ao criar comentário");
    }
}

/**
 * Curtir comentário
 */
function handleLikeComment($pdo) {
    try {
        logDebug("Liking comment");
        
        if (!isset($_SESSION['user_id'])) {
            sendError("Login necessário", 403);
        }
        
        $comment_id = intval($_GET['id'] ?? 0);
        if ($comment_id <= 0) {
            sendError("ID do comentário é obrigatório");
        }
        
        $user_id = intval($_SESSION['user_id']);
        
        // Verificar se comentário existe
        $check_sql = "SELECT id, likes FROM comments WHERE id = :id AND status = 'active'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $comment = $check_stmt->fetch();
        if (!$comment) {
            sendError("Comentário não encontrado", 404);
        }
        
        // Verificar se já curtiu (usar user_favorites ou criar tabela comment_likes)
        $like_check_sql = "SELECT id FROM user_favorites 
                           WHERE user_id = :user_id 
                           AND item_type = 'comment' 
                           AND item_id = :comment_id";
        
        $like_check_stmt = $pdo->prepare($like_check_sql);
        $like_check_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $like_check_stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $like_check_stmt->execute();
        
        $already_liked = $like_check_stmt->fetch();
        
        $pdo->beginTransaction();
        
        try {
            if ($already_liked) {
                // Remover curtida
                $delete_sql = "DELETE FROM user_favorites 
                               WHERE user_id = :user_id 
                               AND item_type = 'comment' 
                               AND item_id = :comment_id";
                
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $delete_stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
                $delete_stmt->execute();
                
                // Decrementar contador
                $update_sql = "UPDATE comments SET likes = GREATEST(0, likes - 1) WHERE id = :id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
                $update_stmt->execute();
                
                $liked = false;
                $message = "Curtida removida";
                $new_count = max(0, $comment['likes'] - 1);
                
            } else {
                // Adicionar curtida
                $insert_sql = "INSERT INTO user_favorites (user_id, item_type, item_id, created_at) 
                               VALUES (:user_id, 'comment', :comment_id, NOW())";
                
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $insert_stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
                $insert_stmt->execute();
                
                // Incrementar contador
                $update_sql = "UPDATE comments SET likes = likes + 1 WHERE id = :id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
                $update_stmt->execute();
                
                $liked = true;
                $message = "Comentário curtido!";
                $new_count = $comment['likes'] + 1;
            }
            
            $pdo->commit();
            
            logDebug("Comment like toggled", [
                'comment_id' => $comment_id,
                'user_id' => $user_id,
                'liked' => $liked,
                'new_count' => $new_count
            ]);
            
            sendSuccess([
                'liked' => $liked,
                'likes_count' => $new_count
            ], $message);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        logDebug("Error liking comment", $e->getMessage());
        sendError("Erro ao curtir comentário");
    }
}

/**
 * Reportar comentário
 */
function handleReportComment($pdo) {
    try {
        if (!isset($_SESSION['user_id'])) {
            sendError("Login necessário", 403);
        }
        
        $comment_id = intval($_GET['id'] ?? 0);
        if ($comment_id <= 0) {
            sendError("ID do comentário é obrigatório");
        }
        
        $user_id = intval($_SESSION['user_id']);
        
        // Verificar se comentário existe
        $check_sql = "SELECT id FROM comments WHERE id = :id AND status = 'active'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            sendError("Comentário não encontrado", 404);
        }
        
        // TODO: Implementar sistema de reports se necessário
        // Por enquanto apenas log
        logDebug("Comment reported", [
            'comment_id' => $comment_id,
            'reported_by' => $user_id
        ]);
        
        sendSuccess([], "Comentário reportado com sucesso. Nossa equipe irá analisar.");
        
    } catch (Exception $e) {
        logDebug("Error reporting comment", $e->getMessage());
        sendError("Erro ao reportar comentário");
    }
}

/**
 * Deletar comentário
 */
function handleDeleteComment($pdo) {
    try {
        if (!isset($_SESSION['user_id'])) {
            sendError("Login necessário", 403);
        }
        
        $comment_id = intval($_GET['id'] ?? 0);
        if ($comment_id <= 0) {
            sendError("ID do comentário é obrigatório");
        }
        
        $user_id = intval($_SESSION['user_id']);
        $user_level = intval($_SESSION['user_level'] ?? 0);
        
        // Verificar permissão
        $check_sql = "SELECT author_id FROM comments WHERE id = :id AND status = 'active'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $comment = $check_stmt->fetch();
        if (!$comment) {
            sendError("Comentário não encontrado", 404);
        }
        
        // Verificar se é o autor ou moderador
        if ($comment['author_id'] != $user_id && $user_level < 3) {
            sendError("Sem permissão para deletar este comentário", 403);
        }
        
        // Soft delete
        $delete_sql = "UPDATE comments SET status = 'deleted', updated_at = NOW() WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $delete_stmt->execute();
        
        logDebug("Comment deleted", [
            'comment_id' => $comment_id,
            'deleted_by' => $user_id
        ]);
        
        sendSuccess([], "Comentário removido com sucesso");
        
    } catch (Exception $e) {
        logDebug("Error deleting comment", $e->getMessage());
        sendError("Erro ao deletar comentário");
    }
}

/**
 * Atualizar comentário
 */
function handleUpdateComment($pdo) {
    try {
        if (!isset($_SESSION['user_id'])) {
            sendError("Login necessário", 403);
        }
        
        $comment_id = intval($_GET['id'] ?? 0);
        if ($comment_id <= 0) {
            sendError("ID do comentário é obrigatório");
        }
        
        $user_id = intval($_SESSION['user_id']);
        $user_level = intval($_SESSION['user_level'] ?? 0);
        
        // Verificar permissão
        $check_sql = "SELECT author_id FROM comments WHERE id = :id AND status = 'active'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $comment = $check_stmt->fetch();
        if (!$comment) {
            sendError("Comentário não encontrado", 404);
        }
        
        if ($comment['author_id'] != $user_id && $user_level < 3) {
            sendError("Sem permissão para editar este comentário", 403);
        }
        
        // Capturar input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['content'])) {
            sendError("Conteúdo é obrigatório");
        }
        
        $content = trim($input['content']);
        if (strlen($content) > 1000) {
            sendError("Comentário muito longo (máximo 1000 caracteres)");
        }
        
        // Atualizar
        $update_sql = "UPDATE comments SET content = :content, updated_at = NOW() WHERE id = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindValue(':content', $content);
        $update_stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $update_stmt->execute();
        
        logDebug("Comment updated", [
            'comment_id' => $comment_id,
            'updated_by' => $user_id
        ]);
        
        sendSuccess([], "Comentário atualizado com sucesso");
        
    } catch (Exception $e) {
        logDebug("Error updating comment", $e->getMessage());
        sendError("Erro ao atualizar comentário");
    }
}

// Limpar buffer
ob_end_clean();
?>