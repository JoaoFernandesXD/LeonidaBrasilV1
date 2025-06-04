/**
 * Leonida Brasil - Noticia.js
 * JavaScript específico para funcionalidades da página de notícia
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // ARTICLE SYSTEM
    // ========================================
    
    class ArticleSystem {
        constructor() {
            this.viewCount = 2147;
            this.commentCount = 67;
            this.likeCount = 543;
            this.isLiked = false;
            this.isBookmarked = false;
            this.readingProgress = 0;
            this.milestone25 = false;
            this.milestone50 = false;
            this.milestone75 = false;
            this.milestone100 = false;
            
            this.init();
        }
        
        init() {
            this.initReadingProgress();
            this.initSocialActions();
            this.initShareSystem();
            this.initCommentsSystem();
            this.initNewsletterSystem();
            this.initBreadcrumbSystem();
            this.initScrollEffects();
            this.initViewTracking();
            this.initKeyboardShortcuts();
            this.initScrollToTop();
        }
        
        // ========================================
        // READING PROGRESS
        // ========================================
        
        initReadingProgress() {
            const $progressBar = $('.progress-fill');
            const $progressText = $('.progress-text');
            const $articleContent = $('.article-content');
            
            if (!$articleContent.length) return;
            
            $(window).on('scroll', () => {
                const windowHeight = $(window).height();
                const documentHeight = $(document).height();
                const scrollTop = $(window).scrollTop();
                const articleTop = $articleContent.offset().top;
                const articleHeight = $articleContent.outerHeight();
                
                // Calculate reading progress
                if (scrollTop >= articleTop) {
                    const progress = Math.min(
                        (scrollTop - articleTop) / (articleHeight - windowHeight) * 100,
                        100
                    );
                    
                    this.readingProgress = Math.max(0, progress);
                    
                    $progressBar.css('width', `${this.readingProgress}%`);
                    $progressText.text(`${Math.round(this.readingProgress)}% lido`);
                    
                    // Track reading milestones
                    if (this.readingProgress >= 25 && !this.milestone25) {
                        this.milestone25 = true;
                        this.trackEngagement('reading_25_percent');
                    }
                    if (this.readingProgress >= 50 && !this.milestone50) {
                        this.milestone50 = true;
                        this.trackEngagement('reading_50_percent');
                    }
                    if (this.readingProgress >= 75 && !this.milestone75) {
                        this.milestone75 = true;
                        this.trackEngagement('reading_75_percent');
                    }
                    if (this.readingProgress >= 100 && !this.milestone100) {
                        this.milestone100 = true;
                        this.trackEngagement('reading_completed');
                        this.showCompletionMessage();
                    }
                }
                
                // Update scroll to top button
                this.updateScrollToTop();
            });
        }
        
        showCompletionMessage() {
            setTimeout(() => {
                NotificationSystem.show('🎉 Artigo concluído! Que tal deixar um comentário?', 'success', 5000);
                
                // Highlight comment form
                $('.comment-form-section').addClass('highlight-pulse');
                setTimeout(() => {
                    $('.comment-form-section').removeClass('highlight-pulse');
                }, 3000);
            }, 1000);
        }
        
        // ========================================
        // SOCIAL ACTIONS
        // ========================================
        
        initSocialActions() {
            // Like button
            $('.like-btn').on('click', (e) => {
                e.preventDefault();
                this.toggleLike();
            });
            
            // Bookmark button
            $('.bookmark-btn').on('click', (e) => {
                e.preventDefault();
                this.toggleBookmark();
            });
            
            // Share button
            $('.share-btn').on('click', (e) => {
                e.preventDefault();
                this.showShareModal();
            });
        }
        
        toggleLike() {
            const $likeBtn = $('.like-btn');
            const $count = $likeBtn.find('.count');
            
            this.isLiked = !this.isLiked;
            
            if (this.isLiked) {
                this.likeCount++;
                $likeBtn.addClass('liked');
                $likeBtn.find('.fa').removeClass('fa-heart').addClass('fa-heart');
                NotificationSystem.show('Artigo curtido! ❤️', 'success');
                
                // Animate heart
                $likeBtn.addClass('animate-heart');
                setTimeout(() => {
                    $likeBtn.removeClass('animate-heart');
                }, 600);
                
            } else {
                this.likeCount--;
                $likeBtn.removeClass('liked');
                NotificationSystem.show('Curtida removida', 'info');
            }
            
            $count.text(this.likeCount);
            this.trackEngagement(this.isLiked ? 'article_liked' : 'article_unliked');
        }
        
        toggleBookmark() {
            const $bookmarkBtn = $('.bookmark-btn');
            
            this.isBookmarked = !this.isBookmarked;
            
            if (this.isBookmarked) {
                $bookmarkBtn.addClass('bookmarked').find('.fa')
                    .removeClass('fa-bookmark').addClass('fa-bookmark');
                NotificationSystem.show('Artigo salvo na sua lista! 📑', 'success');
                this.saveToReadingList();
            } else {
                $bookmarkBtn.removeClass('bookmarked').find('.fa')
                    .removeClass('fa-bookmark').addClass('fa-bookmark');
                NotificationSystem.show('Artigo removido da lista', 'info');
                this.removeFromReadingList();
            }
            
            this.trackEngagement(this.isBookmarked ? 'article_bookmarked' : 'article_unbookmarked');
        }
        
        saveToReadingList() {
            try {
                const readingList = JSON.parse(localStorage.getItem('leonida_reading_list') || '[]');
                const article = {
                    title: $('.article-title').text(),
                    url: window.location.href,
                    savedAt: new Date().toISOString(),
                    category: $('.category-badge').text().trim()
                };
                
                if (!readingList.some(item => item.url === article.url)) {
                    readingList.unshift(article);
                    if (readingList.length > 50) readingList.pop(); // Limit to 50 items
                    localStorage.setItem('leonida_reading_list', JSON.stringify(readingList));
                }
            } catch (e) {
                console.warn('Could not save to reading list:', e);
            }
        }
        
        removeFromReadingList() {
            try {
                const readingList = JSON.parse(localStorage.getItem('leonida_reading_list') || '[]');
                const filtered = readingList.filter(item => item.url !== window.location.href);
                localStorage.setItem('leonida_reading_list', JSON.stringify(filtered));
            } catch (e) {
                console.warn('Could not remove from reading list:', e);
            }
        }
        
        // ========================================
        // SHARE SYSTEM
        // ========================================
        
        initShareSystem() {
            // Share buttons in sidebar
            $('.social-btn').on('click', function(e) {
                e.preventDefault();
                const platform = this.className.split(' ')[1]; // facebook, twitter, etc.
                ArticleSystem.prototype.shareToSocial(platform);
            });
        }
        
        showShareModal() {
            const articleTitle = $('.article-title').text();
            const articleUrl = window.location.href;
            const articleDescription = $('.article-subtitle').text();
            
            const modalContent = `
                <div class="modal-overlay share-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-share-alt"></i> Compartilhar Artigo</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="share-preview">
                                <h4>${articleTitle}</h4>
                                <p>${articleDescription}</p>
                                <small>${articleUrl}</small>
                            </div>
                            
                            <div class="share-options">
                                <div class="share-grid">
                                    <button class="share-option facebook" data-platform="facebook">
                                        <i class="fab fa-facebook-f"></i>
                                        <span>Facebook</span>
                                    </button>
                                    <button class="share-option twitter" data-platform="twitter">
                                        <i class="fab fa-twitter"></i>
                                        <span>Twitter</span>
                                    </button>
                                    <button class="share-option whatsapp" data-platform="whatsapp">
                                        <i class="fab fa-whatsapp"></i>
                                        <span>WhatsApp</span>
                                    </button>
                                    <button class="share-option telegram" data-platform="telegram">
                                        <i class="fab fa-telegram"></i>
                                        <span>Telegram</span>
                                    </button>
                                    <button class="share-option reddit" data-platform="reddit">
                                        <i class="fab fa-reddit"></i>
                                        <span>Reddit</span>
                                    </button>
                                    <button class="share-option email" data-platform="email">
                                        <i class="fa fa-envelope"></i>
                                        <span>E-mail</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="share-link">
                                <label>Link direto:</label>
                                <div class="link-input-group">
                                    <input type="text" value="${articleUrl}" readonly class="share-url">
                                    <button class="copy-link-btn">
                                        <i class="fa fa-copy"></i>
                                        Copiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const $modal = $(modalContent);
            $('body').append($modal);
            
            setTimeout(() => $modal.addClass('show'), 10);
            
            // Share option clicks
            $modal.find('.share-option').on('click', function() {
                const platform = $(this).data('platform');
                ArticleSystem.prototype.shareToSocial(platform);
                ArticleSystem.prototype.closeModal();
            });
            
            // Copy link
            $modal.find('.copy-link-btn').on('click', function() {
                const $input = $modal.find('.share-url');
                $input[0].select();
                document.execCommand('copy');
                
                $(this).html('<i class="fa fa-check"></i> Copiado!');
                NotificationSystem.show('Link copiado para a área de transferência!', 'success');
                
                setTimeout(() => {
                    $(this).html('<i class="fa fa-copy"></i> Copiar');
                }, 2000);
            });
            
            // Close handlers
            $modal.find('.modal-close').on('click', this.closeModal);
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('modal-overlay')) {
                    ArticleSystem.prototype.closeModal();
                }
            });
        }
        
        shareToSocial(platform) {
            const title = encodeURIComponent($('.article-title').text());
            const url = encodeURIComponent(window.location.href);
            const description = encodeURIComponent($('.article-subtitle').text());
            
            let shareUrl = '';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${title}&url=${url}&via=LeonidaBrasil`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${title}%20${url}`;
                    break;
                case 'telegram':
                    shareUrl = `https://t.me/share/url?url=${url}&text=${title}`;
                    break;
                case 'reddit':
                    shareUrl = `https://reddit.com/submit?url=${url}&title=${title}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=${title}&body=${description}%0A%0A${url}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
                this.trackEngagement(`shared_${platform}`);
                NotificationSystem.show(`Compartilhando no ${platform}...`, 'info');
            }
        }
        
        closeModal() {
            $('.modal-overlay').removeClass('show');
            setTimeout(() => {
                $('.modal-overlay').remove();
            }, 300);
        }
        
        // ========================================
        // COMMENTS SYSTEM
        // ========================================
        
        initCommentsSystem() {
            this.initCommentForm();
            this.initCommentActions();
            this.initCommentSorting();
            this.initLoadMoreComments();
        }
        
        initCommentForm() {
            const $form = $('.comment-form');
            const $textarea = $('.comment-textarea');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Auto-resize textarea
            $textarea.on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Character counter
            $textarea.on('input', function() {
                const length = $(this).val().length;
                const maxLength = 1000;
                
                let $counter = $('.char-counter');
                if (!$counter.length) {
                    $counter = $('<div class="char-counter"></div>');
                    $(this).after($counter);
                }
                
                $counter.text(`${length}/${maxLength} caracteres`);
                
                if (length > maxLength * 0.9) {
                    $counter.addClass('warning');
                } else {
                    $counter.removeClass('warning');
                }
                
                $submitBtn.prop('disabled', length === 0 || length > maxLength);
            });
            
            // Form submission
            $form.on('submit', (e) => {
                e.preventDefault();
                this.submitComment();
            });
            
            // Auto-save draft
            let saveTimer;
            $textarea.on('input', function() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => {
                    ArticleSystem.prototype.saveDraft($(this).val());
                }, 2000);
            });
            
            // Load saved draft
            this.loadDraft();
        }
        
        initCommentActions() {
            // Comment like buttons
            $(document).on('click', '.comment-action.like', function() {
                const $btn = $(this);
                const $count = $btn.find('span');
                const isLiked = $btn.hasClass('liked');
                
                $btn.toggleClass('liked');
                
                let currentCount = parseInt($count.text()) || 0;
                if (isLiked) {
                    currentCount--;
                    NotificationSystem.show('Curtida removida', 'info');
                } else {
                    currentCount++;
                    NotificationSystem.show('Comentário curtido!', 'success');
                    $btn.addClass('animate-heart');
                    setTimeout(() => $btn.removeClass('animate-heart'), 600);
                }
                
                $count.text(currentCount);
            });
            
            // Reply buttons
            $(document).on('click', '.comment-action.reply', function() {
                const $comment = $(this).closest('.comment-item');
                const username = $comment.find('.comment-author').text().trim();
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
            });
            
            // Report buttons
            $(document).on('click', '.comment-action.report', function() {
                if (confirm('Tem certeza que deseja denunciar este comentário?')) {
                    NotificationSystem.show('Comentário denunciado! Nossa equipe irá analisar.', 'warning');
                }
            });
        }
        
        initCommentSorting() {
            $('.sort-comments').on('change', function() {
                const sortType = $(this).val();
                NotificationSystem.show(`Ordenando comentários por: ${sortType}`, 'info');
                
                // Simulate sorting
                const $comments = $('.comment-item');
                $comments.addClass('loading');
                
                setTimeout(() => {
                    $comments.removeClass('loading');
                    NotificationSystem.show('Comentários reordenados!', 'success');
                }, 1000);
            });
        }
        
        initLoadMoreComments() {
            $('.load-more-comments .btn').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const originalText = $btn.text();
                
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Carregando...')
                    .prop('disabled', true);
                
                setTimeout(() => {
                    // Add new comments (simulation)
                    const newComments = this.generateNewComments();
                    $('.comments-list').append(newComments);
                    
                    $btn.text(originalText).prop('disabled', false);
                    NotificationSystem.show('Mais comentários carregados!', 'success');
                    
                    // Update counter
                    const remaining = Math.max(0, parseInt($btn.text().match(/\d+/)[0]) - 5);
                    if (remaining > 0) {
                        $btn.text(`Carregar mais comentários (${remaining} restantes)`);
                    } else {
                        $btn.parent().html('<p style="text-align: center; color: #6c757d;">Todos os comentários foram carregados!</p>');
                    }
                }, 1500);
            });
        }
        
        generateNewComments() {
            const comments = [
                {
                    author: 'GTAFan2025',
                    time: 'há 10 minutos',
                    text: 'Concordo completamente com a análise! Os detalhes que a Rockstar colocou são impressionantes.',
                    likes: 5
                },
                {
                    author: 'ViceCityLover',
                    time: 'há 15 minutos',
                    text: 'Mal posso esperar para explorar cada cantinho de Leonida. Esse jogo vai ser épico!',
                    likes: 8
                },
                {
                    author: 'TheoryHunter',
                    time: 'há 22 minutos',
                    text: 'Vocês repararam na placa que aparece aos 1:45? Pode ser uma referência a San Andreas.',
                    likes: 12
                }
            ];
            
            let html = '';
            comments.forEach(comment => {
                html += `
                    <article class="comment-item" style="opacity: 0;">
                        <div class="comment-avatar">
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMwMEJGRkYiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTEwIDMwQzEwIDI2IDEzIDIzIDIwIDIzUzMwIDI2IDMwIDMwSDE0WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+" alt="${comment.author}">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <div class="comment-author">${comment.author}</div>
                                <div class="comment-time">${comment.time}</div>
                            </div>
                            <div class="comment-text">
                                <p>${comment.text}</p>
                            </div>
                            <div class="comment-actions">
                                <button class="comment-action like">
                                    <i class="fa fa-heart"></i>
                                    <span>${comment.likes}</span>
                                </button>
                                <button class="comment-action reply">
                                    <i class="fa fa-reply"></i>
                                    Responder
                                </button>
                                <button class="comment-action report">
                                    <i class="fa fa-flag"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                `;
            });
            
            const $newComments = $(html);
            
            // Animate in
            setTimeout(() => {
                $newComments.animate({opacity: 1}, 500);
            }, 100);
            
            return $newComments;
        }
        
        submitComment() {
            const $textarea = $('.comment-textarea');
            const content = $textarea.val().trim();
            
            if (!content) {
                NotificationSystem.show('Digite um comentário antes de enviar!', 'warning');
                return;
            }
            
            if (content.length > 1000) {
                NotificationSystem.show('Comentário muito longo! Máximo 1000 caracteres.', 'warning');
                return;
            }
            
            // Disable form
            $('.comment-form').addClass('loading');
            $textarea.prop('disabled', true);
            $('.comment-form button').prop('disabled', true);
            
            // Simulate submission
            setTimeout(() => {
                NotificationSystem.show('Comentário enviado com sucesso!', 'success');
                
                // Clear draft
                try {
                    localStorage.removeItem('leonida_comment_draft');
                } catch (e) {}
                
                // Reset form
                $textarea.val('').css('height', 'auto');
                $('.char-counter').remove();
                $('.comment-form').removeClass('loading');
                $textarea.prop('disabled', false);
                $('.comment-form button').prop('disabled', false);
                
                // Add new comment to page
                this.addNewComment(content);
                
                // Update comment count
                this.commentCount++;
                $('.comment-count').text(this.commentCount);
                
            }, 2000);
        }
        
        addNewComment(content) {
            const newComment = `
                <article class="comment-item" style="opacity: 0; border-left: 4px solid var(--color-success);">
                    <div class="comment-avatar">
                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNGRjAwN0YiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTEwIDMwQzEwIDI2IDEzIDIzIDIwIDIzUzMwIDI2IDMwIDMwSDE0WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+" alt="Usuário">
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <div class="comment-author">
                                Você
                                <span class="comment-badge author">Autor</span>
                            </div>
                            <div class="comment-time">agora</div>
                        </div>
                        <div class="comment-text">
                            <p>${content.replace(/\n/g, '</p><p>')}</p>
                        </div>
                        <div class="comment-actions">
                            <button class="comment-action like">
                                <i class="fa fa-heart"></i>
                                <span>0</span>
                            </button>
                            <button class="comment-action reply">
                                <i class="fa fa-reply"></i>
                                Responder
                            </button>
                            <button class="comment-action report">
                                <i class="fa fa-flag"></i>
                            </button>
                        </div>
                    </div>
                </article>
            `;
            
            const $newComment = $(newComment);
            $('.comments-list').prepend($newComment);
            
            // Animate in
            $newComment.animate({opacity: 1}, 600);
            
            // Scroll to new comment
            $('html, body').animate({
                scrollTop: $newComment.offset().top - 100
            }, 800);
            
            // Remove highlight after a while
            setTimeout(() => {
                $newComment.css('border-left', 'none');
            }, 5000);
        }
        
        saveDraft(content) {
            try {
                localStorage.setItem('leonida_comment_draft', content);
            } catch (e) {
                // localStorage not available
            }
        }
        
        loadDraft() {
            try {
                const draft = localStorage.getItem('leonida_comment_draft');
                if (draft && draft.trim()) {
                    $('.comment-textarea').val(draft).trigger('input');
                    NotificationSystem.show('Rascunho carregado', 'info');
                }
            } catch (e) {
                // localStorage not available
            }
        }
        
        // ========================================
        // NEWSLETTER SYSTEM
        // ========================================
        
        initNewsletterSystem() {
            $('.newsletter-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $input = $form.find('input[type="email"]');
                const $button = $form.find('button');
                const email = $input.val().trim();
                
                if (!email) {
                    NotificationSystem.show('Digite um e-mail válido!', 'warning');
                    return;
                }
                
                if (!ArticleSystem.prototype.isValidEmail(email)) {
                    NotificationSystem.show('E-mail inválido!', 'error');
                    return;
                }
                
                // Disable form
                $input.prop('disabled', true);
                $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Inscrevendo...');
                
                // Simulate subscription
                setTimeout(() => {
                    NotificationSystem.show('Inscrição realizada com sucesso! 🎉', 'success');
                    $input.val('').prop('disabled', false);
                    $button.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Inscrever');
                    
                    // Update subscriber count
                    const $count = $('.newsletter-stats span');
                    const currentCount = parseInt($count.text().replace(/\D/g, ''));
                    $count.text(`Junte-se a mais de ${(currentCount + 1).toLocaleString()} inscritos`);
                    
                }, 2000);
            });
        }
        
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // ========================================
        // BREADCRUMB INTERACTIONS
        // ========================================
        
        initBreadcrumbSystem() {
            // Handle breadcrumb navigation
            $('.breadcrumb-item').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && href !== '#' && !$(this).hasClass('current')) {
                    // Allow normal navigation
                    return;
                }
                
                if (!$(this).hasClass('current')) {
                    e.preventDefault();
                    const text = $(this).text().trim();
                    NotificationSystem.show(`Navegando para: ${text}`, 'info');
                }
            });
            
            // Page action buttons
            $('.action-btn.share-btn').on('click', (e) => {
                e.preventDefault();
                this.showShareModal();
            });
            
            $('.action-btn.bookmark-btn').on('click', (e) => {
                e.preventDefault();
                this.toggleBookmark();
            });
            
            $('.action-btn.report-btn').on('click', (e) => {
                e.preventDefault();
                this.showReportModal();
            });
        }
        
        showReportModal() {
            const modalContent = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-flag"></i> Relatar Problema</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form class="report-form">
                                <div class="form-group">
                                    <label>Tipo do problema:</label>
                                    <select class="form-control">
                                        <option>Conteúdo incorreto</option>
                                        <option>Link quebrado</option>
                                        <option>Spam ou conteúdo inadequado</option>
                                        <option>Problema técnico</option>
                                        <option>Violação de direitos autorais</option>
                                        <option>Outro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Descrição:</label>
                                    <textarea class="form-control" rows="4" placeholder="Descreva o problema..."></textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                                    <button type="submit" class="btn btn-danger">Enviar Relatório</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            const $modal = $(modalContent);
            $('body').append($modal);
            
            setTimeout(() => $modal.addClass('show'), 10);
            
            // Handle form submission
            $modal.find('.report-form').on('submit', function(e) {
                e.preventDefault();
                NotificationSystem.show('Relatório enviado! Nossa equipe irá analisar.', 'success');
                ArticleSystem.prototype.closeModal();
            });
            
            // Close handlers
            $modal.find('.modal-close').on('click', this.closeModal);
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('modal-overlay')) {
                    ArticleSystem.prototype.closeModal();
                }
            });
        }
        
        initScrollEffects() {
            // Parallax effect for feature cards
            $(window).on('scroll', function() {
                const scrolled = $(this).scrollTop();
                const parallax = scrolled * 0.5;
                
                $('.feature-card, .location-card').each(function(index) {
                    const speed = (index + 1) * 0.1;
                    $(this).css('transform', `translateY(${parallax * speed}px)`);
                });
            });
            
            // Sticky sidebar
            const $sidebar = $('.article-sidebar');
            const $main = $('.article-main');
            
            if ($sidebar.length && $main.length) {
                $(window).on('scroll', function() {
                    const scrollTop = $(this).scrollTop();
                    const mainTop = $main.offset().top;
                    const mainHeight = $main.outerHeight();
                    const sidebarHeight = $sidebar.outerHeight();
                    const windowHeight = $(this).height();
                    
                    if (scrollTop > mainTop - 100 && scrollTop < mainTop + mainHeight - sidebarHeight - 100) {
                        $sidebar.addClass('sticky');
                    } else {
                        $sidebar.removeClass('sticky');
                    }
                });
            }
        }
        
        // ========================================
        // VIEW TRACKING
        // ========================================
        
        initViewTracking() {
            // Track page view
            this.trackEngagement('page_view');
            
            // Track time spent
            this.startTime = Date.now();
            
            $(window).on('beforeunload', () => {
                const timeSpent = Math.round((Date.now() - this.startTime) / 1000);
                this.trackEngagement('time_spent', timeSpent);
            });
            
            // Track scroll depth
            let maxScroll = 0;
            $(window).on('scroll', () => {
                const scrollPercent = Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100);
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    
                    // Track major scroll milestones
                    if (maxScroll >= 25 && !this.scrollMilestone25) {
                        this.scrollMilestone25 = true;
                        this.trackEngagement('scroll_25_percent');
                    }
                    if (maxScroll >= 50 && !this.scrollMilestone50) {
                        this.scrollMilestone50 = true;
                        this.trackEngagement('scroll_50_percent');
                    }
                    if (maxScroll >= 75 && !this.scrollMilestone75) {
                        this.scrollMilestone75 = true;
                        this.trackEngagement('scroll_75_percent');
                    }
                }
            });
        }
        
        trackEngagement(action, value = null) {
            // Analytics tracking (simulate)
            console.log(`📊 Analytics: ${action}`, value || '');
            
            // In a real implementation, you would send this to your analytics service
            // gtag('event', action, { value: value });
        }
        
        // ========================================
        // KEYBOARD SHORTCUTS
        // ========================================
        
        initKeyboardShortcuts() {
            $(document).on('keydown', (e) => {
                // Only if not typing in an input
                if ($(e.target).is('input, textarea')) return;
                
                switch(e.key) {
                    case 'l': // Like article
                        e.preventDefault();
                        this.toggleLike();
                        break;
                    case 'b': // Bookmark article
                        e.preventDefault();
                        this.toggleBookmark();
                        break;
                    case 's': // Share article
                        e.preventDefault();
                        this.showShareModal();
                        break;
                    case 'c': // Focus comment form
                        e.preventDefault();
                        $('.comment-textarea').focus();
                        $('html, body').animate({
                            scrollTop: $('.comment-form-section').offset().top - 100
                        }, 500);
                        break;
                    case 'Escape': // Close modals
                        this.closeModal();
                        break;
                }
            });
            
            // Show keyboard shortcuts help
            let helpShown = false;
            $(document).on('keydown', (e) => {
                if (e.key === '?' && !helpShown) {
                    helpShown = true;
                    NotificationSystem.show('Atalhos: L=Curtir, B=Salvar, S=Compartilhar, C=Comentar', 'info', 5000);
                    setTimeout(() => { helpShown = false; }, 6000);
                }
            });
        }
        
        // ========================================
        // SCROLL TO TOP
        // ========================================
        
        initScrollToTop() {
            // Create scroll to top button if it doesn't exist
            if (!$('.scroll-to-top').length) {
                $('body').append('<button class="scroll-to-top"><i class="fa fa-arrow-up"></i></button>');
            }
            
            $('.scroll-to-top').on('click', function() {
                $('html, body').animate({scrollTop: 0}, 800);
            });
        }
        
        updateScrollToTop() {
            const $scrollBtn = $('.scroll-to-top');
            const scrollTop = $(window).scrollTop();
            
            if (scrollTop > 500) {
                $scrollBtn.addClass('show');
            } else {
                $scrollBtn.removeClass('show');
            }
        }
    }
    
    // ========================================
    // RELATED ARTICLES INTERACTIONS
    // ========================================
    
    class RelatedArticles {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            $('.related-item').on('click', function() {
                const title = $(this).find('h4').text();
                NotificationSystem.show(`Carregando: ${title}`, 'info');
                
                // Simulate navigation
                $(this).addClass('loading');
                setTimeout(() => {
                    $(this).removeClass('loading');
                    // In a real app, you would navigate to the article
                }, 1000);
            });
            
            $('.related-item').hover(
                function() {
                    $(this).find('.related-thumb').addClass('hover');
                },
                function() {
                    $(this).find('.related-thumb').removeClass('hover');
                }
            );
        }
    }
    
    // ========================================
    // POPULAR ARTICLES WIDGET
    // ========================================
    
    class PopularArticles {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updateCounts();
        }
        
        bindEvents() {
            $('.popular-item').on('click', function() {
                const title = $(this).find('h5').text();
                NotificationSystem.show(`Abrindo: ${title}`, 'info');
                
                // Update view count
                const $views = $(this).find('.popular-meta span:first-child');
                const currentViews = parseInt($views.text().replace(/\D/g, ''));
                $views.html(`<i class="fa fa-eye"></i> ${(currentViews + 1).toLocaleString()}`);
            });
        }
        
        updateCounts() {
            // Simulate real-time view updates
            setInterval(() => {
                $('.popular-meta').each(function() {
                    if (Math.random() > 0.7) { // 30% chance to update
                        const $views = $(this).find('span:first-child');
                        const currentViews = parseInt($views.text().replace(/\D/g, ''));
                        const newViews = currentViews + Math.floor(Math.random() * 5) + 1;
                        $views.html(`<i class="fa fa-eye"></i> ${newViews.toLocaleString()}`);
                    }
                });
            }, 30000); // Update every 30 seconds
        }
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    // Initialize all systems
    const articleSystem = new ArticleSystem();
    const relatedArticles = new RelatedArticles();
    const popularArticles = new PopularArticles();
    
    // Additional page interactions
    $('.tag').on('click', function(e) {
        e.preventDefault();
        const tag = $(this).text();
        NotificationSystem.show(`Buscando artigos com tag: ${tag}`, 'info');
    });
    
    $('.author-name, .btn-profile').on('click', function(e) {
        e.preventDefault();
        const author = $('.author-name').text().trim();
        NotificationSystem.show(`Visualizando perfil de: ${author}`, 'info');
    });
    
    // View count simulation
    setInterval(() => {
        const $viewCount = $('.view-count');
        if ($viewCount.length) {
            const currentViews = parseInt($viewCount.text().replace(/\D/g, ''));
            if (Math.random() > 0.8) { // 20% chance to increment
                $viewCount.text((currentViews + Math.floor(Math.random() * 3) + 1).toLocaleString());
            }
        }
    }, 10000); // Update every 10 seconds
    
    // Welcome message
    setTimeout(() => {
        NotificationSystem.show('📖 Bem-vindo ao artigo! Use L para curtir, B para salvar', 'info');
    }, 2000);
    
    console.log('📰 Article system loaded successfully!');
});