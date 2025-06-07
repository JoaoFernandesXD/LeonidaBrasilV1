<!-- Página inicial dinâmica do Leonida Brasil -->
    <!-- Main Content -->
<main class="main-container">
    <div class="content-wrapper">
<!-- Left Sidebar -->
<aside class="sidebar-left">
    <!-- Login Box -->
    <div class="widget login-widget">
        <?php if (!is_logged_in()): ?>
            <div class="widget-header">
                <i class="fa fa-user"></i>
                Entre na sua conta
                <a href="#" class="btn btn-register">REGISTRE-SE</a>
            </div>
            <div class="widget-content">
                <form class="login-form">
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <div class="input-label">USUÁRIO</div>
                        <input type="text" placeholder="Seu usuário" required>
                    </div>
                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fa fa-lock"></i>
                        </div>
                        <div class="input-label">SENHA</div>
                        <input type="password" placeholder="Sua senha" required>
                    </div>
                    <div class="form-options">
                        <a href="#" class="forgot-password">
                            <i class="fa fa-key"></i>
                            Esqueceu sua senha? Recuperar aqui!
                        </a>
                        <label class="remember-me">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            Manter-me conectado
                        </label>
                    </div>
                    <button type="submit" class="btn btn-login">ENTRAR</button>
                </form>
            </div>
        <?php else: ?>
            <div class="widget-header">
                <i class="fa fa-user"></i>
                Olá, <?= htmlspecialchars($current_user['display_name'] ?: $current_user['username']) ?>!
                <a href="#" class="btn btn-register btn-logout">SAIR</a>
            </div>
            <div class="widget-content">
                <div class="user-info">
                    <div class="user-stats">
                        <div class="stat">
                            <div class="stat-number"><?= $site_stats['total_users'] ?? 127 ?></div>
                            <div class="stat-label">Membros</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?= rand(10, 100) ?></div>
                            <div class="stat-label">Curtidas</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?= rand(5, 50) ?></div>
                            <div class="stat-label">Tópicos</div>
                        </div>
                    </div>
                    <a href="<?= site_url('perfil') ?>" class="btn btn-profile">Meu Perfil</a>
                    <a href="<?= site_url('forum/criar-topico') ?>" class="btn btn-profile">Postar Tópico</a>
                    <a href="<?= site_url('configuracoes') ?>" class="btn btn-profile">Configurações</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Featured User -->
    <div class="widget featured-user">
        <div class="widget-header blue">
            <i class="fa fa-star"></i>
            Usuário em Destaque
        </div>
        <div class="widget-content">
            <?php if (!empty($featured_user)): ?>
                <div class="user-avatar">
                    <div class="avatar-image" style="background-image: url(<?= htmlspecialchars($featured_user['avatar']) ?>); object-fit: cover; background-size: cover; background-position: center;"></div>
                </div>
                <div class="user-name"><?= htmlspecialchars($featured_user['display_name'] ?: $featured_user['username']) ?></div>
                <div class="user-title"><?= htmlspecialchars($featured_user['title'] ?? 'Membro Destacado') ?></div>
                <a href="<?= site_url('perfil/' . $featured_user['username']) ?>" class="btn btn-profile">VER PERFIL</a>
            <?php else: ?>
                <div class="user-avatar">
                    <div class="avatar-image" style="background-image: url(https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg); object-fit: cover; background-size: cover; background-position: center;"></div>
                </div>
                <div class="user-name">Lisamixart</div>
                <div class="user-title">GTA VI Mud Girl Artwork</div>
                <a href="#" class="btn btn-profile">VER PERFIL</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Community Ranking -->
    <div class="widget ranking">
        <div class="widget-header blue">
            <i class="fa fa-trophy"></i>
            Ranking da Comunidade
            <a href="<?= site_url('ranking') ?>" class="btn btn-small">Geral</a>
        </div>
        <div class="widget-content">
            <div class="rank-list">
                <?php if (!empty($community_ranking)): ?>
                    <?php foreach ($community_ranking as $user): ?>
                        <div class="rank-item">
                            <div class="rank-position"><?= $user['position'] ?>º</div>
                            <div class="rank-avatar" style="background-image: url(<?= htmlspecialchars($user['avatar']) ?>); object-fit: cover; background-size: cover; background-position: center;"></div>
                            <div class="rank-color <?= $user['medal_class'] ?>"></div>
                            <div class="rank-user"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></div>
                            <div class="rank-points"><?= number_format($user['points']) ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback ranking -->
                    <div class="rank-item">
                        <div class="rank-position">1º</div>
                        <div class="rank-avatar" style="background-image: url(https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg); object-fit: cover; background-size: cover; background-position: center;"></div>
                        <div class="rank-color gold"></div>
                        <div class="rank-user">VicePlayer2026</div>
                        <div class="rank-points">1,247 pts</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Badges -->
    <div class="widget badges">
        <div class="widget-header pink">
            <i class="fa fa-medal"></i>
            Emblemas Recentes
        </div>
        <div class="widget-content">
            <div class="badge-grid">
                <?php if (!empty($recent_badges)): ?>
                    <?php foreach (array_slice($recent_badges, 0, 8) as $badge): ?>
                        <div class="badge-item" title="<?= htmlspecialchars($badge['badge_name']) ?> - <?= htmlspecialchars($badge['username']) ?>">
                            <img src="<?= htmlspecialchars($badge['badge_icon']) ?>" style="max-width: 30px; max-height: 30px;">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback badges -->
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="badge-item" title="Primeiro Post">
                            <img src="https://www.gtavice.net/content/images/official-gta-vi-logo.png" style="max-width: 30px; max-height: 30px;">
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<section class="main-content">
    <!-- News Carousel -->
    <div class="news-carousel">
        <div class="carousel-slides">
            <?php if (!empty($carousel_slides)): ?>
                <?php foreach ($carousel_slides as $index => $slide): ?>
                    <div class="slide <?= $index === 0 ? 'active' : '' ?>" style="background-image: url(<?= htmlspecialchars($slide['featured_image']) ?>); object-fit: cover; background-size: cover; background-position: center;">
                        <div class="slide-content">
                            <h3><?= htmlspecialchars($slide['title']) ?></h3>
                            <p><?= htmlspecialchars($slide['subtitle'] ?: $slide['excerpt']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback slides -->
                <div class="slide active" style="background-image: url(https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg); object-fit: cover; background-size: cover; background-position: center;">
                    <div class="slide-content">
                        <h3>Segundo Trailer de GTA VI Revelado!</h3>
                        <p>Novas cenas épicas de Jason e Lucia em ação no estado de Leonida</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="carousel-indicators">
            <?php $slideCount = count($carousel_slides) ?: 3; ?>
            <?php for ($i = 0; $i < $slideCount; $i++): ?>
                <button class="indicator <?= $i === 0 ? 'active' : '' ?>"></button>
            <?php endfor; ?>
        </div>
    </div>

    <!-- News Section -->
    <div class="news-section">
        <div class="section-header">
            <h2><i class="fa fa-newspaper"></i> Notícias Recentes</h2>
            <div class="section-actions">
                <a href="<?= site_url('noticias') ?>" class="btn btn-small">Todas</a>
                <select class="filter-select">
                    <option>Categoria</option>
                    <option>Trailers</option>
                    <option>Teorias</option>
                    <option>Análises</option>
                    <option>Mapas</option>
                </select>
            </div>
        </div>
        
        <div class="news-grid">
            <?php if (!empty($latest_news)): ?>
                <?php foreach ($latest_news as $index => $news): ?>
                    <a href="<?php echo site_url('noticia/' . $news['slug']); ?>"><article class="news-item <?= $news['is_featured'] ? 'featured' : '' ?>">
                        <div class="news-thumb" style="background-image: url(<?= htmlspecialchars($news['featured_image']) ?>); object-fit: cover; background-size: cover; background-position: center;">
                            <?php if ($news['is_featured']): ?>
                                <div class="news-badge featured">Destaque</div>
                            <?php else: ?>
                                <div class="news-badge <?= $news['category_class'] ?>"><?= ucfirst($news['category']) ?></div>
                            <?php endif; ?>
                            <div class="news-stats">
                                <span><i class="fa fa-comments"></i><?= $news['comments_count'] ?></span>
                                <span><i class="fa fa-heart"></i><?= $news['likes'] ?></span>
                            </div>
                        </div>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($news['title']) ?></h3>
                            <div class="news-meta">
                                <span class="author">
                                    <i class="fa fa-user"></i>
                                    <?= htmlspecialchars($news['display_name'] ?: $news['username']) ?>
                                </span>
                                <span class="date"><?= $news['time_ago'] ?></span>
                            </div>
                            <div class="news-footer">
                                <span><i class="fa fa-eye"></i><?= number_format($news['views']) ?></span>
                                <span><i class="fa fa-comments"></i><?= $news['comments_count'] ?></span>
                            </div>
                        </div>
                    </article></a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback news quando não há dados -->
                <article class="news-item featured">
                    <div class="news-thumb" style="background-image: url(https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg); object-fit: cover; background-size: cover; background-position: center;">
                        <div class="news-badge featured">Destaque</div>
                        <div class="news-stats">
                            <span><i class="fa fa-comments"></i>12</span>
                            <span><i class="fa fa-heart"></i>543</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <h3>Análise Completa do Trailer 2: Novos Detalhes de Leonida</h3>
                        <div class="news-meta">
                            <span class="author">
                                <i class="fa fa-user"></i>
                                TheoryMaster
                            </span>
                            <span class="date">há 2 dias</span>
                        </div>
                        <div class="news-footer">
                            <span><i class="fa fa-eye"></i>2.1k</span>
                            <span><i class="fa fa-comments"></i>67</span>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>
        
        <!-- Pagination for News -->
        <div class="pagination">
            <a href="#" class="pagination-btn disabled">
                <i class="fa fa-angle-left"></i>
            </a>
            <a href="#" class="pagination-btn active">1</a>
            <a href="#" class="pagination-btn">2</a>
            <a href="#" class="pagination-btn">3</a>
            <span class="pagination-info">...</span>
            <a href="#" class="pagination-btn">8</a>
            <a href="#" class="pagination-btn">
                <i class="fa fa-angle-right"></i>
            </a>
        </div>
    </div>

    <!-- Forum Section -->
    <div class="forum-section">
        <div class="section-header">
            <h2><i class="fa fa-comments"></i> Últimos Tópicos do Fórum</h2>
            <div class="section-actions">
                <a href="<?= site_url('forum') ?>" class="btn btn-small">Ver Todos</a>
            </div>
        </div>
        
        <div class="forum-list">
            <?php if (!empty($recent_topics)): ?>
                <?php foreach ($recent_topics as $topic): ?>
                    <div class="forum-item <?= $topic['is_pinned'] ? 'pinned' : '' ?>">
                        <div class="forum-avatar" style="background-image: url(<?= htmlspecialchars($topic['avatar_url']) ?>); object-fit: cover; background-size: cover; background-position: center;">
                            <?php if ($topic['is_pinned']): ?>
                                <i class="fa fa-thumbtack"></i>
                            <?php endif; ?>
                        </div>
                        <div class="forum-content">
                            <h4><?= htmlspecialchars($topic['title']) ?></h4>
                            <div class="forum-meta">
                                <span class="author">
                                    <?php if ($topic['is_pinned']): ?>
                                        <i class="fa fa-star"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($topic['display_name'] ?: $topic['username']) ?>
                                </span>
                                <span class="time"><?= $topic['time_ago'] ?></span>
                                <div class="forum-stats">
                                    <span><i class="fa fa-comments"></i><?= $topic['replies_count'] ?></span>
                                    <span><i class="fa fa-eye"></i><?= number_format($topic['views']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback forum topics -->
                <div class="forum-item pinned">
                    <div class="forum-avatar" style="background-image: url(https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExcDQ5Z2d1eWN5b2M3Z2R3bXBwN3p0dW9yY21jYjhkMjFtdDZyd3A4eiZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cicSTZuSpc6y9WVtvn/giphy.gif); object-fit: cover; background-size: cover; background-position: center;">
                        <i class="fa fa-thumbtack"></i>
                    </div>
                    <div class="forum-content">
                        <h4>Regras Gerais do Fórum - Leia Antes de Postar</h4>
                        <div class="forum-meta">
                            <span class="author">
                                <i class="fa fa-star"></i>
                                Moderação
                            </span>
                            <span class="time">há 1 mês</span>
                            <div class="forum-stats">
                                <span><i class="fa fa-comments"></i>0</span>
                                <span><i class="fa fa-eye"></i>2.5k</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination for Forum -->
        <div class="pagination">
            <a href="#" class="pagination-btn disabled">
                <i class="fa fa-angle-left"></i>
            </a>
            <a href="#" class="pagination-btn active">1</a>
            <a href="#" class="pagination-btn">2</a>
            <a href="#" class="pagination-btn">3</a>
            <a href="#" class="pagination-btn">4</a>
            <a href="#" class="pagination-btn">
                <i class="fa fa-angle-right"></i>
            </a>
        </div>
    </div>

    <!-- Hot Topics -->
    <div class="hot-topics">
        <div class="section-header">
            <h2><i class="fa fa-fire"></i> Assuntos Quentes</h2>
        </div>
        <div class="topics-list">
            <?php if (!empty($hot_topics)): ?>
                <?php foreach ($hot_topics as $topic): ?>
                    <div class="topic-item">
                        <a href="<?= site_url('busca?q=' . urlencode($topic['name'])) ?>" class="topic-tag">#<?= htmlspecialchars($topic['name']) ?></a>
                        <span class="topic-count"><?= $topic['usage_count'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback hot topics -->
                <div class="topic-item">
                    <a href="#" class="topic-tag">#TeoriasGTA6</a>
                    <span class="topic-count">234</span>
                </div>
                <div class="topic-item">
                    <a href="#" class="topic-tag">#MapaLeonida</a>
                    <span class="topic-count">189</span>
                </div>
                <div class="topic-item">
                    <a href="#" class="topic-tag">#JasonLucia</a>
                    <span class="topic-count">156</span>
                </div>
                <div class="topic-item">
                    <a href="#" class="topic-tag">#ViceCity2026</a>
                    <span class="topic-count">134</span>
                </div>
                <div class="topic-item">
                    <a href="#" class="topic-tag">#EasterEggs</a>
                    <span class="topic-count">98</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
</div>
</main>

<script>
window.leonidaData = {
    siteStats: <?= json_encode($site_stats ?? []) ?>,
    onlineCount: <?= $online_count ?? 1247 ?>,
    currentUser: <?= json_encode($current_user ?? null) ?>,
    isLoggedIn: <?= is_logged_in() ? 'true' : 'false' ?>
};
</script>