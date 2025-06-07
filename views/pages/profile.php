<?php
// Verificar se os dados necessários estão disponíveis
if (!isset($user) || !$user) {
    http_response_code(404);
    include 'views/pages/404.php';
    return;
}

// Determinar se é o próprio perfil ou de outro usuário
$is_own_profile = is_logged_in() && ($_SESSION['user_id'] == $user['id']);
$is_visitor = !is_logged_in();
$current_user_id = $_SESSION['user_id'] ?? 0;

// Verificar se está seguindo (se logado e não é próprio perfil)
$is_following = false;
if (is_logged_in() && !$is_own_profile) {
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) FROM user_followers WHERE user_id = :target_id AND follower_id = :current_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':target_id', $user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':current_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $is_following = $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Erro ao verificar follow: " . $e->getMessage());
    }
}

// Buscar mensagens do mural
$wall_messages = [];
try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT wm.*, u.username, u.display_name, u.avatar
            FROM wall_messages wm
            LEFT JOIN users u ON wm.author_id = u.id
            WHERE wm.user_id = :user_id
            ORDER BY wm.created_at DESC
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
    $stmt->execute();
    $wall_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($wall_messages as &$message) {
        $message['time_ago'] = time_ago($message['created_at']);
        $message['author_avatar'] = $message['avatar'] ?: getDefaultAvatar();
        $message['author_display_name'] = $message['display_name'] ?: $message['username'];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar mensagens do mural: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="<?= site_url() ?>/assets/css/perfil.css">

<!-- Breadcrumb -->
<div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <a href="<?= site_url() ?>" class="breadcrumb-item">
                <i class="fa fa-home"></i>
                Início
            </a>
            <span class="breadcrumb-separator">›</span>
            <a href="<?= site_url('perfil') ?>" class="breadcrumb-item">Perfil</a>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></span>
        </nav>
        
        <?php if (!$is_own_profile && is_logged_in()): ?>
        <div class="page-actions">
            <button class="action-btn report-btn" title="Denunciar usuário" onclick="reportUser(<?= $user['id'] ?>)">
                <i class="fa fa-flag"></i>
                Denunciar usuário
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Profile Container -->
<main class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover">
            <div class="cover-gradient"></div>
        </div>
        
        <div class="profile-main-info">
            <div class="profile-avatar-section">
                <div class="profile-avatar-wrapper">
                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                         alt="<?= htmlspecialchars($user['display_name'] ?: $user['username']) ?>" 
                         class="profile-avatar">
                    
                    <?php if ($is_own_profile): ?>
                    <div class="avatar-upload-overlay" onclick="document.getElementById('avatarInput').click()">
                        <i class="fa fa-camera"></i>
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-info">
                <div class="profile-name-section">
                    <h1 class="profile-username"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></h1>
                    
                    <?php if (!empty($user['bio'])): ?>
                    <div class="profile-title"><?= htmlspecialchars($user['bio']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['website'])): ?>
                    <div class="profile-website">
                        <i class="fa fa-link"></i>
                        <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" rel="nofollow">
                            <?= htmlspecialchars($user['website']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['location'])): ?>
                    <div class="profile-location">
                        <i class="fa fa-map-marker"></i>
                        <?= htmlspecialchars($user['location']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['topics_created'] ?? 0) ?></div>
                        <div class="stat-label">tópicos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="followingCount">0</div>
                        <div class="stat-label">seguindo</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="followersCount">0</div>
                        <div class="stat-label">seguidores</div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($is_own_profile): ?>
                        <a href="<?= site_url('perfil/configuracao') ?>" class="btn btn-primary">
                            <i class="fa fa-cog"></i>
                            Editar perfil
                        </a>
                    <?php elseif (is_logged_in()): ?>
                        <button class="btn btn-primary profile-follow-btn" 
                                id="followBtn" 
                                data-user-id="<?= $user['id'] ?>"
                                data-following="<?= $is_following ? 'true' : 'false' ?>">
                            <i class="fa <?= $is_following ? 'fa-user-check' : 'fa-user-plus' ?>"></i>
                            <?= $is_following ? 'Seguindo' : 'Seguir' ?>
                        </button>
                    <?php else: ?>
                        <a href="<?= site_url('login') ?>" class="btn btn-primary">
                            <i class="fa fa-sign-in"></i>
                            Entrar para seguir
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="profile-content">
        <!-- Wall Section -->
        <div class="profile-wall">
            <div class="wall-header">
                <h2>
                    <i class="fa fa-star"></i>
                    Mural de recados
                </h2>
            </div>
            
            <div class="wall-messages" id="wallMessages">
                <?php if (empty($wall_messages)): ?>
                    <div class="no-messages">
                        <i class="fa fa-comment-slash"></i>
                        <p>Nenhum recado ainda. Seja o primeiro a deixar uma mensagem!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($wall_messages as $message): ?>
                    <div class="wall-post">
                        <div class="wall-post-avatar">
                            <img src="<?= htmlspecialchars($message['author_avatar']) ?>" 
                                 alt="<?= htmlspecialchars($message['author_display_name']) ?>">
                        </div>
                        <div class="wall-post-content">
                            <div class="wall-post-header">
                                <div class="wall-post-author">
                                    <span class="author-name"><?= htmlspecialchars($message['author_display_name']) ?></span>
                                    <?php if ($message['author_id'] == 1): // Admin badge ?>
                                    <div class="author-badge verified">
                                        <i class="fa fa-crown"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="wall-post-time">
                                    <?php if (is_logged_in()): ?>
                                    <span class="report-link" onclick="reportMessage(<?= $message['id'] ?>)">Denunciar</span>
                                    <span>•</span>
                                    <?php endif; ?>
                                    <span><?= $message['time_ago'] ?></span>
                                </div>
                            </div>
                            <div class="wall-post-text">
                                <?= nl2br(htmlspecialchars($message['content'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in() && !$is_own_profile): ?>
            <!-- New Message Form -->
            <div class="wall-new-message">
                <form id="newMessageForm" class="message-form">
                    <input type="hidden" name="target_user_id" value="<?= $user['id'] ?>">
                    <div class="form-group">
                        <textarea id="messageText" name="message" 
                                  placeholder="Deixe um recado para <?= htmlspecialchars($user['display_name'] ?: $user['username']) ?>..." 
                                  rows="3" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-paper-plane"></i>
                            Enviar recado
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif (!is_logged_in()): ?>
            <div class="wall-login-prompt">
                <p><a href="<?= site_url('login') ?>">Faça login</a> para deixar um recado.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Topics Section -->
        <div class="profile-topics">
            <div class="topics-header">
                <h2>
                    <span class="topics-label"><?= $is_own_profile ? 'Meus' : 'Tópicos de ' . htmlspecialchars($user['display_name'] ?: $user['username']) ?></span>
                    <span class="topics-highlight">tópicos</span>
                </h2>
                <div class="topics-search">
                    <input type="text" placeholder="Procurar..." class="topics-search-input" id="topicsSearch">
                    <button class="topics-search-btn" id="searchTopicsBtn">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="topics-grid" id="topicsGrid">
                <?php if (empty($topics)): ?>
                    <div class="no-topics">
                        <i class="fa fa-comments"></i>
                        <p><?= $is_own_profile ? 'Você ainda não criou nenhum tópico.' : 'Este usuário ainda não criou tópicos.' ?></p>
                        <?php if ($is_own_profile): ?>
                        <a href="<?= site_url('forum/criar-topico') ?>" class="btn btn-primary">
                            <i class="fa fa-plus"></i>
                            Criar primeiro tópico
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                    <div class="topic-card" data-title="<?= htmlspecialchars($topic['title']) ?>">
                        <div class="topic-avatar">
                            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                                 alt="<?= htmlspecialchars($user['display_name'] ?: $user['username']) ?>">
                        </div>
                        <div class="topic-content">
                            <h3 class="topic-title">
                                <a href="<?= $topic['url'] ?>"><?= htmlspecialchars($topic['title']) ?></a>
                            </h3>
                            <div class="topic-meta">
                                <div class="topic-author">
                                    <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="">
                                    <span><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></span>
                                    <span class="topic-time"><?= $topic['time_ago'] ?></span>
                                </div>
                                <div class="topic-stats">
                                    <span class="topic-replies">
                                        <i class="fa fa-comment"></i>
                                        <?= number_format($topic['replies_count']) ?>
                                    </span>
                                    <span class="topic-views">
                                        <i class="fa fa-eye"></i>
                                        <?= $topic['formatted_views'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($topic['is_pinned']): ?>
                        <div class="topic-badge pinned">
                            <i class="fa fa-thumbtack"></i>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($topic['is_locked']): ?>
                        <div class="topic-badge locked">
                            <i class="fa fa-lock"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($topics) && count($topics) >= 6): ?>
            <!-- Load More Button -->
            <div class="topics-load-more">
                <button class="btn btn-outline load-more-btn" 
                        id="loadMoreTopics" 
                        data-user-id="<?= $user['id'] ?>" 
                        data-page="2">
                    <i class="fa fa-plus"></i>
                    Carregar mais tópicos
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Sidebar -->
    <div class="profile-sidebar">
        <!-- About Section -->
        <div class="about-section">
            <div class="about-header">
                <h3>Sobre <span class="about-username"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></span></h3>
            </div>
            <div class="about-content">
                <?php if (!empty($user['bio'])): ?>
                    <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                <?php else: ?>
                    <p class="no-bio">
                        <?= $is_own_profile ? 'Adicione uma descrição ao seu perfil.' : 'Este usuário não adicionou uma descrição ainda.' ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-header">
                <h3>
                    <i class="fa fa-chart-bar"></i>
                    Estatísticas
                </h3>
            </div>
            <div class="stats-content">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Membro desde</div>
                        <div class="stat-value"><?= $user['member_since'] ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fa fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Última atividade</div>
                        <div class="stat-value"><?= $user['last_activity'] ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fa fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Pontos totais</div>
                        <div class="stat-value"><?= number_format($stats['experience_points'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fa fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Nível</div>
                        <div class="stat-value"><?= $stats['level'] ?? 1 ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges Section -->
        <?php if (!empty($achievements)): ?>
        <div class="badges-section">
            <div class="badges-header">
                <h3>
                    <i class="fa fa-medal"></i>
                    Emblemas conquistados
                </h3>
            </div>
            <div class="badges-content">
                <?php foreach (array_slice($achievements, 0, 6) as $achievement): ?>
                <div class="badge-item" title="<?= htmlspecialchars($achievement['description']) ?>">
                    <div class="badge-icon active">
                        <i class="<?= htmlspecialchars($achievement['icon']) ?>"></i>
                    </div>
                    <div class="badge-name"><?= htmlspecialchars($achievement['name']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity Section -->
        <?php if (!empty($recent_activity)): ?>
        <div class="activity-section">
            <div class="activity-header">
                <h3>
                    <i class="fa fa-clock"></i>
                    Atividade recente
                </h3>
            </div>
            <div class="activity-list">
                <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                <div class="activity-item">
                    <i class="<?= htmlspecialchars($activity['icon']) ?>"></i>
                    <span><?= htmlspecialchars($activity['content']) ?></span>
                    <div class="activity-time"><?= $activity['time_ago'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Upload de avatar
    window.uploadAvatar = function(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('avatar', input.files[0]);
            
            $.ajax({
                url: '<?= site_url("api/upload-avatar") ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('.profile-avatar').attr('src', response.avatar_url);
                        showNotification(response.message, 'success');
                    } else {
                        showNotification(response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Erro ao fazer upload da imagem', 'error');
                }
            });
        }
    };

    // Follow/Unfollow functionality
    $('#followBtn').click(function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const isFollowing = $btn.data('following') === 'true';
        
        $.ajax({
            url: '<?= site_url("api/follow") ?>',
            type: 'POST',
            data: {
                user_id: userId,
                follow: !isFollowing
            },
            success: function(response) {
                if (response.success) {
                    if (isFollowing) {
                        $btn.removeClass('following')
                            .html('<i class="fa fa-user-plus"></i> Seguir')
                            .data('following', 'false');
                        
                        const followersCount = $('#followersCount');
                        const currentCount = parseInt(followersCount.text().replace(/,/g, ''));
                        followersCount.text((currentCount - 1).toLocaleString());
                    } else {
                        $btn.addClass('following')
                            .html('<i class="fa fa-user-check"></i> Seguindo')
                            .data('following', 'true');
                        
                        const followersCount = $('#followersCount');
                        const currentCount = parseInt(followersCount.text().replace(/,/g, ''));
                        followersCount.text((currentCount + 1).toLocaleString());
                    }
                    
                    $btn.addClass('bounce');
                    setTimeout(() => $btn.removeClass('bounce'), 300);
                } else {
                    showNotification(response.message || 'Erro ao processar solicitação', 'error');
                }
            },
            error: function() {
                showNotification('Erro ao processar solicitação', 'error');
            }
        });
    });

    // New message form submission
    $('#newMessageForm').submit(function(e) {
        e.preventDefault();
        
        const messageText = $('#messageText').val().trim();
        const targetUserId = $('input[name="target_user_id"]').val();
        
        if (!messageText) return;
        
        $.ajax({
            url: '<?= site_url("api/wall-message") ?>',
            type: 'POST',
            data: {
                target_user_id: targetUserId,
                message: messageText
            },
            success: function(response) {
                if (response.success) {
                    // Criar nova mensagem
                    const newMessage = $(`
                        <div class="wall-post new-message" style="display: none;">
                            <div class="wall-post-avatar">
                                <img src="${response.author_avatar}" alt="${response.author_name}">
                            </div>
                            <div class="wall-post-content">
                                <div class="wall-post-header">
                                    <div class="wall-post-author">
                                        <span class="author-name">${response.author_name}</span>
                                    </div>
                                    <div class="wall-post-time">
                                        <span class="report-link" onclick="reportMessage(0)">Denunciar</span>
                                        <span>•</span>
                                        <span>agora</span>
                                    </div>
                                </div>
                                <div class="wall-post-text">${messageText.replace(/\n/g, '<br>')}</div>
                            </div>
                        </div>
                    `);
                    
                    // Remover mensagem de "nenhum recado" se existir
                    $('.no-messages').remove();
                    
                    // Adicionar ao mural
                    $('#wallMessages').prepend(newMessage);
                    newMessage.slideDown(300);
                    
                    // Limpar formulário
                    $('#messageText').val('');
                    
                    showNotification('Recado enviado com sucesso!', 'success');
                } else {
                    showNotification(response.message || 'Erro ao enviar recado', 'error');
                }
            },
            error: function() {
                showNotification('Erro ao enviar recado', 'error');
            }
        });
    });

    // Search topics functionality
    $('#topicsSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.topic-card').each(function() {
            const topicTitle = $(this).data('title').toLowerCase();
            
            if (topicTitle.includes(searchTerm)) {
                $(this).fadeIn(200);
            } else {
                $(this).fadeOut(200);
            }
        });
    });

    // Load more topics
    $('#loadMoreTopics').click(function() {
        const $btn = $(this);
        const userId = $btn.data('user-id');
        const page = $btn.data('page');
        const originalText = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Carregando...')
           .prop('disabled', true);
        
        $.ajax({
            url: '<?= site_url("api/user-topics") ?>',
            type: 'GET',
            data: {
                user_id: userId,
                page: page
            },
            success: function(response) {
                if (response.success && response.topics.length > 0) {
                    response.topics.forEach(function(topic) {
                        const topicCard = $(`
                            <div class="topic-card" data-title="${topic.title}">
                                <div class="topic-avatar">
                                    <img src="${topic.author_avatar}" alt="${topic.author_name}">
                                </div>
                                <div class="topic-content">
                                    <h3 class="topic-title">
                                        <a href="${topic.url}">${topic.title}</a>
                                    </h3>
                                    <div class="topic-meta">
                                        <div class="topic-author">
                                            <img src="${topic.author_avatar}" alt="">
                                            <span>${topic.author_name}</span>
                                            <span class="topic-time">${topic.time_ago}</span>
                                        </div>
                                        <div class="topic-stats">
                                            <span class="topic-replies">
                                                <i class="fa fa-comment"></i>
                                                ${topic.replies_count}
                                            </span>
                                            <span class="topic-views">
                                                <i class="fa fa-eye"></i>
                                                ${topic.formatted_views}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        $('#topicsGrid').append(topicCard);
                    });
                    
                    $btn.data('page', page + 1);
                    $btn.html(originalText).prop('disabled', false);
                } else {
                    $btn.hide();
                    showNotification('Todos os tópicos foram carregados!', 'info');
                }
            },
            error: function() {
                $btn.html(originalText).prop('disabled', false);
                showNotification('Erro ao carregar mais tópicos', 'error');
            }
        });
    });

    // Report functions
    window.reportUser = function(userId) {
        if (confirm('Tem certeza que deseja denunciar este usuário?')) {
            // Implementar sistema de denúncia
            showNotification('Denúncia enviada. Obrigado pelo feedback.', 'success');
        }
    };

    window.reportMessage = function(messageId) {
        if (confirm('Tem certeza que deseja denunciar esta mensagem?')) {
            // Implementar sistema de denúncia
            showNotification('Denúncia enviada. Obrigado pelo feedback.', 'success');
        }
    };

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="notification ${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--color-success)' : 
                            type === 'error' ? 'var(--color-danger)' : 
                            'var(--color-primary)'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                font-size: 14px;
                font-weight: 500;
                max-width: 300px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            ">
                <i class="fa ${type === 'success' ? 'fa-check' : 
                             type === 'error' ? 'fa-times' : 
                             'fa-info'}"></i>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.css('transform', 'translateX(0)');
        }, 100);
        
        setTimeout(() => {
            notification.css('transform', 'translateX(100%)');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Animações de entrada
    $('.profile-header, .profile-wall, .profile-topics, .profile-sidebar > *').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(30px)'
        }).delay(index * 100).animate({
            'opacity': '1'
        }, 600).css('transform', 'translateY(0)');
    });
});

// CSS adicional para funcionalidades
const style = document.createElement('style');
style.textContent = `
    .bounce {
        animation: bounce 0.3s ease;
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .new-message {
        border-left: 4px solid var(--color-success);
        background: rgba(34, 197, 94, 0.05);
    }
    
    .avatar-upload-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        opacity: 0;
        cursor: pointer;
        transition: opacity 0.3s ease;
        color: white;
        font-size: 18px;
    }
    
    .profile-avatar-wrapper:hover .avatar-upload-overlay {
        opacity: 1;
    }
    
    .no-messages, .no-topics {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-medium);
    }
    
    .no-messages i, .no-topics i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .no-bio {
        color: var(--text-medium);
        font-style: italic;
    }
    
    .wall-login-prompt {
        text-align: center;
        padding: 20px;
        background: var(--bg-light);
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .topic-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: white;
    }
    
    .topic-badge.pinned {
        background: var(--color-warning);
    }
    
    .topic-badge.locked {
        background: var(--color-danger);
    }
    
    .report-link {
        cursor: pointer;
        color: var(--text-medium);
        font-size: 12px;
        transition: color 0.2s ease;
    }
    
    .report-link:hover {
        color: var(--color-danger);
    }
    
    .profile-follow-btn.following {
        background: var(--color-success) !important;
        border-color: var(--color-success) !important;
    }
    
    @media (max-width: 768px) {
        .profile-container {
            display: flex;
            flex-direction: column;
        }
        
        .profile-sidebar {
            order: -1;
            margin-bottom: 20px;
        }
        
        .profile-stats {
            justify-content: center;
            gap: 20px;
        }
        
        .topics-grid {
            grid-template-columns: 1fr;
        }
    }
`;
document.head.appendChild(style);
</script>