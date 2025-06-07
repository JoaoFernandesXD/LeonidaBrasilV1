<?php
// index.php
// Router principal do Leonida Brasil - Versão atualizada

// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Autoload das classes
spl_autoload_register(function ($class_name) {
    $directories = [
        'models/',
        'controllers/',
        'utils/',
        'config/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Incluir arquivos de configuração
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'utils/helpers.php';

// Capturar a página solicitada
$page = $_GET['page'] ?? 'home';
$slug = $_GET['slug'] ?? null;
$action = $_GET['action'] ?? 'index';

// Roteamento principal
try {
    switch ($page) {
        case 'home':
        case '':
            $controller = new HomeController();
            $controller->index();
            break;
            
        case 'hub':
            $controller = new HubController();
            $controller->index();
            break;
            
        case 'character':
        case 'personagem':
            $controller = new HubController();
            if ($slug) {
                $controller->character($slug);
            } else {
                $controller->characters();
            }
            break;
            
        case 'location':
        case 'local':
            $controller = new HubController();
            if ($slug) {
                $controller->location($slug);
            } else {
                $controller->locations();
            }
            break;
            
        case 'vehicle':
        case 'veiculo':
            $controller = new HubController();
            if ($slug) {
                $controller->vehicle($slug);
            } else {
                $controller->vehicles();
            }
            break;
            
        case 'mission':
        case 'missao':
            $controller = new HubController();
            if ($slug) {
                $controller->mission($slug);
            } else {
                $controller->missions();
            }
            break;
            
        case 'news':
        case 'noticias':
            $controller = new NewsController();
            if ($slug) {
                $controller->single($slug);  // ← Página individual
            } else {
                $controller->index();        // ← Lista de notícias
            }
            break;
            
        case 'gallery':
        case 'galeria':
            $controller = new GalleryController();
            $controller->index();
            break;
            
        case 'forum':
            $controller = new ForumController();
            $action = $_GET['action'] ?? 'index';
            $slug = $_GET['slug'] ?? null;
            
            switch ($action) {
                case 'topic':
                    if ($slug) {
                        $controller->topic($slug);
                    } else {
                        $controller->index();
                    }
                    break;
                    
                case 'category':
                    if ($slug) {
                        $controller->category($slug);
                    } else {
                        $controller->index();
                    }
                    break;
                    
                case 'create':
                    $controller->createTopic();
                    break;
                    
                case 'index':
                default:
                    $controller->index();
                    break;
            }
            break;
            
        case 'radio':
            $controller = new RadioController();
            $controller->index();
            break;
            
        // ========= ÁREA DO USUÁRIO =========
        case 'profile':
            case 'perfil':
                $controller = new UserController();
                $action = $_GET['action'] ?? 'index';
                
                switch ($action) {
                    case 'upload-avatar':
                        $controller->uploadAvatar();
                        break;
                    case 'configuracao':
                    case 'configuracoes':
                    case 'settings':
                        $controller->settings();
                        break;
                    case 'index':
                    default:
                        $controller->profile($_GET['user'] ?? null);
                        break;
                }
                break;
            
        // ========= AUTENTICAÇÃO =========
        case 'login':
            $controller = new UserController();
            $controller->login();
            break;
            
        case 'register':
        case 'registro':
            $controller = new UserController();
            $controller->register();
            break;
            
        case 'logout':
            $controller = new UserController();
            $controller->logout();
            break;
            
        case 'recover':
        case 'recuperar':
            $controller = new UserController();
            $controller->recover();
            break;
            
        // ========= PÁGINAS INSTITUCIONAIS =========
        case 'sobre':
        case 'about':
            $controller = new PageController();
            $controller->about();
            break;
            
        case 'contato':
        case 'contact':
            $controller = new PageController();
            $controller->contact();
            break;
            
        case 'termos':
        case 'terms':
            $controller = new PageController();
            $controller->terms();
            break;
            
        case 'privacidade':
        case 'privacy':
            $controller = new PageController();
            $controller->privacy();
            break;
            
        // ========= BUSCA =========
        case 'search':
        case 'busca':
            $controller = new SearchController();
            $controller->index();
            break;
            
        // ========= ADMINISTRAÇÃO =========
        case 'admin':
            if (!has_permission(4)) {
                redirect(site_url('login'));
                break;
            }
            
            $controller = new AdminController();
            $admin_action = $_GET['admin_action'] ?? 'dashboard';
            
            switch ($admin_action) {
                case 'users':
                    $controller->users();
                    break;
                case 'content':
                    $controller->content();
                    break;
                case 'forum':
                    $controller->forum();
                    break;
                case 'settings':
                    $controller->settings();
                    break;
                case 'dashboard':
                default:
                    $controller->dashboard();
                    break;
            }
            break;
            
        default:
            // Verificar se é uma página estática
            if (file_exists("views/pages/{$page}.php")) {
                $controller = new PageController();
                $controller->staticPage($page);
            } else {
                // Página 404
                http_response_code(404);
                include 'views/pages/404.php';
            }
            break;
    }
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro no roteamento: " . $e->getMessage());
    
    // Página de erro 500
    http_response_code(500);
    include 'views/pages/error.php';
}
?>