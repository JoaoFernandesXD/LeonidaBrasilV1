/**
 * Leonida Brasil - Sistema Completo de Comentários
 */

class CommentsSystem {
    constructor(options = {}) {
        this.newsId = options.newsId || window.newsData?.id;
        this.contentType = options.contentType || 'news';
        this.contentId = options.contentId || this.newsId;
        this.apiUrl = options.apiUrl || '/api/comments.php';
        this.userId = window.newsData?.userId || null;
        this.debug = options.debug || false;
        
        this.currentPage = 1;
        this.currentSort = 'recent';
        this.isLoading = false;
        this.hasMoreComments = true;
        
        this.init();
    }
    
    init() {
        this.log('CommentsSystem initialized', {
            contentType: this.contentType,
            contentId: this.contentId,
            userId: this.userId
        });
        
        this.bindEvents();
        this.loadComments();
    }
    
    log(message, data = null) {
        if (this.debug) {
            console.log(`[CommentsSystem] ${message}`, data);
        }
    }
    
    bindEvents() {
        // Submit comment form
        $(document).on('submit', '#commentForm', (e) => {
            e.preventDefault();
            this.submitComment();
        });
        
        // Auto-resize textarea
        $(document).on('input', '.comment-textarea', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Character counter
            const maxLength = 1000;
            const currentLength = $(this).val().length;
            const remaining = maxLength - currentLength;
            
            let $counter = $(this).siblings('.char-counter');
            if ($counter.length === 0) {
                $counter = $('<div class="char-counter"></div>');
                $(this).after($counter);
            }
            
            $counter.text(`${currentLength}/${maxLength} caracteres`);
            $counter.toggleClass('warning', remaining < 100);
            $counter.toggleClass('danger', remaining < 0);
            
            // Enable/disable submit button
            const $form = $(this).closest('form');
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', currentLength === 0 || currentLength > maxLength);
        });
        
        // Like comment
        $(document).on('click', '.comment-action.like', (e) => {
            e.preventDefault();
            const commentId = $(e.currentTarget).data('comment-id');
            this.likeComment(commentId, $(e.currentTarget));
        });
        
        // Reply to comment
        $(document).on('click', '.comment-action.reply', (e) => {
            e.preventDefault();
            const commentId = $(e.currentTarget).data('comment-id');
            this.showReplyForm(commentId);
        });
        
        // Report comment
        $(document).on('click', '.comment-action.report', (e) => {
            e.preventDefault();
            const commentId = $(e.currentTarget).data('comment-id');
            this.reportComment(commentId);
        });
        
        // Delete comment
        $(document).on('click', '.comment-action.delete', (e) => {
            e.preventDefault();
            const commentId = $(e.currentTarget).data('comment-id');
            this.deleteComment(commentId);
        });
        
        // Sort comments
        $(document).on('change', '#sortComments', (e) => {
            this.currentSort = $(e.target).val();
            this.currentPage = 1;
            this.loadComments(true);
        });
        
        // Load more comments
        $(document).on('click', '#loadMoreComments', (e) => {
            e.preventDefault();
            this.loadMoreComments();
        });
        
        // Cancel reply
        $(document).on('click', '.cancel-reply-btn', (e) => {
            e.preventDefault();
            this.hideReplyForm();
        });
    }
    
    loadComments(refresh = false) {
        if (this.isLoading) return;
        
        this.log('Loading comments', {
            page: this.currentPage,
            sort: this.currentSort,
            refresh: refresh
        });
        
        if (!this.contentId) {
            this.log('No content ID provided');
            return;
        }
        
        this.isLoading = true;
        const $commentsContainer = $('#commentsList');
        
        if (refresh || this.currentPage === 1) {
            $commentsContainer.html('<div class="loading-comments"><i class="fa fa-spinner fa-spin"></i> Carregando comentários...</div>');
        }
        
        $.ajax({
            url: this.apiUrl,
            method: 'GET',
            data: {
                type: this.contentType,
                id: this.contentId,
                page: this.currentPage,
                per_page: 10,
                sort: this.currentSort
            },
            dataType: 'json',
            success: (response) => {
                this.log('Comments loaded successfully', response);
                
                if (response.success) {
                    if (refresh || this.currentPage === 1) {
                        $commentsContainer.empty();
                    }
                    
                    if (response.data.length === 0 && this.currentPage === 1) {
                        $commentsContainer.html(`
                            <div class="no-comments">
                                <i class="fa fa-comments-o"></i>
                                <p>Seja o primeiro a comentar!</p>
                            </div>
                        `);
                    } else {
                        response.data.forEach(comment => {
                            this.addCommentToList(comment);
                        });
                    }
                    
                    this.updateLoadMoreButton(response.meta);
                    this.updateCommentCount(response.meta.total);
                } else {
                    this.showError('Erro ao carregar comentários: ' + response.error);
                }
            },
            error: (xhr, status, error) => {
                this.log('Error loading comments', { xhr, status, error });
                this.showError('Erro ao carregar comentários');
                
                if (this.currentPage === 1) {
                    $commentsContainer.html('<div class="error-message">Erro ao carregar comentários</div>');
                }
            },
            complete: () => {
                this.isLoading = false;
            }
        });
    }
    
    submitComment() {
        const $form = $('#commentForm');
        const $textarea = $('.comment-textarea');
        const content = $textarea.val().trim();
        
        this.log('Submitting comment', { 
            content: content.substring(0, 50) + '...',
            length: content.length 
        });
        
        // Validações
        if (!content) {
            this.showNotification('Digite um comentário antes de enviar!', 'warning');
            return;
        }
        
        if (content.length > 1000) {
            this.showNotification('Comentário muito longo! Máximo 1000 caracteres.', 'warning');
            return;
        }
        
        if (!this.contentId) {
            this.showNotification('Erro: ID do conteúdo não encontrado', 'error');
            return;
        }
        
        if (!this.userId) {
            this.showLoginRequired();
            return;
        }
        
        // Disable form
        this.setFormLoading(true);
        
        const requestData = {
            content_type: this.contentType,
            content_id: this.contentId,
            content: content
        };
        
        $.ajax({
            url: this.apiUrl,
            method: 'POST',
            dataType: 'json',
            data: JSON.stringify(requestData),
            contentType: 'application/json',
            success: (response) => {
                this.log('Comment submitted successfully', response);
                
                if (response.success) {
                    this.showNotification('Comentário enviado com sucesso!', 'success');
                    
                    // Reset form
                    $textarea.val('').css('height', 'auto');
                    $('.char-counter').remove();
                    
                    // Add new comment to top of list
                    this.addNewComment(response.data);
                    
                    // Update comment count
                    this.incrementCommentCount();
                    
                } else {
                    // Verificar se é erro de flood
                    if (response.error.includes('Aguarde')) {
                        this.showNotification(response.error, 'warning', 8000);
                    } else {
                        this.showNotification(response.error, 'error');
                    }
                }
            },
            error: (xhr) => {
                this.log('Error submitting comment', xhr);
                
                let message = 'Erro ao enviar comentário';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                
                this.showNotification(message, 'error');
            },
            complete: () => {
                this.setFormLoading(false);
            }
        });
    }
    
    likeComment(commentId, $button) {
        if (!this.userId) {
            this.showLoginRequired();
            return;
        }
        
        this.log('Liking comment', commentId);
        
        const $count = $button.find('span');
        const isLiked = $button.hasClass('liked');
        
        // Optimistic UI update
        $button.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: `${this.apiUrl}?action=like&id=${commentId}`,
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                this.log('Comment like response', response);
                
                if (response.success) {
                    $button.toggleClass('liked', response.data.liked);
                    $count.text(response.data.likes_count);
                    
                    // Heart animation for new likes
                    if (response.data.liked) {
                        this.showHeartAnimation($button);
                    }
                    
                    this.showNotification(response.message, 'success', 2000);
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            error: (xhr) => {
                this.log('Error liking comment', xhr);
                
                let message = 'Erro ao curtir comentário';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                
                this.showNotification(message, 'error');
            },
            complete: () => {
                $button.removeClass('loading').prop('disabled', false);
            }
        });
    }
    
    reportComment(commentId) {
        if (!this.userId) {
            this.showLoginRequired();
            return;
        }
        
        if (!confirm('Tem certeza que deseja reportar este comentário?')) {
            return;
        }
        
        this.log('Reporting comment', commentId);
        
        $.ajax({
            url: `${this.apiUrl}?action=report&id=${commentId}`,
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showNotification(response.message, 'success');
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            error: () => {
                this.showNotification('Erro ao reportar comentário', 'error');
            }
        });
    }
    
    deleteComment(commentId) {
        if (!confirm('Tem certeza que deseja deletar este comentário?')) {
            return;
        }
        
        this.log('Deleting comment', commentId);
        
        $.ajax({
            url: `${this.apiUrl}?action=delete&id=${commentId}`,
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Remove comment from DOM
                    $(`.comment-item[data-comment-id="${commentId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    this.showNotification(response.message, 'success');
                    this.decrementCommentCount();
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            error: () => {
                this.showNotification('Erro ao deletar comentário', 'error');
            }
        });
    }
    
    loadMoreComments() {
        if (this.isLoading || !this.hasMoreComments) return;
        
        this.currentPage++;
        this.loadComments();
    }
    
    addNewComment(commentData) {
        const commentHtml = this.buildCommentHtml(commentData, true);
        const $newComment = $(commentHtml);
        
        // Add to top of list
        const $commentsList = $('#commentsList');
        
        // Remove "no comments" message if exists
        $commentsList.find('.no-comments').remove();
        
        $commentsList.prepend($newComment);
        
        // Animate in
        $newComment.css('opacity', 0).animate({opacity: 1}, 600);
        
        // Scroll to new comment
        $('html, body').animate({
            scrollTop: $newComment.offset().top - 100
        }, 800);
        
        // Highlight and remove after 5 seconds
        $newComment.addClass('new-comment');
        setTimeout(() => {
            $newComment.removeClass('new-comment');
        }, 5000);
    }
    
    addCommentToList(commentData) {
        const commentHtml = this.buildCommentHtml(commentData);
        $('#commentsList').append(commentHtml);
    }
    
    buildCommentHtml(comment, isNew = false) {
        const canEdit = comment.can_edit || false;
        const isAuthor = comment.is_author || false;
        
        return `
            <article class="comment-item${isNew ? ' new-comment' : ''}" data-comment-id="${comment.id}">
                <div class="comment-avatar">
                    <img src="${comment.avatar}" alt="${comment.author_name}" loading="lazy">
                    ${comment.level >= 3 ? '<div class="verified-badge"><i class="fa fa-check"></i></div>' : ''}
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <div class="comment-author">
                            <strong>${comment.author_name}</strong>
                            ${isAuthor ? '<span class="comment-badge author">Você</span>' : ''}
                            ${comment.level >= 3 ? '<span class="comment-badge verified">Verificado</span>' : ''}
                        </div>
                        <div class="comment-time" title="${comment.created_at}">${comment.time_ago}</div>
                    </div>
                    <div class="comment-text">
                        <p>${this.formatCommentContent(comment.content)}</p>
                    </div>
                    <div class="comment-actions">
                        <button class="comment-action like ${comment.is_liked ? 'liked' : ''}" data-comment-id="${comment.id}">
                            <i class="fa fa-heart"></i>
                            <span>${comment.likes || 0}</span>
                        </button>
                        <button class="comment-action reply" data-comment-id="${comment.id}">
                            <i class="fa fa-reply"></i>
                            Responder
                        </button>
                        ${canEdit ? `
                            <button class="comment-action delete" data-comment-id="${comment.id}">
                                <i class="fa fa-trash"></i>
                                Excluir
                            </button>
                        ` : `
                            <button class="comment-action report" data-comment-id="${comment.id}">
                                <i class="fa fa-flag"></i>
                                Reportar
                            </button>
                        `}
                    </div>
                </div>
            </article>
        `;
    }
    
    formatCommentContent(content) {
        // Converter quebras de linha em parágrafos
        const paragraphs = content.split('\n').filter(p => p.trim());
        return paragraphs.map(p => `<p>${p}</p>`).join('');
    }
    
    showReplyForm(commentId) {
        const $comment = $(`.comment-item[data-comment-id="${commentId}"]`);
        const username = $comment.find('.comment-author strong').text().trim();
        const $textarea = $('.comment-textarea');
        
        const currentText = $textarea.val();
        const replyText = `@${username} `;
        
        if (!currentText.includes(replyText)) {
            $textarea.val(replyText + currentText).focus();
            $textarea.trigger('input');
        }
        
        // Scroll to comment form
        $('html, body').animate({
            scrollTop: $('.comment-form-section').offset().top - 100
        }, 500);
    }
    
    hideReplyForm() {
        // Limpar menções do textarea
        const $textarea = $('.comment-textarea');
        const text = $textarea.val().replace(/@\w+\s*/g, '');
        $textarea.val(text).trigger('input');
    }
    
    updateLoadMoreButton(meta) {
        const $btn = $('#loadMoreComments');
        
        this.hasMoreComments = meta.has_next;
        
        if (meta.has_next) {
            const remaining = meta.total - (meta.current_page * meta.per_page);
            $btn.text(`Carregar mais comentários (${remaining} restantes)`).show();
        } else {
            $btn.hide();
            
            if (meta.total > meta.per_page && meta.current_page > 1) {
                $('#commentsList').after('<p class="text-center text-muted mt-3">Todos os comentários foram carregados!</p>');
            }
        }
    }
    
    updateCommentCount(count) {
        $('.comment-count').text(count);
        $('.comments-header h3').text(`Comentários (${count})`);
    }
    
    incrementCommentCount() {
        const $counter = $('.comment-count');
        const current = parseInt($counter.text()) || 0;
        this.updateCommentCount(current + 1);
    }
    
    decrementCommentCount() {
        const $counter = $('.comment-count');
        const current = parseInt($counter.text()) || 0;
        this.updateCommentCount(Math.max(0, current - 1));
    }
    
    setFormLoading(loading) {
        const $form = $('#commentForm');
        const $textarea = $('.comment-textarea');
        const $submitBtn = $form.find('button[type="submit"]');
        
        if (loading) {
            $form.addClass('loading');
            $textarea.prop('disabled', true);
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
        } else {
            $form.removeClass('loading');
            $textarea.prop('disabled', false);
            $submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Comentar');
        }
    }
    
    showLoginRequired() {
        this.showNotification('Faça login para interagir com os comentários!', 'warning', 6000);
        
        // Opcional: mostrar modal de login ou redirecionar
        setTimeout(() => {
            if (confirm('Deseja fazer login agora?')) {
                window.location.href = '/login';
            }
        }, 2000);
    }
    
    showNotification(message, type = 'info', duration = 4000) {
        // Remove existing notifications of same type
        $(`.notification-${type}`).remove();
        
        const notification = $(`
            <div class="notification notification-${type}">
                <div class="notification-content">
                    <i class="fa fa-${this.getNotificationIcon(type)}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => notification.addClass('show'), 10);
        
        // Auto remove
        const timeout = setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Manual close
        notification.find('.notification-close').on('click', () => {
            clearTimeout(timeout);
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    showHeartAnimation($element) {
        // Criar coração flutuante
        const $heart = $('<div class="floating-heart"><i class="fa fa-heart"></i></div>');
        const offset = $element.offset();
        
        $heart.css({
            position: 'fixed',
            left: offset.left + $element.width() / 2,
            top: offset.top,
            color: '#e74c3c',
            fontSize: '20px',
            zIndex: 9999,
            pointerEvents: 'none'
        });
        
        $('body').append($heart);
        
        // Animar coração subindo e desaparecendo
        $heart.animate({
            top: offset.top - 50,
            opacity: 0
        }, 1000, function() {
            $(this).remove();
        });
        
        // Animar botão
        $element.addClass('animate-heart');
        setTimeout(() => {
            $element.removeClass('animate-heart');
        }, 600);
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
}

// Inicializar sistema quando documento estiver pronto
$(document).ready(function() {
    'use strict';
    
    // Verificar se os dados estão disponíveis
    if (typeof window.newsData !== 'undefined' && window.newsData.id) {
        window.commentsSystem = new CommentsSystem({
            newsId: window.newsData.id,
            contentType: 'news',
            debug: true // Remover em produção
        });
    } else {
        console.warn('News data not available for comments system');
    }
});

// CSS adicional necessário (adicionar ao arquivo CSS principal)
const additionalCSS = `
.comment-item.new-comment {
    border-left: 4px solid #28a745;
    background-color: rgba(40, 167, 69, 0.1);
    transition: all 0.3s ease;
}

.comment-action.loading {
    opacity: 0.6;
    pointer-events: none;
}

.comment-action.like.animate-heart {
    transform: scale(1.2);
    transition: transform 0.3s ease;
}

.floating-heart {
    animation: floatUp 1s ease-out forwards;
}

@keyframes floatUp {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translateY(-30px) scale(1.5);
        opacity: 0;
    }
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 15px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    max-width: 400px;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #28a745;
}

.notification-error {
    border-left: 4px solid #dc3545;
}

.notification-warning {
    border-left: 4px solid #ffc107;
}

.notification-info {
    border-left: 4px solid #17a2b8;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.notification-close {
    position: absolute;
    top: 5px;
    right: 10px;
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #999;
}

.char-counter {
    font-size: 12px;
    color: #666;
    text-align: right;
    margin-top: 5px;
}

.char-counter.warning {
    color: #ffc107;
}

.char-counter.danger {
    color: #dc3545;
    font-weight: bold;
}

.verified-badge {
    bottom: -2px;
    right: -2px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
}

.comment-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.comment-badge.author {
    background: #28a745;
    color: white;
}

.comment-badge.verified {
    background: #007bff;
    color: white;
}

.loading-comments {
    text-align: center;
    padding: 40px;
    color: #666;
}

.no-comments {
    text-align: center;
    padding: 40px;
    color: #999;
}

.no-comments i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.error-message {
    text-align: center;
    padding: 20px;
    color: #dc3545;
    border: 1px solid #dc3545;
    border-radius: 4px;
    background-color: rgba(220, 53, 69, 0.1);
}
`;

// Adicionar CSS dinamicamente
if (!document.getElementById('comments-css')) {
    const style = document.createElement('style');
    style.id = 'comments-css';
    style.textContent = additionalCSS;
    document.head.appendChild(style);
}