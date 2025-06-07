<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leonida Brasil - Portal GTA VI</title>
    <link rel="stylesheet" href="<?= site_url() ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <meta name="description" content="Portal oficial da comunidade brasileira de GTA VI. Notícias, teorias, mapas e tudo sobre o universo de Leonida.">
    <meta name="keywords" content="GTA VI, GTA 6, Leonida, Vice City, Jason, Lucia, Rockstar Games">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="header-bg">
            <div class="floating-elements">
                <div class="float-item"></div>
                <div class="float-item"></div>
                <div class="float-item"></div>
                <div class="float-item"></div>
            </div>
        </div>
        
        <div class="header-content">
            <div class="logo-section">
                <img src="https://www.leonidabrasil.com.br/wp-content/uploads/2025/05/LOGO-5-1.png" alt="Leonida Brasil" class="main-logo">
            </div>
            
            <div class="player-widget">
                <div class="player-buttons">
                    <a href="#" class="btn-player radio">
                        <div class="btn-icon">🎵</div>
                        <span>Vice City FM</span>
                    </a>
                    <a href="#" class="btn-player forum">
                        <div class="btn-icon">💬</div>
                        <span>Fórum</span>
                    </a>
                    <a href="#" class="btn-player hub">
                        <div class="btn-icon">🎮</div>
                        <span>HUB Leonida</span>
                    </a>
                    <a href="#" class="btn-player gallery">
                        <div class="btn-icon">🖼️</div>
                        <span>Galeria</span>
                    </a>
                </div>
                
                <div class="listener-count">
                    <div class="count">1,247</div>
                    <div class="label">ouvintes</div>
                </div>
                
                <div class="dj-info">
                    <div class="dj-avatar">
                        <div class="microphone"></div>
                    </div>
                    <div class="dj-details">
                        <div class="dj-name">DJ Vice</div>
                        <div class="controls">
                            <div class="like-btn active">♥</div>
                            <div class="volume-control">
                                <span>VOLUME</span>
                                <div class="volume-slider"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <?php include_once('./views/includes/navigation.php'); ?>