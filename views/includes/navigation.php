<nav class="main-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/inicio"><i class="fa fa-home"></i>Início</a>
                    <div class="dropdown">
                        <a href="<?php echo site_url(''); ?>noticias"><i class="fa fa-newspaper"></i>Últimas Notícias</a>
                        <a href="<?php echo site_url(''); ?>noticias/populares"><i class="fa fa-fire"></i>Populares</a>
                        <a href="<?php echo site_url(''); ?>noticias/recentes"><i class="fa fa-clock"></i>Recentes</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="/noticias"><i class="fa fa-newspaper"></i>Notícias</a>
                    <div class="dropdown">
                        <a href="<?php echo site_url(''); ?>noticias/trailers"><i class="fa fa-video"></i>Trailers</a>
                        <a href="<?php echo site_url(''); ?>noticias/teorias"><i class="fa fa-lightbulb"></i>Teorias</a>
                        <a href="<?php echo site_url(''); ?>/noticias/analises"><i class="fa fa-chart-line"></i>Análises</a>
                        <a href="<?php echo site_url(''); ?>noticias/lancamentos"><i class="fa fa-calendar"></i>Lançamentos</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="/forum"><i class="fa fa-comments"></i>Fórum</a>
                    <div class="dropdown">
                        <a href="<?php echo site_url(''); ?>forum/categoria/gerais"><i class="fa fa-comment"></i>Discussões Gerais</a>
                        <a href="<?php echo site_url(''); ?>forum/categoria/teorias"><i class="fa fa-question"></i>Teorias & Especulações</a>
                        <a href="<?php echo site_url(''); ?>forum/categoria/mapas"><i class="fa fa-map"></i>Mapas & Localizações</a>
                        <a href="<?php echo site_url(''); ?>forum/categoria/gameplay"><i class="fa fa-gamepad"></i>Gameplay</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="/hub"><i class="fa fa-database"></i>HUB Leonida</a>
                    <div class="dropdown">
                        <a href="<?php echo site_url(''); ?>hub/personagens"><i class="fa fa-users"></i>Personagens</a>
                        <a href="<?php echo site_url(''); ?>hub/localizacoes"><i class="fa fa-map-marker"></i>Localizações</a>
                        <a href="<?php echo site_url(''); ?>hub/veiculos"><i class="fa fa-car"></i>Veículos</a>
                        <a href="<?php echo site_url(''); ?>hub/missoes"><i class="fa fa-tasks"></i>Missões</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="/galeria"><i class="fa fa-images"></i>Galeria</a>
                    <div class="dropdown">
                        <a href="#"><i class="fa fa-image"></i>Screenshots</a>
                        <a href="#"><i class="fa fa-video"></i>Vídeos</a>
                        <a href="#"><i class="fa fa-palette"></i>Fan Art</a>
                        <a href="#"><i class="fa fa-mobile"></i>Wallpapers</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#"><i class="fa fa-radio"></i>Rádio</a>
                    <div class="dropdown">
                        <a href="#"><i class="fa fa-play"></i>Vice City FM</a>
                        <a href="#"><i class="fa fa-music"></i>Playlist</a>
                        <a href="#"><i class="fa fa-podcast"></i>Podcast</a>
                        <a href="#"><i class="fa fa-history"></i>Histórico</a>
                    </div>
                </li>
            </ul>
            
            <div class="search-box">
                <input type="text" placeholder="Buscar..." class="search-input">
                <button type="button" class="search-btn">
                    <i class="fa fa-search"></i>
                </button>
            </div>
        </div>
    </nav>