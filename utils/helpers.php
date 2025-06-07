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


/**
 * Buscar artigos populares 
 */
function getPopularArticles($limit = 5) {
    try {
        // Obter conexão com banco corretamente
        $db = Database::getInstance()->getConnection();
        
        if (!$db) {
            error_log("Database connection failed in getPopularArticles");
            return getFallbackPopularArticles($limit);
        }
        
        $sql = "SELECT n.id, n.slug, n.title, n.views, n.created_at,
                       (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id AND status = 'active') as comments_count
                FROM news n
                WHERE n.status = 'published' 
                ORDER BY n.views DESC 
                LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar dados
        foreach ($articles as &$article) {
            $article['formatted_views'] = formatNumber($article['views']);
            $article['time_ago'] = time_ago($article['created_at']);
        }
        
        return $articles;
        
    } catch (Exception $e) {
        error_log("Error in getPopularArticles: " . $e->getMessage());
        return getFallbackPopularArticles($limit);
    }
}

/**
 * Artigos populares de fallback quando há erro no banco
 */
function getFallbackPopularArticles($limit = 5) {
    $fallbackArticles = [
        [
            'id' => 1,
            'slug' => 'data-lancamento-gta-vi-confirmada',
            'title' => 'Data de Lançamento de GTA VI Confirmada?',
            'views' => 8200,
            'formatted_views' => '8.2k',
            'comments_count' => 234,
            'time_ago' => 'há 2 dias'
        ],
        [
            'id' => 2,
            'slug' => 'mapa-completo-vice-city-vazado',
            'title' => 'Mapa Completo de Vice City Vazado',
            'views' => 6700,
            'formatted_views' => '6.7k',
            'comments_count' => 189,
            'time_ago' => 'há 1 semana'
        ],
        [
            'id' => 3,
            'slug' => 'todos-veiculos-confirmados-gta-vi',
            'title' => 'Todos os Veículos Confirmados em GTA VI',
            'views' => 5900,
            'formatted_views' => '5.9k',
            'comments_count' => 156,
            'time_ago' => 'há 3 dias'
        ],
        [
            'id' => 4,
            'slug' => 'sistema-monetario-como-funcionara-dinheiro',
            'title' => 'Sistema Monetário: Como Funcionará o Dinheiro',
            'views' => 4800,
            'formatted_views' => '4.8k',
            'comments_count' => 98,
            'time_ago' => 'há 5 dias'
        ],
        [
            'id' => 5,
            'slug' => 'comparacao-gta-v-vs-gta-vi',
            'title' => 'Comparação: GTA V vs GTA VI',
            'views' => 4200,
            'formatted_views' => '4.2k',
            'comments_count' => 87,
            'time_ago' => 'há 1 semana'
        ]
    ];
    
    return array_slice($fallbackArticles, 0, $limit);
}

/**
 * Verificar se usuário está online (função simplificada)
 */
function isUserOnline($user_id) {
    try {
        $db = Database::getInstance()->getConnection();
        
        if (!$db) {
            return false;
        }
        
        // Verificar se usuário foi ativo nos últimos 15 minutos
        $sql = "SELECT id FROM users WHERE id = :user_id AND last_login >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Error checking user online status: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter nível do usuário
 */
function getUserLevel($user_id) {
    try {
        $db = Database::getInstance()->getConnection();
        
        if (!$db) {
            return 1;
        }
        
        $sql = "SELECT level FROM users WHERE id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? intval($result['level']) : 1;
        
    } catch (Exception $e) {
        error_log("Error getting user level: " . $e->getMessage());
        return 1;
    }
}

/**
 * Verificar se comentário foi curtido pelo usuário
 */
function isCommentLiked($comment_id) {
    if (!is_logged_in()) {
        return false;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        if (!$db) {
            return false;
        }
        
        // Verificar se existe tabela comment_likes
        $checkTable = "SHOW TABLES LIKE 'comment_likes'";
        $tableExists = $db->query($checkTable)->fetch();
        
        if (!$tableExists) {
            // Se a tabela não existe, retornar false
            return false;
        }
        
        $sql = "SELECT id FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Error checking comment like: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatar números grandes (1000 -> 1k, 1000000 -> 1M)
 */
function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'k';
    }
    return number_format($number);
}

/**
 * Obter nome formatado da categoria
 */
function formatCategoryName($category) {
    $categories = [
        'trailers' => 'Trailers',
        'analysis' => 'Análises',
        'theories' => 'Teorias',
        'maps' => 'Mapas',
        'characters' => 'Personagens',
        'gameplay' => 'Gameplay'
    ];
    
    return $categories[$category] ?? ucfirst($category);
}

/**
 * Obter ícone da categoria
 */
function getCategoryIcon($category) {
    $icons = [
        'trailers' => 'video',
        'analysis' => 'chart-line',
        'theories' => 'lightbulb',
        'maps' => 'map',
        'characters' => 'users',
        'gameplay' => 'gamepad'
    ];
    
    return $icons[$category] ?? 'newspaper';
}

/**
 * Obter role do usuário baseado no nível
 */
function getUserRole($level) {
    $roles = [
        5 => 'Super Admin',
        4 => 'Administrador',
        3 => 'Moderador',
        2 => 'Editor Verificado',
        1 => 'Membro',
        0 => 'Visitante'
    ];
    
    return $roles[$level] ?? 'Visitante';
}


/**
 * Gerar badges do usuário baseado no level e quantidade de mensagens
 */
function getUserBadges($level, $message_count = 0) {
    $badges = [];
    
    // Badge de nível administrativo
    if ($level >= 5) {
        $badges[] = [
            'name' => 'Diretor Geral',
            'icon' => 'fa fa-crown',
            'class' => 'director-badge',
            'level' => null
        ];
    } elseif ($level >= 4) {
        $badges[] = [
            'name' => 'Administrador',
            'icon' => 'fa fa-shield-alt',
            'class' => 'admin-badge',
            'level' => null
        ];
    } elseif ($level >= 3) {
        $badges[] = [
            'name' => 'Moderador',
            'icon' => 'fa fa-shield',
            'class' => 'moderator-badge',
            'level' => null
        ];
    }
    
    // Badge por quantidade de mensagens
    if ($message_count >= 500) {
        $badges[] = [
            'name' => 'Diferente',
            'icon' => 'fa fa-star',
            'class' => 'differente-badge',
            'level' => 'Nv.6'
        ];
    } elseif ($message_count >= 100) {
        $badges[] = [
            'name' => 'Simpático',
            'icon' => 'fa fa-thumbs-up',
            'class' => 'simpatico-badge',
            'level' => 'Nv.4'
        ];
    } elseif ($message_count >= 10) {
        $badges[] = [
            'name' => 'Ativo',
            'icon' => 'fa fa-comment',
            'class' => 'active-badge',
            'level' => 'Nv.2'
        ];
    }
    
    return $badges;
}

/**
 * Determinar título do usuário baseado no level
 */
function getUserTitle($level) {
    switch ($level) {
        case 5: return 'Diretor Geral';
        case 4: return 'Administrador';
        case 3: return 'Moderador';
        case 2: return 'Membro Verificado';
        case 1: return 'Membro';
        default: return 'Visitante';
    }
}

/**
 * Verificar se usuário é verificado
 */
function isUserVerified($level) {
    return ($level >= 2);
}

/**
 * Status do usuário (simulado - em produção usar last_activity)
 */
function getUserStatus($user_id = null) {
    // Simulação simples - em produção verificar last_activity
    return rand(0, 1) ? 'online' : 'offline';
}

/**
 * Formatar tempo de última atividade
 */
function getLastActivityText($status) {
    if ($status === 'online') {
        return 'Online';
    } else {
        // Simular tempo offline
        $hours = rand(1, 48);
        if ($hours < 24) {
            return "Offline há {$hours}h";
        } else {
            $days = floor($hours / 24);
            return "Offline há {$days} dia" . ($days > 1 ? 's' : '');
        }
    }
}

/**
 * Gerar URL do avatar padrão se não existir
 */
function getDefaultAvatar() {
    return 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
}

/**
 * Sanitizar conteúdo BBCode básico (para preview)
 */
function parseBBCodeBasic($text) {
    $text = htmlspecialchars($text);
    
    // BBCode básico
    $text = preg_replace('/\[b\](.*?)\[\/b\]/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\[i\](.*?)\[\/i\]/s', '<em>$1</em>', $text);
    $text = preg_replace('/\[u\](.*?)\[\/u\]/s', '<u>$1</u>', $text);
    $text = preg_replace('/\[s\](.*?)\[\/s\]/s', '<s>$1</s>', $text);
    
    // Links
    $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/s', '<a href="$1" target="_blank">$2</a>', $text);
    
    // Quebras de linha
    $text = nl2br($text);
    
    return $text;
}



/**
 * Contar total de mensagens do usuário no fórum
 */
function getUserMessageCount($user_id, $db = null) {
    if (!$db) {
        try {
            $db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    try {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM forum_topics WHERE author_id = :user_id) +
                    (SELECT COUNT(*) FROM forum_replies WHERE author_id = :user_id AND status = 'active') 
                    as total_messages";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($result['total_messages']);
    } catch (Exception $e) {
        return 0;
    }
}

?>
