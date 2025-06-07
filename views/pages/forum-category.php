<?php
// views/pages/forum-category.php
// Página de categoria do fórum

// Verificar se os dados foram passados
$category = $category ?? null;
$topics = $topics ?? [];
$pagination = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'total' => 0];

if (!$category) {
    header('HTTP/1.0 404 Not Found');
    include 'views/pages/404.php';
    return;
}

$current_page = $pagination['current_page'];
$total_pages = $pagination['total_pages'];
$total_topics = $pagination['total'];
?>

<!-- Breadcrumb -->
<div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <a href="<?php echo site_url(''); ?>" class="breadcrumb-item">
                <i class="fas fa-home"></i> Início
            </a>
            <span class="breadcrumb-separator">/</span>
            <a href="<?php echo site_url('forum'); ?>" class="breadcrumb-item">Fórum</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item current"><?php echo htmlspecialchars($category['name']); ?></span>
        </nav>
        <div class="page-actions">
            <?php if (is_logged_in()): ?>
                <button class="action-btn" onclick="window.location.href='<?php echo site_url('forum/criar-topico?categoria=' . $category['id']); ?>'">
                    <i class="fas fa-plus"></i> Novo Tópico
                </button>
            <?php else: ?>
                <button class="action-btn" onclick="window.location.href='<?php echo site_url('login'); ?>'">
                    <i class="fas fa-sign-in-alt"></i> Login para Postar
                </button>
            <?php endif; ?>
            <button class="action-btn" onclick="toggleSubscription(<?php echo $category['id']; ?>)">
                <i class="fas fa-bell"></i> Seguir Categoria
            </button>
        </div>
    </div>
</div>

<!-- Main Container -->
<div class="main-container">
    <div class="content-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar-left">
            <!-- Category Info Widget -->
            <div class="widget">
                <div class="widget-header blue">
                    <span><i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-folder'); ?>"></i> 
                          <?php echo htmlspecialchars($category['name']); ?></span>
                </div>
                <div class="widget-content">
                    <div class="category-description">
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                    <div class="category-stats-detailed">
                        <div class="stat-row">
                            <div class="stat-label">Total de Tópicos:</div>
                            <div class="stat-value"><?php echo number_format($total_topics); ?></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Página Atual:</div>
                            <div class="stat-value"><?php echo $current_page; ?> de <?php echo $total_pages; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="widget">
                <div class="widget-header pink">
                    <span><i class="fas fa-compass"></i> Navegação Rápida</span>
                </div>
                <div class="widget-content">
                    <div class="quick-nav-list">
                        <a href="<?php echo site_url('forum'); ?>" class="quick-nav-item">
                            <i class="fas fa-arrow-left"></i> Voltar ao Fórum
                        </a>
                        <a href="#pinned-topics" class="quick-nav-item">
                            <i class="fas fa-thumbtack"></i> Tópicos Fixados
                        </a>
                        <a href="#latest-topics" class="quick-nav-item">
                            <i class="fas fa-clock"></i> Últimos Tópicos
                        </a>
                        <?php if ($total_pages > 1): ?>
                            <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=' . $total_pages); ?>" 
                               class="quick-nav-item">
                                <i class="fas fa-fast-forward"></i> Última Página
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Forum Rules Widget -->
            <div class="widget">
                <div class="widget-header blue">
                    <span><i class="fas fa-exclamation-triangle"></i> Regras da Categoria</span>
                </div>
                <div class="widget-content">
                    <div class="rules-list">
                        <div class="rule-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Mantenha o foco no tópico da categoria</span>
                        </div>
                        <div class="rule-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Seja respeitoso com outros membros</span>
                        </div>
                        <div class="rule-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Não faça spam ou posts repetitivos</span>
                        </div>
                        <div class="rule-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Use títulos descritivos</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Category Header -->
            <div class="category-header-section">
                <div class="category-header-content">
                    <div class="category-icon-large">
                        <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-folder'); ?>"></i>
                    </div>
                    <div class="category-header-info">
                        <h1><?php echo htmlspecialchars($category['name']); ?></h1>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <div class="category-meta">
                            <span class="meta-item">
                                <i class="fas fa-comments"></i>
                                <?php echo number_format($total_topics); ?> tópicos
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-eye"></i>
                                Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="category-actions">
                    <?php if (is_logged_in()): ?>
                        <button class="category-action-btn primary" 
                                onclick="window.location.href='<?php echo site_url('forum/criar-topico?categoria=' . $category['id']); ?>'">
                            <i class="fas fa-plus"></i>
                            Novo Tópico
                        </button>
                    <?php endif; ?>
                    <button class="category-action-btn secondary" onclick="toggleCategorySubscription()">
                        <i class="fas fa-bell"></i>
                        Seguir
                    </button>
                </div>
            </div>

            <!-- Topics Filters -->
            <div class="topics-filters">
                <div class="filter-left">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> Todos
                        </button>
                        <button class="filter-btn" data-filter="pinned">
                            <i class="fas fa-thumbtack"></i> Fixados
                        </button>
                        <button class="filter-btn" data-filter="unread">
                            <i class="fas fa-circle"></i> Não Lidos
                        </button>
                    </div>
                </div>
                <div class="filter-right">
                    <form method="GET" action="<?php echo site_url('forum/categoria/' . $category['slug']); ?>" class="sort-form">
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="recent" <?php echo ($_GET['sort'] ?? '') === 'recent' ? 'selected' : ''; ?>>
                                Mais Recentes
                            </option>
                            <option value="popular" <?php echo ($_GET['sort'] ?? '') === 'popular' ? 'selected' : ''; ?>>
                                Mais Populares
                            </option>
                            <option value="replies" <?php echo ($_GET['sort'] ?? '') === 'replies' ? 'selected' : ''; ?>>
                                Mais Respondidos
                            </option>
                            <option value="views" <?php echo ($_GET['sort'] ?? '') === 'views' ? 'selected' : ''; ?>>
                                Mais Visualizados
                            </option>
                            <option value="oldest" <?php echo ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : ''; ?>>
                                Mais Antigos
                            </option>
                        </select>
                        <?php if (isset($_GET['page']) && $_GET['page'] > 1): ?>
                            <input type="hidden" name="page" value="<?php echo intval($_GET['page']); ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Topics List -->
            <?php if (!empty($topics)): ?>
                <div class="topics-section" id="latest-topics">
                    <div class="topics-list">
                        <?php foreach ($topics as $topic): ?>
                            <div class="topic-item <?php echo $topic['is_pinned'] ? 'pinned' : ''; ?> <?php echo $topic['is_locked'] ? 'locked' : ''; ?>">
                                <div class="topic-status">
                                    <?php if ($topic['is_pinned']): ?>
                                        <div class="status-icon pinned">
                                            <i class="fas fa-thumbtack"></i>
                                        </div>
                                    <?php elseif ($topic['is_locked']): ?>
                                        <div class="status-icon locked">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-icon unread">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="topic-info">
                                    <div class="topic-title">
                                        <h3>
                                            <a href="<?php echo site_url('forum/topico/' . $topic['slug']); ?>">
                                                <?php if ($topic['is_pinned']): ?>
                                                    <i class="fas fa-thumbtack topic-pin-icon"></i>
                                                <?php endif; ?>
                                                <?php if ($topic['is_locked']): ?>
                                                    <i class="fas fa-lock topic-lock-icon"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($topic['title']); ?>
                                            </a>
                                        </h3>
                                        <div class="topic-tags">
                                            <?php if ($topic['is_pinned']): ?>
                                                <span class="tag pinned">Fixado</span>
                                            <?php endif; ?>
                                            <?php if ($topic['is_locked']): ?>
                                                <span class="tag locked">Fechado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="topic-meta">
                                        <span class="author">
                                            <img src="<?php echo $topic['avatar'] ?: site_url('assets/images/avatar.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($topic['author_name']); ?>" 
                                                 class="author-avatar-tiny">
                                            <strong><?php echo htmlspecialchars($topic['author_name']); ?></strong>
                                        </span>
                                        <span class="created-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo $topic['time_ago']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="topic-stats">
                                    <div class="stat">
                                        <div class="stat-number"><?php echo number_format($topic['replies_count']); ?></div>
                                        <div class="stat-label">Respostas</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-number"><?php echo $topic['formatted_views']; ?></div>
                                        <div class="stat-label">Views</div>
                                    </div>
                                </div>
                                
                                <div class="topic-last-post">
                                    <?php if (isset($topic['last_reply_author']) && $topic['last_reply_at']): ?>
                                        <div class="last-post-info">
                                            <div class="last-post-user">
                                                <strong><?php echo htmlspecialchars($topic['last_reply_author']); ?></strong>
                                            </div>
                                            <div class="last-post-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $topic['last_reply_time_ago']; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="last-post-info">
                                            <div class="last-post-user">
                                                <strong><?php echo htmlspecialchars($topic['author_name']); ?></strong>
                                            </div>
                                            <div class="last-post-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $topic['time_ago']; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-topics">
                    <div class="no-topics-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Nenhum tópico encontrado</h3>
                    <p>Esta categoria ainda não possui tópicos. Seja o primeiro a criar um!</p>
                    <?php if (is_logged_in()): ?>
                        <button class="btn btn-primary" 
                                onclick="window.location.href='<?php echo site_url('forum/criar-topico?categoria=' . $category['id']); ?>'">
                            <i class="fas fa-plus"></i>
                            Criar Primeiro Tópico
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" 
                                onclick="window.location.href='<?php echo site_url('login'); ?>'">
                            <i class="fas fa-sign-in-alt"></i>
                            Faça Login para Criar
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Botão Anterior -->
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=' . ($current_page - 1)); ?>" 
                       class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php else: ?>
                    <button class="pagination-btn disabled">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                <?php endif; ?>

                <!-- Páginas -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                // Sempre mostrar primeira página
                if ($start_page > 1):
                ?>
                    <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=1'); ?>" 
                       class="pagination-btn">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Páginas do meio -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <button class="pagination-btn active"><?php echo $i; ?></button>
                    <?php else: ?>
                        <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=' . $i); ?>" 
                           class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Sempre mostrar última página -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                    <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=' . $total_pages); ?>" 
                       class="pagination-btn"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <!-- Botão Próximo -->
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo site_url('forum/categoria/' . $category['slug'] . '?page=' . ($current_page + 1)); ?>" 
                       class="pagination-btn">
                        Próximo <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="pagination-btn disabled">
                        Próximo <i class="fas fa-chevron-right"></i>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
/* Estilos específicos para página de categoria */
.category-header-section {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    border-radius: 16px;
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
}

.category-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}



.category-icon-large {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    flex-shrink: 0;
}

.category-header-info h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 700;
}

.category-header-info p {
    margin: 0 0 15px 0;
    opacity: 0.9;
    font-size: 16px;
    line-height: 1.5;
}

.category-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    opacity: 0.8;
}

.category-actions {
    display: flex;
    gap: 15px;
    flex-shrink: 0;
}

.category-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.category-action-btn.primary {
    background: white;
    color: var(--accent-color);
}

.category-action-btn.secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.category-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.topics-filters {
    background: white;
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-md);
}


.filter-buttons {
    display: flex;
    gap: 10px;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--accent-color);
    color: white;
    border-color: var(--accent-color);
}

.sort-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-select {
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--card-bg);
    color: var(--text-color);
    cursor: pointer;
}

.topic-item {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 20px;
    padding: 20px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 15px;
    align-items: center;
    transition: all 0.3s ease;
}

.topic-item:hover {
    border-color: var(--accent-color);
    transform: translateX(5px);
}

.topic-item.pinned {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
    border-color: rgba(255, 193, 7, 0.3);
}

.topic-item.locked {
    opacity: 0.7;
    background: var(--bg-secondary);
}

.topic-pin-icon,
.topic-lock-icon {
    font-size: 12px;
    margin-right: 8px;
    opacity: 0.7;
}

.author-avatar-tiny {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 8px;
}

.no-topics {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-muted);
}

.no-topics-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.no-topics h3 {
    margin: 0 0 15px 0;
    font-size: 24px;
    color: var(--text-color);
}

.no-topics p {
    margin: 0 0 30px 0;
    font-size: 16px;
    line-height: 1.6;
}

.category-stats-detailed {
    margin-top: 15px;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--text-muted);
    font-size: 14px;
}

.stat-value {
    font-weight: 600;
    color: var(--text-color);
}

.quick-nav-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: var(--bg-secondary);
    border-radius: 8px;
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.quick-nav-item:hover {
    transform: translateX(5px);
}

.rules-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rule-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    line-height: 1.5;
}

.rule-item i {
    color: var(--success-color);
    margin-top: 2px;
    flex-shrink: 0;
}

/* Topics Section */
.topics-section,
.pinned-section {
    background: white;
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--spacing-lg);
}

.section-header {
    background: var(--bg-light);
    padding: var(--spacing-md) var(--spacing-lg);
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.section-header h2 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.section-header .fa {
    color: var(--color-primary);
    font-size: 14px;
}

.section-actions {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.topics-count {
    font-size: 11px;
    color: var(--text-medium);
    font-weight: 500;
}

/* Topics List */
.topics-list {
    display: flex;
    flex-direction: column;
}

.topic-item {
    display: grid;
    grid-template-columns: 50px 1fr 100px 120px;
    gap: var(--spacing-md);
    align-items: center;
    padding: var(--spacing-md) var(--spacing-lg);
    border-bottom: 1px solid var(--bg-light);
    transition: all 0.3s ease;
    cursor: pointer;
}

.topic-item:last-child {
    border-bottom: none;
}

.topic-item:hover {
    background: var(--bg-light);
    transform: translateY(-1px);
}

/* Topic Status */
.topic-status {
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: white;
}

.status-icon.unread {
    background: var(--color-primary);
}

.status-icon.read {
    background: var(--text-light);
}

.status-icon.pinned {
    background: var(--color-accent);
    transform: rotate(45deg);
}

.status-icon.hot {
    background: var(--color-danger);
    animation: pulse 2s infinite;
}

.status-icon.locked {
    background: var(--text-medium);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Topic Info */
.topic-info {
    min-width: 0;
}

.topic-title {
    margin-bottom: var(--spacing-xs);
}

.topic-title h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 var(--spacing-xs) 0;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.topic-title h3 a {
    color: inherit;
    text-decoration: none;
    transition: color 0.2s ease;
}

.topic-title h3 a:hover {
    color: var(--color-primary);
}

.topic-tags {
    display: flex;
    gap: var(--spacing-xs);
    flex-wrap: wrap;
    margin-bottom: var(--spacing-xs);
}

.tag {
    background: var(--bg-light);
    color: var(--text-medium);
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
}

.tag:hover {
    background: var(--color-primary);
    color: white;
    cursor: pointer;
}

.tag.official {
    background: var(--color-accent);
    color: var(--text-dark);
}

.tag.important {
    background: var(--color-danger);
    color: white;
}

.tag.guide {
    background: var(--color-info);
    color: white;
}

.tag.updated {
    background: var(--color-success);
    color: white;
}

.tag.hot {
    background: var(--color-danger);
    color: white;
    animation: glow 2s ease-in-out infinite alternate;
}

.tag.locked {
    background: var(--text-medium);
    color: white;
}

@keyframes glow {
    from { box-shadow: 0 0 0 rgba(255, 107, 107, 0); }
    to { box-shadow: 0 0 8px rgba(255, 107, 107, 0.4); }
}

.topic-meta {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    font-size: 11px;
    color: var(--text-medium);
}

.author {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 500;
}

.author .fa {
    color: var(--color-primary);
    font-size: 10px;
}

.category {
    display: flex;
    align-items: center;
    gap: 4px;
}

.category .fa {
    color: var(--color-accent);
    font-size: 10px;
}

/* Topic Stats */
.topic-stats {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
    text-align: center;
}

.topic-stats .stat {
    background: var(--bg-light);
    border-radius: var(--radius-sm);
    padding: var(--spacing-xs);
}

.topic-stats .stat-number {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-primary);
    line-height: 1;
    margin-bottom: 2px;
}

.topic-stats .stat-label {
    font-size: 9px;
    color: var(--text-medium);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsivo */
@media (max-width: 768px) {
    .category-header-section {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .category-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .category-actions {
        width: 100%;
        justify-content: center;
    }
    
    .topics-filters {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .filter-buttons {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .topic-item {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
    }
    
    .category-meta {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>


<script>
// JavaScript mínimo necessário
function toggleCategorySubscription() {
    <?php if (is_logged_in()): ?>
        // Implementar funcionalidade de seguir categoria
        alert('Funcionalidade de seguir categoria em desenvolvimento');
    <?php else: ?>
        window.location.href = '<?php echo site_url('login'); ?>';
    <?php endif; ?>
}

function toggleSubscription(categoryId) {
    toggleCategorySubscription();
}

// Auto-scroll para tópicos específicos se houver hash na URL
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        const element = document.querySelector(window.location.hash);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    }
});
</script>