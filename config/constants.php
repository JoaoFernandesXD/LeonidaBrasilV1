<?php
// config/constants.php
// Constantes globais do sistema Leonida Brasil

// URLs e caminhos
define('SITE_URL', 'https://staging.leonidabrasil.com.br'); // Ajuste conforme seu setup
define('SITE_NAME', 'Leonida Brasil');
define('SITE_DESCRIPTION', 'Portal dedicado ao universo de GTA VI');

// Caminhos de diretórios
define('UPLOAD_PATH', 'assets/uploads/');
define('IMAGES_PATH', 'assets/images/');
define('CSS_PATH', 'assets/css/');
define('JS_PATH', 'assets/js/');

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'ogg']);

// Configurações de paginação
define('ITEMS_PER_PAGE', 12);
define('NEWS_PER_PAGE', 6);
define('FORUM_TOPICS_PER_PAGE', 15);

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1 hora

// Níveis de usuário
define('USER_GUEST', 0);
define('USER_MEMBER', 1);
define('USER_VERIFIED', 2);
define('USER_MODERATOR', 3);
define('USER_ADMIN', 4);
define('USER_SUPER', 5);

// Status de conteúdo
define('STATUS_DRAFT', 'draft');
define('STATUS_PUBLISHED', 'published');
define('STATUS_ARCHIVED', 'archived');

// Tipos de conteúdo
define('CONTENT_CHARACTER', 'character');
define('CONTENT_LOCATION', 'location');
define('CONTENT_VEHICLE', 'vehicle');
define('CONTENT_MISSION', 'mission');
define('CONTENT_NEWS', 'news');

// Configurações de SEO
define('DEFAULT_META_TITLE', 'Leonida Brasil - Portal GTA VI');
define('DEFAULT_META_DESCRIPTION', 'Tudo sobre GTA VI: personagens, localizações, veículos, missões, notícias e comunidade ativa de fãs.');
define('DEFAULT_META_KEYWORDS', 'GTA VI, GTA 6, Leonida, Vice City, Jason, Lucia, Rockstar Games');

// Configurações de segurança
define('SESSION_TIMEOUT', 7200); // 2 horas
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Mensagens do sistema
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');
?>