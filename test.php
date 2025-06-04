<?php
// debug_auth.php - Arquivo para testar a autenticação
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG DE AUTENTICAÇÃO ===\n\n";

// 1. Testar conexão com banco
echo "1. Testando conexão com banco...\n";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "✅ Conexão com banco OK\n";
    } else {
        echo "❌ Falha na conexão com banco\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit;
}

// 2. Verificar se usuário admin existe
echo "\n2. Verificando usuário admin...\n";
$sql = "SELECT id, username, email, password_hash, status, email_verified FROM users WHERE username = 'admin' OR email = 'admin@leonidabrasil.com'";
$stmt = $db->prepare($sql);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ Usuário encontrado:\n";
    echo "   - ID: " . $user['id'] . "\n";
    echo "   - Username: " . $user['username'] . "\n";
    echo "   - Email: " . $user['email'] . "\n";
    echo "   - Status: " . $user['status'] . "\n";
    echo "   - Email verificado: " . ($user['email_verified'] ? 'Sim' : 'Não') . "\n";
    echo "   - Hash da senha: " . substr($user['password_hash'], 0, 30) . "...\n";
} else {
    echo "❌ Usuário admin não encontrado!\n";
    
    // Criar usuário admin se não existir
    echo "\n3. Criando usuário admin...\n";
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    
    $insert_sql = "INSERT INTO users (username, email, password_hash, display_name, level, experience_points, status, email_verified, registration_date) 
                   VALUES ('admin', 'admin@leonidabrasil.com', :password_hash, 'Administrador', 5, 5000, 'active', 1, NOW())";
    
    $insert_stmt = $db->prepare($insert_sql);
    $insert_stmt->bindValue(':password_hash', $password_hash);
    
    if ($insert_stmt->execute()) {
        echo "✅ Usuário admin criado com sucesso!\n";
        $user_id = $db->lastInsertId();
        echo "   - Novo ID: " . $user_id . "\n";
        
        // Buscar novamente para confirmar
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "❌ Erro ao criar usuário admin\n";
        exit;
    }
}

// 3. Testar verificação de senha
echo "\n3. Testando verificação de senha...\n";
$test_password = 'password';
$is_valid = password_verify($test_password, $user['password_hash']);

if ($is_valid) {
    echo "✅ Senha 'password' é válida para o hash!\n";
} else {
    echo "❌ Senha 'password' NÃO é válida para o hash!\n";
    
    // Gerar novo hash
    echo "   Gerando novo hash...\n";
    $new_hash = password_hash('password', PASSWORD_DEFAULT);
    echo "   Novo hash: " . $new_hash . "\n";
    
    // Atualizar no banco
    $update_sql = "UPDATE users SET password_hash = :new_hash WHERE id = :id";
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->bindValue(':new_hash', $new_hash);
    $update_stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
    
    if ($update_stmt->execute()) {
        echo "   ✅ Hash atualizado no banco!\n";
    } else {
        echo "   ❌ Erro ao atualizar hash\n";
    }
}

// 4. Testar helpers.php
echo "\n4. Testando helpers.php...\n";
try {
    require_once 'utils/helpers.php';
    echo "✅ helpers.php carregado\n";
    
    // Testar função sanitize_input se existir
    if (function_exists('sanitize_input')) {
        echo "✅ Função sanitize_input existe\n";
    } else {
        echo "⚠️  Função sanitize_input não existe\n";
    }
    
    // Testar função is_valid_email se existir
    if (function_exists('is_valid_email')) {
        echo "✅ Função is_valid_email existe\n";
    } else {
        echo "⚠️  Função is_valid_email não existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar helpers.php: " . $e->getMessage() . "\n";
}

// 5. Testar simulação de login via POST
echo "\n5. Simulando login...\n";

// Simular dados POST
$login_data = [
    'username' => 'admin@leonidabrasil.com',
    'password' => 'password'
];

echo "Dados de login:\n";
echo "   - Username: " . $login_data['username'] . "\n";
echo "   - Password: " . $login_data['password'] . "\n";

// Buscar usuário
$sql = "SELECT id, username, email, password_hash, display_name, level, 
               experience_points, status, email_verified
        FROM users 
        WHERE (username = :username OR email = :username) AND status = 'active'";

$stmt = $db->prepare($sql);
$stmt->bindValue(':username', $login_data['username']);
$stmt->execute();

$login_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($login_user) {
    echo "✅ Usuário encontrado para login\n";
    
    if (password_verify($login_data['password'], $login_user['password_hash'])) {
        echo "✅ Senha verificada com sucesso!\n";
        
        if ($login_user['email_verified']) {
            echo "✅ Email verificado - Login seria SUCESSFUL!\n";
            
            // Simular criação de sessão
            session_start();
            $_SESSION['user_id'] = $login_user['id'];
            $_SESSION['username'] = $login_user['username'];
            $_SESSION['display_name'] = $login_user['display_name'];
            $_SESSION['user_level'] = $login_user['level'];
            $_SESSION['email'] = $login_user['email'];
            
            echo "✅ Sessão criada:\n";
            echo "   - User ID: " . $_SESSION['user_id'] . "\n";
            echo "   - Username: " . $_SESSION['username'] . "\n";
            echo "   - Level: " . $_SESSION['user_level'] . "\n";
            
        } else {
            echo "❌ Email não verificado\n";
        }
    } else {
        echo "❌ Senha incorreta\n";
    }
} else {
    echo "❌ Usuário não encontrado para login\n";
}

echo "\n=== FIM DO DEBUG ===\n";

// 6. Criar função auxiliar para helpers.php se não existir
if (!function_exists('sanitize_input')) {
    echo "\n6. Criando funções auxiliares faltantes...\n";
    
    $helpers_content = "<?php
// utils/helpers.php - Funções auxiliares

/**
 * Sanitizar input do usuário
 */
function sanitize_input(\$input) {
    return htmlspecialchars(trim(\$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function is_valid_email(\$email) {
    return filter_var(\$email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verificar se usuário está logado
 */
function is_logged_in() {
    return isset(\$_SESSION['user_id']) && !empty(\$_SESSION['user_id']);
}

/**
 * Obter dados do usuário atual
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => \$_SESSION['user_id'],
        'username' => \$_SESSION['username'],
        'display_name' => \$_SESSION['display_name'],
        'email' => \$_SESSION['email'],
        'level' => \$_SESSION['user_level']
    ];
}

/**
 * Calcular tempo decorrido
 */
function time_ago(\$datetime) {
    \$time = time() - strtotime(\$datetime);
    
    if (\$time < 60) return 'agora mesmo';
    if (\$time < 3600) return floor(\$time/60) . ' min atrás';
    if (\$time < 86400) return floor(\$time/3600) . 'h atrás';
    if (\$time < 2592000) return floor(\$time/86400) . 'd atrás';
    if (\$time < 31536000) return floor(\$time/2592000) . ' meses atrás';
    
    return floor(\$time/31536000) . ' anos atrás';
}

/**
 * Formatar número
 */
function format_number(\$number) {
    return number_format(\$number);
}

/**
 * Gerar slug
 */
function generate_slug(\$string) {
    \$slug = strtolower(\$string);
    \$slug = preg_replace('/[^a-z0-9-]/', '-', \$slug);
    \$slug = preg_replace('/-+/', '-', \$slug);
    \$slug = trim(\$slug, '-');
    return \$slug;
}
?>";
    
    if (file_put_contents('utils/helpers.php', $helpers_content)) {
        echo "✅ Arquivo helpers.php criado com sucesso!\n";
    } else {
        echo "❌ Erro ao criar helpers.php\n";
    }
}
?>