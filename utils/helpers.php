<?php
// utils/helpers.php
// Funções auxiliares globais

/**
 * Sanitizar entrada de dados
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Gerar slug amigável para URLs
 */
function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[áàâãä]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòôõö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Formatar data para exibição
 */
function format_date($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Calcular tempo relativo (ex: "há 2 horas")
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time/60) . ' min atrás';
    if ($time < 86400) return floor($time/3600) . 'h atrás';
    if ($time < 2592000) return floor($time/86400) . 'd atrás';
    if ($time < 31536000) return floor($time/2592000) . ' meses atrás';
    
    return floor($time/31536000) . ' anos atrás';
}

/**
 * Truncar texto com reticências
 */
function truncate_text($text, $length = 150) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Gerar URL completa
 */
function site_url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Incluir CSS
 */
function css_url($file) {
    return site_url(CSS_PATH . $file);
}

/**
 * Incluir JS
 */
function js_url($file) {
    return site_url(JS_PATH . $file);
}

/**
 * URL de imagem
 */
function img_url($file) {
    return site_url(IMAGES_PATH . $file);
}

/**
 * URL de upload
 */
function upload_url($file) {
    return site_url(UPLOAD_PATH . $file);
}

/**
 * Verificar se usuário está logado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obter dados do usuário logado
 */
function current_user() {
    if (!is_logged_in()) return null;
    
    // Aqui você pode buscar dados completos do usuário se necessário
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'display_name' => $_SESSION['display_name'] ?? '',
        'level' => $_SESSION['user_level'] ?? 1
    ];
}

/**
 * Verificar permissão do usuário
 */
function has_permission($required_level) {
    if (!is_logged_in()) return false;
    return ($_SESSION['user_level'] ?? 0) >= $required_level;
}

/**
 * Redirecionar para URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently");
    }
    header("Location: " . $url);
    exit();
}

/**
 * Definir mensagem flash
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obter e limpar mensagem flash
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Validar email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gerar token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>