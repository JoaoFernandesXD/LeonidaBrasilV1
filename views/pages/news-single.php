<!-- views/pages/news-single.php -->
<!-- Página individual de notícia integrada com backend -->

<div class="breadcrumb-container">
        <div class="breadcrumb-content">
            <nav class="breadcrumb">
                <a href="index.html" class="breadcrumb-item">
                    <i class="fa fa-home"></i>
                    Início
                </a>
                <span class="breadcrumb-separator">›</span>
                <a href="<?= site_url('noticias') ?>" class="breadcrumb-item">Notícias</a>
                <span class="breadcrumb-separator">›</span>
                <span class="breadcrumb-item current"><?= htmlspecialchars($news['title']) ?></span>
            </nav>
        </div>
</div>


<!-- Main Container -->
<main class="article-container">
    <div class="article-wrapper">
        <!-- Article Content -->
        <article class="article-main">
            <!-- Article Header -->
            <header class="article-header">
                <div class="article-category">
                    <span class="category-badge <?= strtolower($news['category']) ?>">
                        <i class="fa fa-<?= getCategoryIcon($news['category']) ?>"></i>
                        <?= $news['category_formatted'] ?>
                    </span>
                    <?php if ($news['featured']): ?>
                        <span class="article-featured">
                            <i class="fa fa-star"></i>
                            Em Destaque
                        </span>
                    <?php endif; ?>
                </div>
                
                <h1 class="article-title">
                    <?= htmlspecialchars($news['title']) ?>
                </h1>
                
                <?php if (!empty($news['subtitle'])): ?>
                    <div class="article-subtitle">
                        <?= htmlspecialchars($news['subtitle']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="article-meta">
                    <div class="author-info">
                        <div class="author-avatar">
                            <img src="<?= htmlspecialchars($author['avatar']) ?>" alt="<?= htmlspecialchars($author['name']) ?>">
                        </div>
                        <div class="author-details">
                            <div class="author-name">
                                <?= htmlspecialchars($author['name']) ?>
                                <?php if ($author['level'] >= 3): ?>
                                    <i class="fa fa-check-circle verified" title="Autor Verificado"></i>
                                <?php endif; ?>
                            </div>
                            <div class="author-role"><?= getUserRole($author['level']) ?></div>
                        </div>
                    </div>
                    
                    <div class="article-stats">
                        <div class="stat-item">
                            <i class="fa fa-calendar"></i>
                            <span><?= $news['formatted_date'] ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-clock"></i>
                            <span><?= $news['reading_time'] ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-eye"></i>
                            <span class="view-count"><?= number_format($news['views']) ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fa fa-comments"></i>
                            <span class="comment-count"><?= $comments_count ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Article Featured Image -->
            <?php if (!empty($news['featured_image'])): ?>
                <div class="article-featured-image">
                    <img src="<?= htmlspecialchars($news['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($news['title']) ?>"
                         loading="lazy">
                    <div class="image-caption">
                        <i class="fa fa-info-circle"></i>
                        <?= htmlspecialchars($news['title']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Article Social Actions -->
            <div class="article-actions">
                <div class="social-actions">
                    <button class="action-btn like-btn <?= $is_liked ? 'liked' : '' ?>" 
                            data-news-id="<?= $news['id'] ?>"
                            title="Curtir esta notícia">
                        <i class="fa fa-heart"></i>
                        <span class="like-count"><?= number_format($news['likes']) ?></span>
                    </button>
                    
                    <button class="action-btn share-btn" title="Compartilhar notícia">
                        <i class="fa fa-share"></i>
                        Compartilhar
                    </button>
                    
                    <button class="action-btn bookmark-btn" 
                            data-news-id="<?= $news['id'] ?>"
                            title="Salvar nos favoritos">
                        <i class="fa fa-bookmark"></i>
                        Salvar
                    </button>
                </div>
                
                <div class="reading-progress">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <span class="progress-text">0% lido</span>
                </div>
            </div>

            <!-- Article Content -->
            <div class="article-content">
                <?php if (!empty($news['excerpt'])): ?>
                    <div class="content-intro">
                        <p class="lead">
                            <?= nl2br(htmlspecialchars($news['excerpt'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="content-body">
                    <?= $news['content'] ?>
                </div>
                
                <!-- Image Gallery (se houver) -->
                <?php if (!empty($news['image_gallery'])): ?>
                    <div class="news-gallery">
                        <h3>Galeria de Imagens</h3>
                        <div class="gallery-grid">
                            <?php foreach ($news['image_gallery'] as $image): ?>
                                <div class="gallery-item">
                                    <img src="<?= htmlspecialchars($image['url']) ?>" 
                                         alt="<?= htmlspecialchars($image['caption'] ?? '') ?>"
                                         data-lightbox="news-gallery"
                                         loading="lazy">
                                    <?php if (!empty($image['caption'])): ?>
                                        <div class="image-caption"><?= htmlspecialchars($image['caption']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Article Tags -->
            <?php if (!empty($news['tags'])): ?>
                <div class="article-tags">
                    <h3>
                        <i class="fa fa-tags"></i>
                        Tags relacionadas:
                    </h3>
                    <div class="tags-list">
                        <?php foreach ($news['tags'] as $tag): ?>
                            <a href="<?= site_url('busca?q=' . urlencode($tag)) ?>" class="tag">#<?= htmlspecialchars($tag) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Related Articles -->
            <?php if (!empty($related_news)): ?>
                <div class="related-articles">
                    <h3>
                        <i class="fa fa-newspaper"></i>
                        Artigos Relacionados
                    </h3>
                    <div class="related-grid">
                        <?php foreach ($related_news as $related): ?>
                            <article class="related-item">
                                <div class="related-thumb">
                                    <img src="<?= htmlspecialchars($related['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($related['title']) ?>"
                                         loading="lazy">
                                    <div class="related-category"><?= formatCategoryName($news['category']) ?></div>
                                </div>
                                <div class="related-content">
                                    <h4>
                                        <a href="<?= $related['url'] ?>">
                                            <?= htmlspecialchars($related['title']) ?>
                                        </a>
                                    </h4>
                                    <div class="related-meta">
                                        <span><i class="fa fa-eye"></i><?= $related['formatted_views'] ?></span>
                                        <span><i class="fa fa-clock"></i><?= $related['time_ago'] ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </article>

        <!-- Sidebar -->
        <aside class="article-sidebar">
            <!-- Author Card -->
            <div class="sidebar-widget author-card">
                <div class="widget-header">
                    <i class="fa fa-user"></i>
                    Sobre o Autor
                </div>
                <div class="widget-content">
                    <div class="author-full-info">
                        <div class="author-avatar-large">
                            <img src="<?= htmlspecialchars($author['avatar']) ?>" alt="<?= htmlspecialchars($author['name']) ?>">
                            <div class="author-status <?= isUserOnline($author['id']) ? 'online' : 'offline' ?>"></div>
                        </div>
                        <div class="author-info-details">
                            <h4><?= htmlspecialchars($author['name']) ?></h4>
                            <div class="author-title"><?= getUserRole($author['level']) ?></div>
                            <div class="author-stats">
                                <div class="stat">
                                    <span class="stat-number"><?= $author['news_count'] ?></span>
                                    <span class="stat-label">Artigos</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?= number_format($author['experience_points']) ?></span>
                                    <span class="stat-label">XP</span>
                                </div>
                            </div>
                            <?php if (!empty($author['bio'])): ?>
                                <p class="author-bio">
                                    <?= nl2br(htmlspecialchars($author['bio'])) ?>
                                </p>
                            <?php endif; ?>
                            <a href="<?= $author['url'] ?>" class="btn btn-primary btn-small">
                                <i class="fa fa-user"></i>
                                Ver Perfil
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter -->
            <div class="sidebar-widget newsletter">
                <div class="widget-header">
                    <i class="fa fa-envelope"></i>
                    Newsletter
                </div>
                <div class="widget-content">
                    <h4>Receba as últimas notícias de GTA VI</h4>
                    <p>Seja o primeiro a saber sobre novos trailers, vazamentos e análises exclusivas!</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" name="email" placeholder="Seu e-mail" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-paper-plane"></i>
                            Inscrever
                        </button>
                    </form>
                    <div class="newsletter-stats">
                        <i class="fa fa-users"></i>
                        <span>Junte-se a mais de 12.500 inscritos</span>
                    </div>
                </div>
            </div>

            <!-- Popular Articles - VERSÃO SEGURA -->
            <div class="sidebar-widget popular-articles">
                <div class="widget-header">
                    <i class="fa fa-fire"></i>
                    Mais Populares
                </div>
                <div class="widget-content">
                    <div class="popular-list">
                        <?php
                        // Usar função segura que não quebra se houver erro
                        $popular_articles = getPopularArticles(5);
                        if (!empty($popular_articles)):
                            foreach ($popular_articles as $index => $article):
                        ?>
                            <article class="popular-item">
                                <div class="popular-rank"><?= $index + 1 ?></div>
                                <div class="popular-content">
                                    <h5>
                                        <a href="<?= site_url('noticia/' . $article['slug']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h5>
                                    <div class="popular-meta">
                                        <span><i class="fa fa-eye"></i><?= $article['formatted_views'] ?></span>
                                        <span><i class="fa fa-comments"></i><?= $article['comments_count'] ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php 
                            endforeach;
                        else:
                            // Fallback caso não tenha artigos populares
                        ?>
                            <p class="text-muted">Nenhum artigo popular no momento.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Social Widget -->
            <div class="sidebar-widget social-widget">
                <div class="widget-header">
                    <i class="fa fa-share-alt"></i>
                    Siga-nos
                </div>
                <div class="widget-content">
                    <div class="social-buttons">
                        <a href="#" class="social-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                            <div class="social-info">
                                <span class="social-name">Facebook</span>
                                <span class="social-count">25.4k</span>
                            </div>
                        </a>
                        <a href="#" class="social-btn twitter">
                            <i class="fab fa-twitter"></i>
                            <div class="social-info">
                                <span class="social-name">Twitter</span>
                                <span class="social-count">18.7k</span>
                            </div>
                        </a>
                        <a href="#" class="social-btn youtube">
                            <i class="fab fa-youtube"></i>
                            <div class="social-info">
                                <span class="social-name">YouTube</span>
                                <span class="social-count">42.1k</span>
                            </div>
                        </a>
                        <a href="#" class="social-btn discord">
                            <i class="fab fa-discord"></i>
                            <div class="social-info">
                                <span class="social-name">Discord</span>
                                <span class="social-count">12.8k</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Comments Section -->
    <section class="comments-section">
        <div class="comments-header">
            <h3>
                <i class="fa fa-comments"></i>
                Comentários (<?= $comments_count ?>)
            </h3>
            <div class="comments-actions">
                <select class="sort-comments" id="sortComments">
                    <option value="recent">Mais Recentes</option>
                    <option value="popular">Mais Curtidos</option>
                    <option value="oldest">Mais Antigos</option>
                </select>
            </div>
        </div>

        <!-- Comment Form -->
        <?php if (is_logged_in()): ?>
            <div class="comment-form-section">
                <div class="comment-form-header">
                    <h4>
                        <i class="fa fa-edit"></i>
                        Deixe seu comentário
                    </h4>
                </div>
                <form class="comment-form" id="commentForm" data-news-id="<?= $news['id'] ?>">
                    <div class="form-user-info">
                        <div class="user-avatar">
                            <img src="<?= htmlspecialchars(current_user()['avatar'] ?? 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg') ?>" 
                                 alt="<?= htmlspecialchars(current_user()['display_name']) ?>">
                        </div>
                        <div class="user-name"><?= htmlspecialchars(current_user()['display_name'] ?: current_user()['username']) ?></div>
                    </div>
                    <textarea class="comment-textarea" 
                              name="content" 
                              placeholder="O que você achou desta análise? Compartilhe sua opinião..." 
                              required></textarea>
                    <div class="comment-actions">
                        <div class="comment-options">
                            <label class="option">
                                <input type="checkbox" name="notify_replies" checked>
                                <span>Notificar sobre respostas</span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-paper-plane"></i>
                            Comentar
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="comment-login-required">
                <div class="login-message">
                    <i class="fa fa-lock"></i>
                    <h4>Faça login para comentar</h4>
                    <p>Participe da discussão e compartilhe sua opinião sobre esta notícia!</p>
                    <a href="<?= site_url('login') ?>" class="btn btn-primary">
                        <i class="fa fa-sign-in-alt"></i>
                        Fazer Login
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Comments List -->
        <div class="comments-list" id="commentsList">
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-item" data-comment-id="<?= $comment['id'] ?>">
                        <div class="comment-avatar">
                            <img src="<?= htmlspecialchars($comment['avatar']) ?>" alt="<?= htmlspecialchars($comment['author_name']) ?>">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <a href="<?= $comment['author_url'] ?>">
                                        <?= htmlspecialchars($comment['author_name']) ?>
                                    </a>
                                    <?php if ($comment['author_id'] == $news['author_id']): ?>
                                        <span class="comment-badge author">Autor</span>
                                    <?php elseif (getUserLevel($comment['author_id']) >= 3): ?>
                                        <span class="comment-badge verified">Verificado</span>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-time"><?= $comment['time_ago'] ?></div>
                            </div>
                            <div class="comment-text">
                                <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                            </div>
                            <div class="comment-actions">
                                <button class="comment-action like <?= isCommentLiked($comment['id']) ? 'liked' : '' ?>" 
                                        data-comment-id="<?= $comment['id'] ?>">
                                    <i class="fa fa-heart"></i>
                                    <span><?= $comment['likes'] ?></span>
                                </button>
                                <?php if (is_logged_in()): ?>
                                    <button class="comment-action reply" data-comment-id="<?= $comment['id'] ?>">
                                        <i class="fa fa-reply"></i>
                                        Responder
                                    </button>
                                <?php endif; ?>
                                <button class="comment-action report" data-comment-id="<?= $comment['id'] ?>">
                                    <i class="fa fa-flag"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-comments">
                    <i class="fa fa-comments fa-3x"></i>
                    <h4>Seja o primeiro a comentar!</h4>
                    <p>Compartilhe sua opinião sobre esta notícia.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Load More Comments -->
        <?php if ($comments_count > count($comments)): ?>
            <div class="load-more-comments">
                <button class="btn btn-secondary" id="loadMoreComments" data-news-id="<?= $news['id'] ?>">
                    <i class="fa fa-plus"></i>
                    Carregar mais comentários (<?= $comments_count - count($comments) ?> restantes)
                </button>
            </div>
        <?php endif; ?>
    </section>
</main>
<style>
/* Notifications */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    transform: translateX(100%);
    transition: all 0.3s ease;
    z-index: 9999;
    max-width: 400px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success { border-left: 4px solid #28a745; }
.notification-error { border-left: 4px solid #dc3545; }
.notification-warning { border-left: 4px solid #ffc107; }
.notification-info { border-left: 4px solid #17a2b8; }

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.5;
    margin-left: auto;
}

/* Loading states */
.loading {
    pointer-events: none;
    opacity: 0.6;
}

/* Animations */
.animate-heart {
    animation: heartBeat 0.6s ease-in-out;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.1); }
    50% { transform: scale(1.2); }
    75% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.show {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: all 0.3s ease;
}

.modal-overlay.show .modal-content {
    transform: scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.5;
}

/* Share options */
.share-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.share-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
}

.share-option:hover {
    background: #f8f9fa;
    border-color: #007bff;
}

.share-option.facebook { color: #1877f2; }
.share-option.twitter { color: #1da1f2; }
.share-option.whatsapp { color: #25d366; }
.share-option.email { color: #6c757d; }

/* Scroll to top */
.scroll-to-top {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
}

.scroll-to-top.show {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

/* Character counter */
.char-counter {
    font-size: 12px;
    color: #6c757d;
    text-align: right;
    margin-top: 4px;
}

.char-counter.warning {
    color: #dc3545;
}

/* Comment form states */
.comment-form.loading {
    opacity: 0.6;
    pointer-events: none;
}

.comment-form.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Button states */
.liked {
    color: #e91e63 !important;
}

.bookmarked {
    color: #ffc107 !important;
}

/* Reading progress */
.reading-progress {
    position: sticky;
    top: 0;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    z-index: 100;
}

.progress-bar {
    height: 3px;
    background: #e9ecef;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    width: 0%;
    transition: width 0.1s ease;
}
</style>
<!-- JavaScript para funcionalidades interativas -->
<script>
// Dados da notícia para JavaScript
window.newsData = {
    id: <?= $news['id'] ?>,
    title: <?= json_encode($news['title']) ?>,
    url: <?= json_encode($canonical_url) ?>,
    isLiked: <?= $is_liked ? 'true' : 'false' ?>,
    userId: <?= is_logged_in() ? $_SESSION['user_id'] : 'null' ?>
};
</script>
<script src="<?= site_url() ?>assets/js/noticia.js"></script>