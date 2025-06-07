<?php
// views/pages/forum.php
// Página principal do fórum integrada com PHP

// Verificar se os dados foram passados
$categories = $categories ?? [];
$recent_topics = $recent_topics ?? [];
$forum_stats = $forum_stats ?? [
    'total_topics' => 0,
    'total_replies' => 0,
    'total_users' => 0
];
$online_users = $online_users ?? 0;

// Paginação
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$topics_per_page = 20;
$total_pages = isset($pagination['total_pages']) ? $pagination['total_pages'] : 1;
?>

<!-- Breadcrumb -->
<div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <a href="<?php echo site_url(''); ?>" class="breadcrumb-item">
                <i class="fas fa-home"></i> Início
            </a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-item current">Fórum</span>
        </nav>
        <div class="page-actions">
            <?php if (is_logged_in()): ?>
                <button class="action-btn" onclick="window.location.href='<?php echo site_url('forum/criar-topico'); ?>'">
                    <i class="fas fa-plus"></i> Novo Tópico
                </button>
            <?php else: ?>
                <button class="action-btn" onclick="window.location.href='<?php echo site_url('login'); ?>'">
                    <i class="fas fa-sign-in-alt"></i> Login para Postar
                </button>
            <?php endif; ?>
            <button class="action-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i> Notificações
            </button>
        </div>
    </div>
</div>

<!-- Main Container -->
<div class="main-container">
    <div class="content-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar-left">
            <!-- Login Widget -->
            <div class="widget">
                <div class="widget-header pink">
                    <span><i class="fas fa-user"></i>Publicidade</span>
                </div>
                <div class="widget-content">
                    Anuncio aqui
                </div>
            </div>

            <!-- Forum Stats Widget -->
            <div class="widget">
                <div class="widget-header blue">
                    <span><i class="fas fa-chart-bar"></i> Estatísticas do Fórum</span>
                </div>
                <div class="widget-content">
                    <div class="forum-stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon topics">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo number_format($forum_stats['total_topics']); ?></div>
                                <div class="stat-label">Tópicos</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon posts">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo number_format($forum_stats['total_replies']); ?></div>
                                <div class="stat-label">Posts</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon members">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo number_format($forum_stats['total_users']); ?></div>
                                <div class="stat-label">Membros</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon online">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo number_format($online_users); ?></div>
                                <div class="stat-label">Online</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Contributors Widget -->
            <div class="widget">
                <div class="widget-header pink">
                    <span><i class="fas fa-star"></i> Top Contribuidores</span>
                </div>
                <div class="widget-content">
                    <div class="contributor-list">
                        <?php
                        // Buscar top contribuidores (pode ser implementado no controller depois)
                        $top_contributors = [
                            ['name' => 'GameMaster', 'posts' => 1247, 'avatar' => 'user1.jpg', 'position' => 1],
                            ['name' => 'ViceTheory', 'posts' => 891, 'avatar' => 'user2.jpg', 'position' => 2],
                            ['name' => 'LeonidaFan', 'posts' => 756, 'avatar' => 'user3.jpg', 'position' => 3]
                        ];
                        
                        foreach ($top_contributors as $contributor):
                            $rank_class = ['', 'gold', 'silver', 'bronze'][$contributor['position']] ?? '';
                        ?>
                            <div class="contributor-item">
                                <div class="contributor-rank <?php echo $rank_class; ?>">
                                    <?php echo $contributor['position']; ?>
                                </div>
                                <div class="contributor-avatar">
                                    <img src="<?php echo site_url('media/images/avatars/' . $contributor['avatar']); ?>" 
                                         alt="<?php echo htmlspecialchars($contributor['name']); ?>">
                                </div>
                                <div class="contributor-info">
                                    <div class="contributor-name"><?php echo htmlspecialchars($contributor['name']); ?></div>
                                    <div class="contributor-count"><?php echo number_format($contributor['posts']); ?> posts</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Widget -->
            <div class="widget">
                <div class="widget-header blue">
                    <span><i class="fas fa-clock"></i> Atividade Recente</span>
                </div>
                <div class="widget-content">
                    <div class="activity-list">
                        <?php foreach (array_slice($recent_topics, 0, 3) as $topic): ?>
                            <div class="activity-item">
                                <div class="activity-icon new-topic">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text">
                                        <strong><?php echo htmlspecialchars($topic['author_name']); ?></strong> 
                                        criou um novo tópico
                                    </div>
                                    <div class="activity-time"><?php echo $topic['time_ago']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Forum Header -->
            <div class="forum-header">
                <div class="forum-title">
                    <h1><i class="fas fa-comments"></i> Fórum da Comunidade</h1>
                    <p>Discuta sobre GTA VI, compartilhe teorias e conecte-se com outros fãs da série</p>
                </div>
                <div class="forum-actions">
                    <?php if (is_logged_in()): ?>
                        <button class="forum-action-btn mark-read">
                            <i class="fas fa-check-double"></i>
                            Marcar como lido
                        </button>
                        <button class="forum-action-btn new-topic" 
                                onclick="window.location.href='<?php echo site_url('forum/criar-topico'); ?>'">
                            <i class="fas fa-plus"></i>
                            Novo Tópico
                        </button>
                    <?php endif; ?>
                </div>
            </div>
                        
             <!-- Forum Filters -->
             <div class="forum-filters">
                <?php if (!empty($categories)): ?>
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">
                            <i class="fas fa-th-large"></i>
                            Todas as Categorias
                        </button>
                        <?php foreach ($categories as $category): ?>
                        <a href="<?php echo site_url('forum/categoria/' . $category['slug']); ?>"><button class="filter-tab" data-filter="discussions">
                            <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-folder'); ?>"></i>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button></a>
                        <?php endforeach; ?>
                        <button class="filter-tab" data-filter="help">
                            <i class="fas fa-question-circle"></i>
                            Ajuda
                        </button>
                        <?php else: ?>
                            <button class="filter-tab" data-filter="help">
                            <i class="fas fa-question-circle"></i>
                            >Nenhuma categoria encontrada.
                        </button>
                        <?php endif; ?>

                    </div>
                   
                </div>

            <!-- Recent Topics -->
            <?php if (!empty($recent_topics)): ?>
            <div class="topics-section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Tópicos Recentes</h2>
                    <div class="section-actions">
                        <span class="topics-count">
                            Últimos <?php echo count($recent_topics); ?> tópicos atualizados
                        </span>
                    </div>
                </div>
                <div class="topics-list">
                    <?php foreach ($recent_topics as $topic): ?>
                        <div class="topic-item">
                            <div class="topic-status">
                                <div class="status-icon unread">
                                    <i class="fas fa-circle"></i>
                                </div>
                            </div>
                            <div class="topic-info">
                                <div class="topic-title">
                                    <h3>
                                        <a href="<?php echo site_url('forum/topico/' . $topic['slug']); ?>">
                                            <?php echo htmlspecialchars($topic['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="topic-tags">
                                        <span class="tag"><?php echo htmlspecialchars($topic['category_name']); ?></span>
                                    </div>
                                </div>
                                <div class="topic-meta">
                                    <span class="author">
                                        <i class="fas fa-user"></i>
                                        <strong><?php echo htmlspecialchars($topic['author_name']); ?></strong>
                                    </span>
                                    <span class="category">
                                        <i class="fas fa-folder"></i>
                                        <?php echo htmlspecialchars($topic['category_name']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="topic-stats">
                                <div class="stat">
                                    <div class="stat-number"><?php echo number_format($topic['replies_count']); ?></div>
                                    <div class="stat-label">Respostas</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?php echo isset($topic['formatted_views']) ? $topic['formatted_views'] : number_format($topic['views']); ?></div>
                                    <div class="stat-label">Views</div>
                                </div>
                            </div>
                            <div class="topic-last-post">
                                <div class="last-post-user">
                                    <img src="<?php echo !empty($topic['avatar']) ? $topic['avatar'] : site_url('assets/images/avatar.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($topic['author_name']); ?>" 
                                         class="user-avatar-small">
                                </div>
                                <div class="last-post-info">
                                    <div class="last-post-user-name"><?php echo htmlspecialchars($topic['author_name']); ?></div>
                                    <div class="last-post-time"><?php echo $topic['time_ago']; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Botão Anterior -->
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo site_url('forum/' . ($current_page - 1)); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <button class="pagination-btn disabled">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                <?php endif; ?>

                <!-- Páginas -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                // Sempre mostrar primeira página
                if ($start_page > 1):
                ?>
                    <a href="<?php echo site_url('forum/1'); ?>" class="pagination-btn">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Páginas do meio -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <button class="pagination-btn active"><?php echo $i; ?></button>
                    <?php else: ?>
                        <a href="<?php echo site_url('forum/' . $i); ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Sempre mostrar última página -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="pagination-info">...</span>
                    <?php endif; ?>
                    <a href="<?php echo site_url('forum/' . $total_pages); ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <!-- Botão Próximo -->
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo site_url('forum/' . ($current_page + 1)); ?>" class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="pagination-btn disabled">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>



<style>
 /* ========================================
   FORUM STYLES - LEONIDA BRASIL
   ======================================== */

/* Forum Header */
.forum-header {
    background: white;
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-lg);
}

.forum-title h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 var(--spacing-sm) 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.forum-title .fa {
    color: var(--color-primary);
    font-size: 26px;
}

.forum-title p {
    font-size: 14px;
    color: var(--text-medium);
    margin: 0;
    line-height: 1.5;
}

.forum-actions {
    display: flex;
    gap: var(--spacing-sm);
    flex-shrink: 0;
}

.forum-action-btn {
    background: var(--bg-light);
    border: 1px solid var(--border-light);
    color: var(--text-dark);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    text-decoration: none;
}

.forum-action-btn:hover {
    background: white;
    border-color: var(--color-primary);
    color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.forum-action-btn.new-topic {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}

.forum-action-btn.new-topic:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
    color: white;
}

/* Forum Filters */
.forum-filters {
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

.filter-tabs {
    display: flex;
    gap: var(--spacing-xs);
    flex-wrap: wrap;
}

.filter-tab {
    background: var(--bg-light);
    border: 1px solid var(--border-light);
    color: var(--text-medium);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-tab:hover {
    background: white;
    border-color: var(--color-primary);
    color: var(--color-primary);
    transform: translateY(-1px);
}

.filter-tab.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
    box-shadow: var(--shadow-md);
}

.sort-options {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.sort-select {
    background: var(--bg-light);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-sm);
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 11px;
    color: var(--text-dark);
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s ease;
}

.sort-select:focus {
    border-color: var(--color-primary);
}

/* Forum Stats Widget */
.forum-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-sm);
}

.forum-stats-grid .stat-item {
    background: var(--bg-light);
    border-radius: var(--radius-md);
    padding: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    transition: all 0.3s ease;
}

.forum-stats-grid .stat-item:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.forum-stats-grid .stat-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
}

.forum-stats-grid .stat-icon.topics {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
}

.forum-stats-grid .stat-icon.posts {
    background: linear-gradient(135deg, var(--color-secondary), #0099CC);
}

.forum-stats-grid .stat-icon.members {
    background: linear-gradient(135deg, var(--color-accent), #f39c12);
}

.forum-stats-grid .stat-icon.online {
    background: linear-gradient(135deg, var(--color-success), #0abde3);
}

.forum-stats-grid .stat-info {
    flex: 1;
    min-width: 0;
}

.forum-stats-grid .stat-number {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
    line-height: 1;
    margin-bottom: 2px;
}

.forum-stats-grid .stat-label {
    font-size: 10px;
    color: var(--text-medium);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Contributors Widget */
.contributor-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.contributor-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: var(--bg-light);
    border-radius: var(--radius-md);
    transition: all 0.3s ease;
    cursor: pointer;
}

.contributor-item:hover {
    background: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.contributor-rank {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.contributor-rank.gold {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
}

.contributor-rank.silver {
    background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
}

.contributor-rank.bronze {
    background: linear-gradient(135deg, #cd7f32, #d4a574);
}

.contributor-avatar {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-md);
    overflow: hidden;
    flex-shrink: 0;
}

.contributor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.contributor-info {
    flex: 1;
    min-width: 0;
}

.contributor-name {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.contributor-count {
    font-size: 10px;
    color: var(--text-medium);
}

/* Activity Widget */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: var(--bg-light);
    border-radius: var(--radius-md);
    transition: all 0.3s ease;
    cursor: pointer;
}

.activity-item:hover {
    background: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.activity-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: white;
    flex-shrink: 0;
}

.activity-icon.new-topic {
    background: var(--color-success);
}

.activity-icon.reply {
    background: var(--color-info);
}

.activity-icon.like {
    background: var(--color-danger);
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-text {
    font-size: 11px;
    color: var(--text-dark);
    line-height: 1.4;
    margin-bottom: 2px;
}

.activity-text strong {
    font-weight: 600;
    color: var(--color-primary);
}

.activity-time {
    font-size: 9px;
    color: var(--text-light);
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

/* Last Post */
.topic-last-post {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.last-post-user {
    flex-shrink: 0;
}

.user-avatar-small {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-md);
    border: 2px solid var(--border-light);
    object-fit: cover;
}

.last-post-info {
    min-width: 0;
}

.last-post-user-name {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.last-post-time {
    font-size: 9px;
    color: var(--text-light);
}

/* Special Topic States */
.topic-item.pinned {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border-left: 4px solid var(--color-accent);
}

.topic-item.hot {
    background: linear-gradient(135deg, #ffe6e6, #ffcccc);
    border-left: 4px solid var(--color-danger);
}

.topic-item.locked {
    background: var(--bg-lighter);
    opacity: 0.7;
}

.topic-item.locked .topic-title h3 {
    color: var(--text-medium);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .topic-item {
        grid-template-columns: 40px 1fr 80px 100px;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
    }
    
    .forum-header {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-md);
    }
    
    .forum-filters {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .filter-tabs {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .forum-header {
        padding: var(--spacing-lg);
    }
    
    .forum-title h1 {
        font-size: 22px;
    }
    
    .forum-actions {
        width: 100%;
        justify-content: center;
    }
    
    .forum-action-btn {
        flex: 1;
        justify-content: center;
    }
    
    .forum-filters {
        padding: var(--spacing-sm);
    }
    
    .filter-tabs {
        gap: var(--spacing-xs);
    }
    
    .filter-tab {
        padding: var(--spacing-xs) var(--spacing-sm);
        font-size: 10px;
    }
    
    .topic-item {
        grid-template-columns: 1fr;
        gap: var(--spacing-sm);
        padding: var(--spacing-md);
    }
    
    .topic-status {
        position: absolute;
        top: var(--spacing-sm);
        right: var(--spacing-sm);
    }
    
    .status-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    
    .topic-info {
        margin-bottom: var(--spacing-sm);
    }
    
    .topic-stats {
        flex-direction: row;
        justify-content: space-around;
        margin-bottom: var(--spacing-sm);
    }
    
    .topic-stats .stat {
        flex: 1;
        margin: 0 var(--spacing-xs);
    }
    
    .topic-last-post {
        justify-content: center;
    }
    
    .forum-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .contributor-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--spacing-xs);
    }
    
    .activity-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--spacing-xs);
    }
}

@media (max-width: 480px) {
    .forum-header {
        padding: var(--spacing-md);
    }
    
    .forum-title h1 {
        font-size: 18px;
        flex-direction: column;
        gap: var(--spacing-xs);
    }
    
    .forum-title p {
        font-size: 12px;
        text-align: center;
    }
    
    .topic-item {
        padding: var(--spacing-sm);
        position: relative;
    }
    
    .topic-title h3 {
        font-size: 13px;
        white-space: normal;
        line-height: 1.4;
        padding-right: 40px;
    }
    
    .topic-tags {
        margin-bottom: var(--spacing-sm);
    }
    
    .tag {
        font-size: 8px;
        padding: 1px 4px;
    }
    
    .topic-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-xs);
        font-size: 10px;
    }
    
    .topic-stats .stat {
        padding: var(--spacing-xs);
    }
    
    .topic-stats .stat-number {
        font-size: 12px;
    }
    
    .topic-stats .stat-label {
        font-size: 8px;
    }
    
    .user-avatar-small {
        width: 24px;
        height: 24px;
    }
    
    .last-post-user-name {
        font-size: 10px;
    }
    
    .last-post-time {
        font-size: 8px;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.topic-item {
    animation: fadeInUp 0.4s ease-out;
}

.topic-item:nth-child(1) { animation-delay: 0.05s; }
.topic-item:nth-child(2) { animation-delay: 0.1s; }
.topic-item:nth-child(3) { animation-delay: 0.15s; }
.topic-item:nth-child(4) { animation-delay: 0.2s; }
.topic-item:nth-child(5) { animation-delay: 0.25s; }
.topic-item:nth-child(6) { animation-delay: 0.3s; }
.topic-item:nth-child(7) { animation-delay: 0.35s; }
.topic-item:nth-child(8) { animation-delay: 0.4s; }

.forum-header {
    animation: fadeInUp 0.6s ease-out;
}

.forum-filters {
    animation: fadeInUp 0.6s ease-out 0.1s both;
}

.pinned-section {
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.topics-section {
    animation: fadeInUp 0.6s ease-out 0.3s both;
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .topic-item,
    .forum-header,
    .forum-filters,
    .pinned-section,
    .topics-section {
        animation: none;
    }
    
    .status-icon.hot {
        animation: none;
    }
    
    .tag.hot {
        animation: none;
    }
}

/* Focus indicators */
.filter-tab:focus-visible,
.forum-action-btn:focus-visible,
.topic-item:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .topic-item {
        border-width: 2px;
    }
    
    .filter-tab {
        border-width: 2px;
    }
    
    .forum-action-btn {
        border-width: 2px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .forum-header,
    .forum-filters,
    .topics-section,
    .pinned-section {
        background: #2d2d2d;
        border-color: #404040;
    }
    
    .topic-item:hover {
        background: #383838;
    }
    
    .section-header {
        background: #383838;
    }
    
    .filter-tab {
        background: #383838;
        border-color: #555555;
        color: #cccccc;
    }
    
    .filter-tab:hover {
        background: #404040;
        border-color: var(--color-primary);
        color: var(--color-primary);
    }
    
    .status-icon.read {
        background: #555555;
    }
    
    .tag {
        background: #383838;
        color: #cccccc;
    }
    
    .forum-stats-grid .stat-item {
        background: #383838;
    }
    
    .forum-stats-grid .stat-item:hover {
        background: #404040;
    }
    
    .contributor-item {
        background: #383838;
    }
    
    .contributor-item:hover {
        background: #404040;
    }
    
    .activity-item {
        background: #383838;
    }
    
    .activity-item:hover {
        background: #404040;
    }
    
    .topic-stats .stat {
        background: #383838;
    }
}

/* Print styles */
@media print {
    .forum-actions,
    .forum-filters,
    .sidebar-left {
        display: none;
    }
    
    .topic-item {
        break-inside: avoid;
        margin-bottom: var(--spacing-sm);
        border: 1px solid #ccc;
        border-radius: 0;
    }
    
    .topic-item:hover {
        background: transparent;
        transform: none;
    }
    
    .status-icon.hot {
        animation: none;
    }
    
    .tag.hot {
        animation: none;
    }
}

/* Loading states */
.topics-list.loading {
    opacity: 0.6;
    pointer-events: none;
}

.topic-item.loading {
    opacity: 0.5;
    pointer-events: none;
}

/* Empty state */
.topics-empty {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--text-light);
}

.topics-empty .empty-icon {
    font-size: 48px;
    margin-bottom: var(--spacing-md);
    opacity: 0.5;
}

.topics-empty h3 {
    font-size: 18px;
    margin-bottom: var(--spacing-sm);
    color: var(--text-medium);
}

.topics-empty p {
    font-size: 14px;
    margin-bottom: var(--spacing-lg);
}

.topics-empty .empty-action {
    background: var(--color-primary);
    color: white;
    border: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.topics-empty .empty-action:hover {
    background: var(--color-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Utility classes */
.text-center {
    text-align: center;
}

.hidden {
    display: none !important;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Microinteractions */
.topic-item:active {
    transform: translateY(0);
}

.filter-tab:active {
    transform: scale(0.95);
}

.forum-action-btn:active {
    transform: translateY(0) scale(0.95);
}

.tag:active {
    transform: scale(0.9);
}

/* Scrollbar customization for forum */
.topics-list::-webkit-scrollbar {
    width: 4px;
}

.topics-list::-webkit-scrollbar-track {
    background: var(--bg-lighter);
}

.topics-list::-webkit-scrollbar-thumb {
    background: var(--color-primary);
    border-radius: 2px;
}

.topics-list::-webkit-scrollbar-thumb:hover {
    background: var(--color-primary-dark);
}

</style>