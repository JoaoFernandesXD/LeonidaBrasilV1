<?php
// api/auth_debug.php - API de autenticação com debug melhorado
error_reporting(E_ALL);
ini_set('display_errors', 1); // Ativar para debug

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função para log de debug
function debug_log($message, $data = null) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data
    ];
    error_log("AUTH DEBUG: " . json_encode($log_entry));
    return $log_entry;
}

// Função de resposta com debug
function returnResponse($success, $message, $data = null, $debug = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => $debug // Remover em produção
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    if (!$success) {
        http_response_code(401);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Incluir dependências
try {
    if (!file_exists('../config/database.php')) {
        throw new Exception('database.php não encontrado');
    }
    require_once '../config/database.php';
    
    if (!file_exists('../utils/helpers.php')) {
        // Criar helpers.php básico se não existir
        $helpers_dir = dirname(__FILE__) . '/../utils';
        if (!is_dir($helpers_dir)) {
            mkdir($helpers_dir, 0755, true);
        }
        
        $helpers_content = "<?php
function sanitize_input(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

function is_valid_email(\$email) {
    return filter_var(\$email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_logged_in() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => \$_SESSION['user_id'],
        'username' => \$_SESSION['username'],
        'display_name' => \$_SESSION['display_name'],
        'email' => \$_SESSION['email'],
        'level' => \$_SESSION['user_level']
    ];
}
?>";
        file_put_contents('../utils/helpers.php', $helpers_content);
    }
    require_once '../utils/helpers.php';
    
} catch (Exception $e) {
    returnResponse(false, 'Erro ao carregar dependências: ' . $e->getMessage());
}

session_start();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    debug_log("Método recebido", $method);
    
    if ($method === 'POST') {
        handleAuth();
    } elseif ($method === 'GET') {
        handleGetAuthStatus();
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    debug_log("Erro geral", $e->getMessage());
    returnResponse(false, 'Erro interno: ' . $e->getMessage());
}

/**
 * Processar autenticação
 */
function handleAuth() {
    $debug = [];
    
    try {
        // Capturar input
        $input_raw = file_get_contents('php://input');
        debug_log("Input bruto recebido", $input_raw);
        $debug['input_raw'] = $input_raw;
        
        $input = json_decode($input_raw, true);
        debug_log("Input decodificado", $input);
        $debug['input_decoded'] = $input;
        
        if (!$input) {
            throw new Exception('JSON inválido ou vazio');
        }
        
        $action = $input['action'] ?? 'login';
        debug_log("Ação solicitada", $action);
        $debug['action'] = $action;
        
        switch ($action) {
            case 'login':
                handleLogin($input, $debug);
                break;
            case 'logout':
                handleLogout($debug);
                break;
            case 'register':
                handleRegister($input, $debug);
                break;
            default:
                throw new Exception('Ação não reconhecida: ' . $action);
        }
        
    } catch (Exception $e) {
        debug_log("Erro em handleAuth", $e->getMessage());
        returnResponse(false, $e->getMessage(), null, $debug);
    }
}

/**
 * Processar login
 */
function handleLogin($input, &$debug) {
    try {
        // Validar dados obrigatórios
        if (empty($input['username'])) {
            throw new Exception('Campo username/email é obrigatório');
        }
        
        if (empty($input['password'])) {
            throw new Exception('Campo password é obrigatório');
        }
        
        $debug['username_provided'] = $input['username'];
        $debug['password_length'] = strlen($input['password']);
        
        // Conectar ao banco
        $db = Database::getInstance()->getConnection();
        if (!$db) {
            throw new Exception('Falha na conexão com banco de dados');
        }
        
        debug_log("Conectado ao banco com sucesso");
        $debug['database_connected'] = true;
        
        // Buscar usuário
        $sql = "SELECT id, username, email, password_hash, display_name, level, 
                       experience_points, status, email_verified
                FROM users 
                WHERE (username = :username OR email = :email)";
        
        $stmt = $db->prepare($sql);
        $username_clean = sanitize_input($input['username']);
        $stmt->bindValue(':username', $username_clean);
        $stmt->bindValue(':email', $username_clean);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        debug_log("Query executada", [
            'sql' => $sql,
            'username' => $input['username'],
            'user_found' => !!$user
        ]);
        
        if (!$user) {
            $debug['user_found'] = false;
            
            // Verificar se existe algum usuário no banco
            $count_sql = "SELECT COUNT(*) as total FROM users";
            $count_stmt = $db->query($count_sql);
            $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $debug['total_users_in_db'] = $total_users;
            
            if ($total_users == 0) {
                throw new Exception('Nenhum usuário encontrado no banco de dados. Execute a importação inicial.');
            }
            
            throw new Exception('Usuário não encontrado');
        }
        
        $debug['user_found'] = true;
        $debug['user_data'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $user['status'],
            'email_verified' => $user['email_verified'],
            'level' => $user['level']
        ];
        
        // Verificar status
        if ($user['status'] !== 'active') {
            throw new Exception('Conta inativa: ' . $user['status']);
        }
        
        // Verificar senha
        $password_valid = password_verify($input['password'], $user['password_hash']);
        debug_log("Verificação de senha", [
            'password_provided' => $input['password'],
            'hash_from_db' => substr($user['password_hash'], 0, 30) . '...',
            'password_valid' => $password_valid
        ]);
        
        $debug['password_verification'] = [
            'provided_password' => $input['password'],
            'stored_hash' => substr($user['password_hash'], 0, 30) . '...',
            'verification_result' => $password_valid
        ];
        
        if (!$password_valid) {
            // Tentar verificar se é o hash padrão
            $default_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            if ($user['password_hash'] === $default_hash) {
                $debug['using_default_hash'] = true;
                $default_valid = password_verify('password', $default_hash);
                $debug['default_hash_test'] = $default_valid;
                
                if ($input['password'] === 'password' && $default_valid) {
                    $password_valid = true;
                    $debug['password_verification']['override'] = 'Usando hash padrão conhecido';
                }
            }
            
            if (!$password_valid) {
                sleep(1); // Delay contra força bruta
                throw new Exception('Senha incorreta');
            }
        }
        
        // Verificar email verificado
        if (!$user['email_verified']) {
            $debug['email_not_verified'] = true;
            throw new Exception('Conta não verificada. Verifique seu email.');
        }
        
        // Iniciar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['user_level'] = $user['level'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        debug_log("Sessão criada", $_SESSION);
        $debug['session_created'] = $_SESSION;
        
        // Atualizar último login
        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
        $update_result = $update_stmt->execute();
        
        $debug['last_login_updated'] = $update_result;
        
        // Dados seguros para retorno
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'email' => $user['email'],
            'level' => $user['level'],
            'experience_points' => $user['experience_points']
        ];
        
        debug_log("Login bem-sucedido", $userData);
        
        returnResponse(true, 'Login realizado com sucesso!', $userData, $debug);
        
    } catch (Exception $e) {
        debug_log("Erro no login", $e->getMessage());
        returnResponse(false, $e->getMessage(), null, $debug);
    }
}

/**
 * Processar logout
 */
function handleLogout(&$debug) {
    try {
        $debug['session_before_logout'] = $_SESSION;
        
        // Limpar sessão
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        debug_log("Logout realizado");
        $debug['logout_completed'] = true;
        
        returnResponse(true, 'Logout realizado com sucesso!', null, $debug);
        
    } catch (Exception $e) {
        debug_log("Erro no logout", $e->getMessage());
        returnResponse(false, 'Erro ao fazer logout: ' . $e->getMessage(), null, $debug);
    }
}

/**
 * Verificar status da autenticação
 */
function handleGetAuthStatus() {
    $debug = [];
    
    try {
        $debug['session_data'] = $_SESSION;
        $debug['session_id'] = session_id();
        
        if (is_logged_in()) {
            $userData = current_user();
            debug_log("Status: logado", $userData);
            
            returnResponse(true, 'Usuário autenticado', [
                'authenticated' => true,
                'user' => $userData
            ], $debug);
        } else {
            debug_log("Status: não logado");
            
            returnResponse(true, 'Usuário não autenticado', [
                'authenticated' => false,
                'user' => null
            ], $debug);
        }
        
    } catch (Exception $e) {
        debug_log("Erro ao verificar status", $e->getMessage());
        returnResponse(false, $e->getMessage(), null, $debug);
    }
}

/**
 * Registro de usuário (simplificado para debug)
 */
function handleRegister($input, &$debug) {
    // Implementação básica apenas para completude
    returnResponse(false, 'Registro não implementado nesta versão de debug');
}
?>