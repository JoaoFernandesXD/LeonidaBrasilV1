<?php
/**
 * views/pages/forum-topic.php
 * Página de tópico único do fórum Leonida Brasil
 * 
 * Requisitos atendidos:
 * - Renderização inicial via PHP com dados sanitizados
 * - Funcionalidades AJAX usando API existente
 * - Suporte a BBCode processado no backend
 * - Código simples e funcional
 * - Compatível com ForumController sem BaseController
 */

// Verificar se tópico foi carregado
if (!isset($topic) || empty($topic)) {
    header("HTTP/1.1 404 Not Found");
    include 'views/pages/404.php';
    exit;
}
?>

<link rel="stylesheet" href="<?= site_url() ?>/assets/css/forum.css">

<!-- Breadcrumb Container -->
<div class="breadcrumb-container">
    <div class="breadcrumb-content">
        <nav class="breadcrumb">
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (isset($crumb['current']) && $crumb['current']): ?>
                    <span class="breadcrumb-item current">
                        <?php if ($crumb['icon']): ?>
                            <i class="<?= htmlspecialchars($crumb['icon']) ?>"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($crumb['name']) ?>
                    </span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="breadcrumb-item">
                        <?php if ($crumb['icon']): ?>
                            <i class="<?= htmlspecialchars($crumb['icon']) ?>"></i>
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
            <button class="action-btn report-btn" title="Relatar erro" 
                    onclick="reportContent('topic', <?= intval($topic['id']) ?>)">
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
                    <button class="btn btn-danger btn-small" 
                            onclick="reportContent('topic', <?= intval($topic['id']) ?>)">
                        <i class="fa fa-flag"></i>
                        Denunciar
                    </button>
                    <div class="topic-time">
                        <i class="fa fa-clock"></i>
                        <?= htmlspecialchars($topic['time_ago']) ?>
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
                        <img src="<?= htmlspecialchars($topic['avatar']) ?>" 
                             alt="<?= htmlspecialchars($topic['author_name']) ?>"
                             onerror="this.src='<?= DEFAULT_AVATAR ?>'">
                        <div class="user-status <?= $topic['user_status'] ?>" 
                             title="<?= $topic['user_status'] === 'online' ? 'Online' : 'Offline' ?>"></div>
                    </div>
                    
                    <?php 
                    // Usar message_count e badges fornecidos pelo controlador
                    $author_message_count = $topic['message_count'] ?? 0;
                    $author_badges = $topic['user_badges'] ?? []; 
                    ?>
                    <?php if (!empty($author_badges)): ?>
                        <div class="user-badges">
                            <?php foreach ($author_badges as $badge): ?>
                                <div class="badge <?= htmlspecialchars($badge['class']) ?>" 
                                     title="<?= htmlspecialchars($badge['name']) ?>">
                                    <i class="<?= htmlspecialchars($badge['icon']) ?>"></i>
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
                        <button class="post-action-btn like-btn" title="Curtir"
                                onclick="likeContent('topic', <?= intval($topic['id']) ?>)">
                            <i class="fa fa-thumbs-up"></i>
                        </button>
                        <?php if ($topic['is_pinned']): ?>
                            <button class="post-action-btn fixed-btn active" title="Fixo">
                                Fixo
                            </button>
                        <?php endif; ?>
                        <button class="post-action-btn quote-btn" title="Citar"
                                onclick="quoteContent('<?= htmlspecialchars($topic['author_name']) ?>', `<?= htmlspecialchars(strip_tags($topic['content'])) ?>`)">
                            <i class="fa fa-quote-right"></i>
                        </button>
                        <?php if (is_logged_in() && has_permission(3)): ?>
                            <button class="post-action-btn check-btn" title="Marcar como resolvido">
                                <i class="fa fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="post-body">
                    <div class="post-text">
                        <?= $topic['content'] ?>
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
                    <div class="reply-section" data-reply-id="<?= intval($reply['id']) ?>">
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
                                    <img src="<?= htmlspecialchars($reply['avatar']) ?>" 
                                         alt="<?= htmlspecialchars($reply['author_name']) ?>"
                                         onerror="this.src='<?= DEFAULT_AVATAR ?>'">
                                    <div class="user-status <?= $reply['user_status'] ?>" 
                                         title="<?= $reply['user_status'] === 'online' ? 'Online' : 'Offline' ?>"></div>
                                </div>
                                
                                <?php if (!empty($reply['user_badges'])): ?>
                                    <div class="user-badges">
                                        <?php foreach ($reply['user_badges'] as $badge): ?>
                                            <div class="badge <?= htmlspecialchars($badge['class']) ?>" 
                                                 title="<?= htmlspecialchars($badge['name']) ?>">
                                                <i class="<?= htmlspecialchars($badge['icon']) ?>"></i>
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
                                        <button class="btn btn-danger btn-small"
                                                onclick="reportContent('reply', <?= intval($reply['id']) ?>)">
                                            <i class="fa fa-flag"></i>
                                            Denunciar
                                        </button>
                                        <button class="btn btn-warning btn-small"
                                                onclick="quoteContent('<?= htmlspecialchars($reply['author_name']) ?>', `<?= htmlspecialchars(strip_tags($reply['content'])) ?>`)">
                                            <i class="fa fa-quote-right"></i>
                                            Citar
                                        </button>
                                        <div class="reply-time">
                                            <i class="fa fa-clock"></i>
                                            <?= htmlspecialchars($reply['time_ago']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="reply-body">
                                <div class="reply-text">
                                    <?= $reply['content'] ?>
                                </div>
                                <div class="reply-actions-bottom">
                                    <button class="like-reply-btn" 
                                            onclick="likeReply(<?= intval($reply['id']) ?>)"
                                            title="Curtir resposta">
                                        <i class="fa fa-thumbs-up"></i>
                                        <span class="like-count"><?= intval($reply['likes']) ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        

        <!-- Pagination for Replies -->
        <div class="forum-pagination" id="pagination-container">
            <div class="pagination">
                <button class="pagination-btn disabled" id="prev-page">
                    <i class="fa fa-angle-left"></i>
                </button>
                <button class="pagination-btn active" id="current-page">1</button>
                <button class="pagination-btn disabled" id="next-page">
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
                        <button class="btn btn-secondary" onclick="showBBCodeHelp()">BBCode</button>
                        <button class="btn btn-secondary" onclick="showForumRules()">Regras do Fórum</button>
                    </div>
                </div>

                <div class="reply-form-content">
                    <div class="form-user-avatar">
                        <?php if ($current_user): ?>
                            <img src="<?= htmlspecialchars($current_user['avatar'] ?? DEFAULT_AVATAR) ?>" 
                                 alt="<?= htmlspecialchars($current_user['display_name'] ?: $current_user['username']) ?>">
                        <?php else: ?>
                            <img src="<?= DEFAULT_AVATAR ?>" alt="Usuário">
                        <?php endif; ?>
                    </div>

                    <form class="form-editor" id="reply-form">
                        <input type="hidden" name="type" value="reply">
                        <input type="hidden" name="topic_id" value="<?= intval($topic['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <div class="editor-toolbar">
                            <button type="button" class="editor-btn" title="Negrito" onclick="insertBBCode('b')">
                                <i class="fa fa-bold"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Itálico" onclick="insertBBCode('i')">
                                <i class="fa fa-italic"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Sublinhado" onclick="insertBBCode('u')">
                                <i class="fa fa-underline"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Link" onclick="insertBBCode('url')">
                                <i class="fa fa-link"></i>
                            </button>
                            <div class="toolbar-separator"></div>
                            <button type="button" class="editor-btn" title="Código" onclick="insertBBCode('code')">
                                <i class="fa fa-code"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Citação" onclick="insertBBCode('quote')">
                                <i class="fa fa-quote-left"></i>
                            </button>
                            <button type="button" class="editor-btn" title="Imagem" onclick="insertImageBBCode()">
                                <i class="fa fa-image"></i>
                            </button>
                            <div class="toolbar-separator"></div>
                            <button type="button" class="editor-btn preview-btn" onclick="togglePreview()">
                                Live Preview
                            </button>
                        </div>

                        <div class="editor-container">
                            <textarea name="content" id="reply-content" class="editor-textarea" 
                                      placeholder="Escreva um comentário..." required></textarea>
                            <div id="preview-content" class="preview-content" style="display: none;"></div>
                        </div>

                        <div class="editor-footer">
                            <div class="editor-info">
                                <small>Você pode usar BBCode: [b], [i], [u], [url], [code], [quote], [img]</small>
                            </div>
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

<!-- Modais -->
<div id="bbcode-help-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajuda do BBCode</h3>
            <button class="modal-close" onclick="closeBBCodeHelp()">×</button>
        </div>
        <div class="modal-body">
            <table class="bbcode-help-table">
                <tr><th>BBCode</th><th>Resultado</th></tr>
                <tr><td>[b]texto[/b]</td><td><strong>texto</strong></td></tr>
                <tr><td>[i]texto[/i]</td><td><em>texto</em></td></tr>
                <tr><td>[u]texto[/u]</td><td><u>texto</u></td></tr>
                <tr><td>[url=http://example.com]texto[/url]</td><td><a href="http://example.com">texto</a></td></tr>
                <tr><td>[code]código[/code]</td><td><code>código</code></td></tr>
                <tr><td>[quote]citação[/quote]</td><td>Bloco de citação</td></tr>
                <tr><td>[img]URL[/img]</td><td>Exibe imagem (URL deve ser segura)</td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
// Dados globais para JavaScript
window.forumData = {
    topicId: <?= intval($topic['id']) ?>,
    topicSlug: '<?= htmlspecialchars($topic['slug']) ?>',
    isLoggedIn: <?= $is_logged_in ? 'true' : 'false' ?>,
    canReply: <?= $can_reply ? 'true' : 'false' ?>,
    currentUser: <?= $current_user ? json_encode($current_user) : 'null' ?>,
    replyCount: <?= $reply_count ?>,
    apiUrl: '<?= site_url('api/forum.php') ?>',
    csrfToken: '<?= htmlspecialchars($csrf_token) ?>'
};

// Funções AJAX para interação com a API
function likeReply(replyId) {
    if (!window.forumData.isLoggedIn) {
        alert('Você precisa estar logado para curtir respostas.');
        return;
    }
    
    fetch(`${window.forumData.apiUrl}?action=like_reply&id=${replyId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeButton = document.querySelector(`[data-reply-id="${replyId}"] .like-reply-btn .like-count`);
            if (likeButton) {
                likeButton.textContent = parseInt(likeButton.textContent) + 1;
            }
            showNotification('Resposta curtida!', 'success');
        } else {
            showNotification(data.error || 'Erro ao curtir resposta', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro de conexão', 'error');
    });
}

function likeContent(type, contentId) {
    if (!window.forumData.isLoggedIn) {
        alert('Você precisa estar logado para curtir conteúdo.');
        return;
    }
    showNotification('Funcionalidade em desenvolvimento', 'info');
}

function reportContent(type, contentId) {
    if (!window.forumData.isLoggedIn) {
        alert('Você precisa estar logado para denunciar conteúdo.');
        return;
    }
    
    const reason = prompt('Motivo da denúncia:');
    if (!reason) return;
    
    fetch(window.forumData.apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'report',
            type: type,
            content_id: contentId,
            reason: reason,
            csrf_token: window.forumData.csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Denúncia enviada com sucesso', 'success');
        } else {
            showNotification(data.error || 'Erro ao enviar denúncia', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro de conexão', 'error');
    });
}

function quoteContent(author, content) {
    const textarea = document.getElementById('reply-content');
    if (!textarea) return;
    
    const maxQuoteLength = 200;
    let quotedContent = content.length > maxQuoteLength 
        ? content.substring(0, maxQuoteLength) + '...' 
        : content;
    
    const quoteText = `[quote="${sanitizeForBBCode(author)}"]${sanitizeForBBCode(quotedContent)}[/quote]\n\n`;
    
    const cursorPos = textarea.selectionStart;
    const textBefore = textarea.value.substring(0, cursorPos);
    const textAfter = textarea.value.substring(cursorPos);
    
    textarea.value = textBefore + quoteText + textAfter;
    textarea.focus();
    textarea.setSelectionRange(cursorPos + quoteText.length, cursorPos + quoteText.length);
}

function sanitizeForBBCode(text) {
    return text.replace(/[\[\]]/g, '').replace(/"/g, "'").trim();
}

function insertBBCode(tag) {
    const textarea = document.getElementById('reply-content');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    let insertText;
    if (selectedText) {
        insertText = `[${tag}]${selectedText}[/${tag}]`;
    } else {
        insertText = `[${tag}][/${tag}]`;
    }
    
    textarea.value = textarea.value.substring(0, start) + insertText + textarea.value.substring(end);
    
    if (selectedText) {
        textarea.setSelectionRange(start + insertText.length, start + insertText.length);
    } else {
        textarea.setSelectionRange(start + tag.length + 2, start + tag.length + 2);
    }
    
    textarea.focus();
}

function insertImageBBCode() {
    const textarea = document.getElementById('reply-content');
    if (!textarea) return;
    
    const imageUrl = prompt('URL da imagem (deve ser https:// e terminar com .jpg, .png, .gif ou .webp):');
    if (!imageUrl) return;
    
    if (!imageUrl.match(/^https:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
        alert('URL inválida. Use apenas URLs HTTPS que terminem com extensões de imagem válidas.');
        return;
    }
    
    const insertText = `[img]${imageUrl}[/img]`;
    const start = textarea.selectionStart;
    
    textarea.value = textarea.value.substring(0, start) + insertText + textarea.value.substring(start);
    textarea.setSelectionRange(start + insertText.length, start + insertText.length);
    textarea.focus();
}

function togglePreview() {
    const textarea = document.getElementById('reply-content');
    const preview = document.getElementById('preview-content');
    const button = document.querySelector('.preview-btn');
    
    if (!textarea || !preview || !button) return;
    
    if (preview.style.display === 'none') {
        const content = textarea.value;
        preview.innerHTML = processBBCodePreview(content);
        preview.style.display = 'block';
        textarea.style.display = 'none';
        button.textContent = 'Editar';
        button.classList.add('active');
    } else {
        preview.style.display = 'none';
        textarea.style.display = 'block';
        button.textContent = 'Live Preview';
        button.classList.remove('active');
        textarea.focus();
    }
}

function processBBCodePreview(content) {
    content = content.replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
    
    content = content.replace(/\[b\](.*?)\[\/b\]/gi, '<strong>$1</strong>')
                    .replace(/\[i\](.*?)\[\/i\]/gi, '<em>$1</em>')
                    .replace(/\[u\](.*?)\[\/u\]/gi, '<u>$1</u>')
                    .replace(/\[url=([^\]]*?)\](.*?)\[\/url\]/gi, '<a href="$1" target="_blank">$2</a>')
                    .replace(/\[url\](.*?)\[\/url\]/gi, '<a href="$1" target="_blank">$1</a>')
                    .replace(/\[code\](.*?)\[\/code\]/gi, '<pre><code>$1</code></pre>')
                    .replace(/\[quote\](.*?)\[\/quote\]/gi, '<blockquote>$1</blockquote>')
                    .replace(/\[quote="([^"]+)"\](.*?)\[\/quote\]/gi, '<blockquote><cite>$1 disse:</cite><p>$2</p></blockquote>')
                    .replace(/\[img\](.*?)\[\/img\]/gi, function(match, url) {
                        if (url.match(/^https:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i)) {
                            return `<img src="${url}" alt="Imagem" style="max-width: 100%; height: auto;" loading="lazy" />`;
                        }
                        return '[img]URL inválida[/img]';
                    });
    
    content = content.replace(/\n/g, '<br>');
    
    return content || '<em>Preview aparecerá aqui...</em>';
}

function showBBCodeHelp() {
    const modal = document.getElementById('bbcode-help-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeBBCodeHelp() {
    const modal = document.getElementById('bbcode-help-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function showForumRules() {
    alert('Regras do Fórum:\n\n1. Seja respeitoso com outros usuários\n2. Não faça spam ou posts desnecessários\n3. Use BBCode apropriadamente\n4. Não poste conteúdo ofensivo\n5. Mantenha discussões relevantes ao tópico');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                z-index: 1000;
                max-width: 400px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }
            .notification.show {
                opacity: 1;
                transform: translateX(0);
            }
            .notification-success { background-color: #28a745; }
            .notification-error { background-color: #dc3545; }
            .notification-info { background-color: #17a2b8; }
            .notification-warning { background-color: #ffc107; color: #212529; }
            .notification-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .notification-close {
                background: none;
                border: none;
                color: inherit;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitReply();
        });
    }
    
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('bbcode-help-modal');
        if (e.target === modal) {
            closeBBCodeHelp();
        }
    });
    
    const textarea = document.getElementById('reply-content');
    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case 'b':
                        e.preventDefault();
                        insertBBCode('b');
                        break;
                    case 'i':
                        e.preventDefault();
                        insertBBCode('i');
                        break;
                    case 'u':
                        e.preventDefault();
                        insertBBCode('u');
                        break;
                }
            }
        });
    }
});

function submitReply() {
    if (!window.forumData.isLoggedIn) {
        alert('Você precisa estar logado para responder.');
        return;
    }
    
    const form = document.getElementById('reply-form');
    const textarea = document.getElementById('reply-content');
    const submitButton = form.querySelector('button[type="submit"]');
    
    if (!textarea.value.trim()) {
        alert('Por favor, escreva uma resposta.');
        textarea.focus();
        return;
    }
    
    if (textarea.value.length < 10) {
        alert('A resposta deve ter pelo menos 10 caracteres.');
        textarea.focus();
        return;
    }
    
    if (textarea.value.length > 10000) {
        alert('A resposta não pode ter mais de 10.000 caracteres.');
        textarea.focus();
        return;
    }
    
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enviando...';
    
    const formData = {
        type: 'reply',
        topic_id: window.forumData.topicId,
        content: textarea.value.trim(),
        csrf_token: window.forumData.csrfToken
    };
    
    fetch(window.forumData.apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Resposta enviada com sucesso!', 'success');
            textarea.value = '';
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.error || 'Erro ao enviar resposta', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro de conexão ao enviar resposta', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fa fa-paper-plane"></i> Enviar';
    });
}

function loadReplies(page = 1) {
    const container = document.getElementById('replies-container');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> Carregando respostas...</div>';
    
    fetch(`${window.forumData.apiUrl}?topic=${window.forumData.topicId}&page=${page}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Erro ao carregar respostas', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro de conexão', 'error');
    });
}

function updateCounters() {
    fetch(`${window.forumData.apiUrl}?action=stats&topic=${window.forumData.topicId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar contadores na interface, se necessário
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar contadores:', error);
    });
}

// Adicionar estilos para o modal
const modalStyles = document.createElement('style');
modalStyles.textContent = `
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: white;
        border-radius: 8px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
    .modal-close:hover {
        color: #333;
    }
    .modal-body {
        padding: 20px;
    }
    .bbcode-help-table {
        width: 100%;
        border-collapse: collapse;
    }
    .bbcode-help-table th,
    .bbcode-help-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .bbcode-help-table th {
        background-color: #f5f5f5;
        font-weight: bold;
    }
    .bbcode-help-table td:first-child {
        font-family: monospace;
        background-color: #f8f9fa;
    }
    .preview-content {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        min-height: 100px;
        background-color: #f9f9f9;
    }
    .editor-container {
        position: relative;
    }
    .editor-textarea,
    .preview-content {
        width: 100%;
        min-height: 120px;
        resize: vertical;
    }
    .bbcode-img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .loading-spinner {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    .loading-spinner i {
        font-size: 24px;
        margin-right: 10px;
    }
    .like-reply-btn {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .like-reply-btn:hover {
        background-color: #f0f0f0;
        color: #333;
    }
    .like-reply-btn.liked {
        color: #007bff;
        background-color: #e7f3ff;
    }
    .editor-info {
        flex: 1;
    }
    .editor-info small {
        color: #666;
        font-style: italic;
    }
    .toolbar-separator {
        width: 1px;
        height: 20px;
        background-color: #ddd;
        margin: 0 5px;
    }
    .editor-btn.active {
        background-color: #007bff;
        color: white;
    }
    .reply-actions-bottom {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
    }
`;
document.head.appendChild(modalStyles);
</script>
