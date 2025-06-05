<link rel="stylesheet" href="<?= site_url() ?>/assets/css/forum.css">

<div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (isset($crumb['current']) && $crumb['current']): ?>
                    <span class="breadcrumb-item current">
                        <?php if ($crumb['icon']): ?>
                            <i class="<?= $crumb['icon'] ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($crumb['name']) ?>
                    </span>
                <?php else: ?>
                    <a href="<?= $crumb['url'] ?>" class="breadcrumb-item">
                        <?php if ($crumb['icon']): ?>
                            <i class="<?= $crumb['icon'] ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($crumb['name']) ?>
                    </a>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        
        <div class="page-actions">
            <button class="action-btn info-btn" title="Informações">
                <i class="fa fa-info-circle"></i>
                Informações
            </button>
            <button class="action-btn help-btn" title="Ajuda">
                <i class="fa fa-question-circle"></i>
                Ajuda
            </button>
            <button class="action-btn report-btn" title="Relatar erro">
                <i class="fa fa-flag"></i>
                Relatar erro
            </button>
        </div>
    </div>
</div>

<!-- Main Container -->
<main class="forum-container">
    <div class="forum-wrapper">
        <!-- Topic Header -->
        <div class="topic-header">
            <div class="topic-info">
                <div class="topic-status <?= $topic['is_closed'] ? 'closed' : ($topic['is_pinned'] ? 'pinned' : 'open') ?>">
                    <?php if ($topic['is_closed']): ?>
                        <i class="fa fa-lock"></i>
                        Fórum: <?= htmlspecialchars($topic['title']) ?>
                    <?php elseif ($topic['is_pinned']): ?>
                        <i class="fa fa-thumbtack"></i>
                        Fórum: <?= htmlspecialchars($topic['title']) ?>
                    <?php else: ?>
                        <i class="fa fa-comments"></i>
                        Fórum: <?= htmlspecialchars($topic['title']) ?>
                    <?php endif; ?>
                </div>
                <div class="topic-actions">
                    <button class="btn btn-danger btn-small">
                        <i class="fa fa-flag"></i>
                        Denunciar
                    </button>
                    <div class="topic-time">
                        <i class="fa fa-clock"></i>
                        <?= $topic['time_ago'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forum Content -->
        <div class="forum-content">
            <!-- User Sidebar -->
            <aside class="user-sidebar">
                <div class="user-card">
                    <div class="user-header">
                        <div class="username">
                            <?= htmlspecialchars($topic['author_name']) ?>
                            <?php if ($topic['is_verified']): ?>
                                <i class="fa fa-check-circle verified" title="Usuário Verificado"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-title"><?= htmlspecialchars($topic['user_title']) ?></div>
                    </div>
                    
                    <div class="user-avatar">
                        <img src="<?= htmlspecialchars($topic['avatar']) ?>" alt="<?= htmlspecialchars($topic['author_name']) ?>">
                        <div class="user-status online" title="Online"></div>
                    </div>
                    
                    <?php 
                    $author_message_count = getUserMessageCount($topic['author_id'] ?? 0);
                    $author_badges = getUserBadges($topic['level'], $author_message_count); 
                    ?>
                    <?php if (!empty($author_badges)): ?>
                        <div class="user-badges">
                            <?php foreach ($author_badges as $badge): ?>
                                <div class="badge <?= $badge['class'] ?>" title="<?= htmlspecialchars($badge['name']) ?>">
                                    <i class="<?= $badge['icon'] ?>"></i>
                                    <?= htmlspecialchars($badge['name']) ?>
                                    <?php if ($badge['level']): ?>
                                        <span class="badge-level"><?= htmlspecialchars($badge['level']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= number_format($author_message_count) ?></span>
                            <span class="stat-label">mensagens</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Post Content -->
            <div class="post-content">
                <div class="post-header">
                    <div class="post-actions">
                        <button class="post-action-btn like-btn" title="Curtir">
                            <i class="fa fa-thumbs-up"></i>
                        </button>
                        <?php if ($topic['is_pinned']): ?>
                            <button class="post-action-btn fixed-btn active" title="Fixo">
                                Fixo
                            </button>
                        <?php endif; ?>
                        <button class="post-action-btn quote-btn" title="Citar">
                            <i class="fa fa-quote-right"></i>
                        </button>
                        <button class="post-action-btn check-btn" title="Marcar como resolvido">
                            <i class="fa fa-check"></i>
                        </button>
                    </div>
                </div>

                <div class="post-body">
                    <div class="post-text">
                        <?= nl2br(htmlspecialchars($topic['content'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topic Status Notice -->
        <?php if ($topic['is_closed']): ?>
            <div class="topic-notice closed">
                <div class="notice-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="notice-content">
                    <strong>Oops!</strong>
                    <span>Esse tópico foi fechado.</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Replies Section -->
        <?php if (!empty($replies)): ?>
            <?php foreach ($replies as $reply): ?>
                <div class="reply-section">
                    <!-- User Reply Card -->
                    <aside class="reply-user-sidebar">
                        <div class="user-card">
                            <div class="user-header">
                                <div class="username">
                                    <?= htmlspecialchars($reply['author_name']) ?>
                                    <?php if ($reply['is_verified']): ?>
                                        <i class="fa fa-check-circle verified" title="Usuário Verificado"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="user-title"><?= htmlspecialchars($reply['user_title']) ?></div>
                            </div>
                            
                            <div class="user-avatar">
                                <img src="<?= htmlspecialchars($reply['avatar']) ?>" alt="<?= htmlspecialchars($reply['author_name']) ?>">
                                <div class="user-status <?= $reply['user_status'] ?>" 
                                     title="<?= $reply['user_status'] === 'online' ? 'Online' : 'Offline' ?>"></div>
                            </div>
                            
                            <?php if (!empty($reply['user_badges'])): ?>
                                <div class="user-badges">
                                    <?php foreach ($reply['user_badges'] as $badge): ?>
                                        <div class="badge <?= $badge['class'] ?>" title="<?= htmlspecialchars($badge['name']) ?>">
                                            <i class="<?= $badge['icon'] ?>"></i>
                                            <?= htmlspecialchars($badge['name']) ?>
                                            <?php if ($badge['level']): ?>
                                                <span class="badge-level"><?= htmlspecialchars($badge['level']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= number_format($reply['message_count']) ?></span>
                                    <span class="stat-label">mensagens</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Reply Content -->
                    <div class="reply-content">
                        <div class="reply-header">
                            <div class="reply-info">
                                <span class="reply-title">Re: <?= htmlspecialchars($topic['title']) ?></span>
                                <div class="reply-actions">
                                    <button class="btn btn-danger btn-small">
                                        <i class="fa fa-flag"></i>
                                        Denunciar
                                    </button>
                                    <button class="btn btn-warning btn-small">
                                        <i class="fa fa-quote-right"></i>
                                        Citar
                                    </button>
                                    <div class="reply-time">
                                        <i class="fa fa-clock"></i>
                                        <?= $reply['time_ago'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reply-body">
                            <div class="reply-text">
                                <?= nl2br(htmlspecialchars($reply['content'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Pagination -->
        <div class="forum-pagination">
            <div class="pagination">
                <button class="pagination-btn disabled">
                    <i class="fa fa-angle-left"></i>
                </button>
                <button class="pagination-btn active">1</button>
                <button class="pagination-btn disabled">
                    <i class="fa fa-angle-right"></i>
                </button>
            </div>
        </div>

        <!-- Reply Form -->
        <?php if ($can_reply): ?>
            <div class="reply-form-section">
                <div class="reply-form-header">
                    <div class="form-title">
                        <i class="fa fa-reply"></i>
                        Escrever comentário
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-secondary">Regras do Fórum</button>
                        <button class="btn btn-warning">BBCode</button>
                    </div>
                </div>

                <div class="reply-form-content">
                    <div class="form-user-avatar">
                        <?php if ($current_user): ?>
                            <img src="<?= htmlspecialchars($current_user['avatar'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjZGRkIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSIjOTk5Ii8+CjxwYXRoIGQ9Ik0yNSA4MEMyNSA2Ny41IDM2LjUgNTcgNTAgNTdTNzUgNjcuNSA3NSA4MEgyNVoiIGZpbGw9IiM5OTkiLz4KPC9zdmc+') ?>" alt="<?= htmlspecialchars($current_user['display_name'] ?: $current_user['username']) ?>">
                        <?php else: ?>
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjZGRkIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSIjOTk5Ii8+CjxwYXRoIGQ9Ik0yNSA4MEMyNSA2Ny41IDM2LjUgNTcgNTAgNTdTNzUgNjcuNSA3NSA4MEgyNVoiIGZpbGw9IiM5OTkiLz4KPC9zdmc+" alt="Usuário">
                        <?php endif; ?>
                    </div>

                    <form class="form-editor" method="POST" action="<?= site_url('api/forum') ?>">
                        <input type="hidden" name="type" value="reply">
                        <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="editor-toolbar">
                            <button type="button" class="editor-btn" title="Negrito" data-tag="b">
                                <i class="fa fa-bold"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Itálico" data-tag="i">
                                <i class="fa fa-italic"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Sublinhado" data-tag="u">
                                <i class="fa fa-underline"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Riscado" data-tag="s">
                                <i class="fa fa-strikethrough"></i>
                            </button>
                            <div class="toolbar-separator"></div>
                            <button type="button" class="editor-btn" title="Link" data-tag="url">
                                <i class="fa fa-link"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Imagem" data-tag="img">
                                <i class="fa fa-image"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Emoji" data-tag="emoji">
                                <i class="fa fa-smile"></i>
                            </button>
                            <div class="toolbar-separator"></div>
                            <button type="button" class="editor-btn preview-btn" title="Live Preview">
                                Live Preview
                            </button>
                        </div>

                        <textarea name="content" class="editor-textarea" placeholder="Escreva um comentário..." required></textarea>

                        <div class="editor-footer">
                            <button type="button" class="btn btn-warning">
                                <i class="fa fa-smile"></i>
                                Emojis
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-paper-plane"></i>
                                Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif (!$is_logged_in): ?>
            <div class="login-required-notice">
                <div class="notice-content">
                    <i class="fa fa-info-circle"></i>
                    <span>Você precisa <a href="<?= site_url('login') ?>">fazer login</a> para responder a este tópico.</span>
                </div>
            </div>
        <?php elseif ($topic['is_closed']): ?>
            <div class="topic-closed-notice">
                <div class="notice-content">
                    <i class="fa fa-lock"></i>
                    <span>Este tópico foi fechado e não aceita mais respostas.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>


<script>
window.forumData = {
    topicId: <?= $topic['id'] ?>,
    topicSlug: '<?= htmlspecialchars($topic['slug']) ?>',
    isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
    canReply: <?= $can_reply ? 'true' : 'false' ?>,
    currentUser: <?= $current_user ? json_encode($current_user) : 'null' ?>,
    replyCount: <?= $reply_count ?>
};
</script>

<script src="<?= site_url() ?>assets/js/forum.js"></script>