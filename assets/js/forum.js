/**
 * Leonida Brasil - Forum.js
 * JavaScript específico para funcionalidades do fórum
 * Integrado com API backend - Versão Corrigida
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // FORUM SYSTEM
    // ========================================
    
    class ForumSystem {
        constructor() {
            this.apiBase = '/api/forum.php';
            this.currentTopicId = this.getCurrentTopicId();
            this.currentPage = 1;
            this.isSubmitting = false; // Prevenir envios duplos
            this.init();
        }
        
        getCurrentTopicId() {
            // PRIORIDADE 1: Buscar no campo hidden primeiro
            const hiddenInput = $('input[name="topic_id"]').first();
            if (hiddenInput.length && hiddenInput.val()) {
                console.log('Topic ID encontrado no campo hidden:', hiddenInput.val());
                return parseInt(hiddenInput.val());
            }
            
            // PRIORIDADE 2: Buscar em outros campos hidden
            const hiddenDataInput = $('input[data-topic-id]').first();
            if (hiddenDataInput.length && hiddenDataInput.data('topic-id')) {
                console.log('Topic ID encontrado no data-topic-id:', hiddenDataInput.data('topic-id'));
                return parseInt(hiddenDataInput.data('topic-id'));
            }
            
            // PRIORIDADE 3: Extrair da URL 
            const urlMatch = window.location.pathname.match(/\/forum\/topico\/(\d+)/);
            if (urlMatch) {
                console.log('Topic ID encontrado na URL:', urlMatch[1]);
                return parseInt(urlMatch[1]);
            }
            
            // PRIORIDADE 4: Buscar em elementos com data-topic-id
            const topicElement = $('[data-topic-id]').first();
            if (topicElement.length && topicElement.data('topic-id')) {
                console.log('Topic ID encontrado em elemento data-topic-id:', topicElement.data('topic-id'));
                return parseInt(topicElement.data('topic-id'));
            }
            
            // PRIORIDADE 5: Buscar em elementos de fórum específicos
            const forumSection = $('.forum-content, .topic-header, .reply-section').first();
            if (forumSection.length && forumSection.data('topic-id')) {
                console.log('Topic ID encontrado em seção do fórum:', forumSection.data('topic-id'));
                return parseInt(forumSection.data('topic-id'));
            }
            
            console.warn('Topic ID NÃO encontrado em nenhum local da página');
            console.log('Elementos verificados:', {
                'input[name="topic_id"]': $('input[name="topic_id"]').length,
                'input[data-topic-id]': $('input[data-topic-id]').length,
                'url_pattern': window.location.pathname,
                'data-topic-id_elements': $('[data-topic-id]').length
            });
            
            return null;
        }
        
        init() {
            this.initPostActions();
            this.initReplyActions();
            this.initEditor();
            this.initPageActions();
            this.initUserInteractions();
            this.loadTopicData();
            
            // Debug: mostrar info do tópico atual
            console.log('Forum System initialized:', {
                currentTopicId: this.currentTopicId,
                currentPage: this.currentPage,
                url: window.location.pathname
            });
            
            // Se não conseguiu pegar o topic_id, mas está numa página de tópico, 
            // adicionar um campo manual para debug
            if (!this.currentTopicId && window.location.pathname.includes('/forum')) {
                console.warn('Topic ID não detectado automaticamente. Adicione data-topic-id ao HTML ou use URL padrão.');
            }
        }
        
        // ========================================
        // MANUAL TOPIC ID SETTING (para debug)
        // ========================================
        
        setTopicId(topicId) {
            this.currentTopicId = parseInt(topicId);
            console.log('Topic ID definido manualmente:', this.currentTopicId);
            return this.currentTopicId;
        }
        
        // ========================================
        // API INTEGRATION - CORRIGIDA
        // ========================================
        
        async apiRequest(endpoint, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                
                if (data && method !== 'GET') {
                    options.body = JSON.stringify(data);
                }
                
                let url = this.apiBase;
                if (endpoint) {
                    url += (url.includes('?') ? '&' : '?') + endpoint;
                }
                
                console.log('API Request:', method, url, data); // Debug
                
                const response = await fetch(url, options);
                
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Response is not JSON:', text);
                    throw new Error('Resposta da API não é JSON válido');
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Erro na requisição');
                }
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                this.showNotification(`Erro: ${error.message}`, 'error');
                throw error;
            }
        }
        
        async loadTopicData() {
            if (!this.currentTopicId) return;
            
            try {
                const result = await this.apiRequest(`topic=${this.currentTopicId}&page=${this.currentPage}`);
                this.updateTopicDisplay(result.data);
                this.updatePagination(result.meta);
            } catch (error) {
                console.error('Error loading topic:', error);
                // Não mostrar erro se for página sem tópico específico
            }
        }
        
        updateTopicDisplay(data) {
            const { topic, replies } = data;
            
            // Atualizar informações do tópico
            $('.topic-title').text(topic.title);
            $('.topic-views').text(topic.formatted_views);
            $('.topic-author').text(topic.author_name);
            $('.topic-time').text(topic.time_ago);
            
            // Atualizar contador de respostas
            $('.replies-count').text(replies.length);
            
            // Renderizar respostas se existirem
            if (replies.length > 0) {
                this.renderReplies(replies);
            }
        }
        
        renderReplies(replies) {
            const $repliesContainer = $('.replies-container');
            
            replies.forEach(reply => {
                const replyHtml = this.buildReplyHTML(reply);
                $repliesContainer.append(replyHtml);
            });
            
            // Re-inicializar eventos para novos elementos
            this.initReplyActions();
        }
        
        buildReplyHTML(reply) {
            return `
                <div class="reply-section" data-reply-id="${reply.id}">
                    <aside class="reply-user-sidebar">
                        <div class="user-card">
                            <div class="user-header">
                                <div class="username">
                                    ${reply.author_name}
                                    <i class="fa fa-check-circle verified" title="Usuário Verificado"></i>
                                </div>
                                <div class="user-title">Membro</div>
                            </div>
                            
                            <div class="user-avatar">
                                <img src="${reply.avatar || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRkYwMDdGIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjUgODBDMjUgNjcuNSAzNi41IDU3IDUwIDU3Uzc1IDY3LjUgNzUgODBIMjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K'}" alt="${reply.author_name}">
                                <div class="user-status online" title="Online"></div>
                            </div>
                            
                            <div class="user-badges">
                                <div class="badge simpatico-badge" title="Membro">
                                    <i class="fa fa-user"></i>
                                    Membro
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-number">${reply.likes || 0}</span>
                                    <span class="stat-label">curtidas</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="reply-content">
                        <div class="reply-header">
                            <div class="reply-info">
                                <span class="reply-title">Re: ${$('.topic-title').text()}</span>
                                <div class="reply-actions">
                                    <button class="btn btn-success btn-small like-btn" data-reply-id="${reply.id}">
                                        <i class="fa fa-thumbs-up"></i>
                                        Curtir
                                    </button>
                                    <button class="btn btn-danger btn-small report-btn" data-reply-id="${reply.id}">
                                        <i class="fa fa-flag"></i>
                                        Denunciar
                                    </button>
                                    <button class="btn btn-warning btn-small quote-btn">
                                        <i class="fa fa-quote-right"></i>
                                        Citar
                                    </button>
                                    <div class="reply-time">
                                        <i class="fa fa-clock"></i>
                                        ${reply.time_ago}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reply-body">
                            <div class="reply-text">
                                ${this.parseBBCodeToHTML(reply.content)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // ========================================
        // SISTEMA DE NOTIFICAÇÃO - USANDO O EXISTENTE (SEM ALERT)
        // ========================================
        
        showNotification(message, type = 'info') {
            // Usar apenas o sistema de notificação existente do site
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            } else if (typeof window.NotificationSystem === 'object' && window.NotificationSystem.show) {
                window.NotificationSystem.show(message, type);
            } else {
                // Fallback apenas no console - SEM ALERT
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        }
        
        // ========================================
        // POST ACTIONS
        // ========================================
        
        initPostActions() {
            // Like button com integração API
            $(document).off('click', '.like-btn').on('click', '.like-btn', async (e) => {
                const $btn = $(e.currentTarget);
                const replyId = $btn.data('reply-id');
                const isLiked = $btn.hasClass('liked');
                
                if (!replyId) {
                    this.showNotification('Erro: ID da resposta não encontrado', 'error');
                    return;
                }
                
                try {
                    const result = await this.apiRequest(`action=like_reply&id=${replyId}`);
                    
                    $btn.toggleClass('liked');
                    
                    if (isLiked) {
                        $btn.removeClass('liked').html('<i class="fa fa-thumbs-up"></i> Curtir');
                        this.showNotification('Curtida removida', 'info');
                    } else {
                        $btn.addClass('liked').html('<i class="fa fa-thumbs-up"></i> Curtido');
                        this.showNotification('Post curtido!', 'success');
                    }
                } catch (error) {
                    this.showNotification('Erro ao curtir post', 'error');
                }
            });
            
            // Quote button
            $(document).off('click', '.quote-btn').on('click', '.quote-btn', (e) => {
                const $btn = $(e.currentTarget);
                const postText = $btn.closest('.post-content, .reply-content').find('.post-text, .reply-text').text().trim();
                const username = $btn.closest('.forum-content, .reply-section').find('.username').first().text().trim();
                
                const quote = `[quote="${username}"]${postText.substring(0, 200)}${postText.length > 200 ? '...' : ''}[/quote]\n\n`;
                
                const $textarea = $('.editor-textarea');
                const currentText = $textarea.val();
                $textarea.val(quote + currentText).focus();
                
                this.showNotification('Citação adicionada ao editor', 'success');
                
                // Scroll to editor
                $('html, body').animate({
                    scrollTop: $('.reply-form-section').offset().top - 100
                }, 500);
            });
            
            // Check button (mark as resolved)
            $('.check-btn').on('click', (e) => {
                const $btn = $(e.currentTarget);
                const isResolved = $btn.hasClass('resolved');
                
                if (isResolved) {
                    $btn.removeClass('resolved').html('<i class="fa fa-check"></i>');
                    this.showNotification('Marcação de resolvido removida', 'info');
                } else {
                    $btn.addClass('resolved').html('<i class="fa fa-check"></i> Resolvido');
                    this.showNotification('Tópico marcado como resolvido!', 'success');
                }
            });
        }
        
        // ========================================
        // REPLY ACTIONS
        // ========================================
        
        initReplyActions() {
            // Report buttons com integração API
            $(document).off('click', '.report-btn').on('click', '.report-btn', async (e) => {
                e.preventDefault();
                const replyId = $(e.currentTarget).data('reply-id');
                const postType = replyId ? 'resposta' : 'tópico';
                
                if (confirm(`Tem certeza que deseja denunciar esta ${postType}?`)) {
                    try {
                        // Aqui você pode implementar o endpoint de denúncia
                        this.showNotification(`${postType.charAt(0).toUpperCase() + postType.slice(1)} denunciada! Nossa equipe irá analisar.`, 'warning');
                    } catch (error) {
                        this.showNotification('Erro ao enviar denúncia', 'error');
                    }
                }
            });
        }
        
        // ========================================
        // EDITOR FUNCTIONALITY - SEM RASCUNHO
        // ========================================
        
        initEditor() {
            const $textarea = $('.editor-textarea');
            
            // Remover eventos anteriores para evitar duplicação
            $('.editor-btn[data-tag]').off('click.forum');
            $('.editor-btn[data-tag="emoji"]').off('click.forum');
            $('.preview-btn').off('click.forum');
            $('.reply-form-section form, .editor-footer .btn-primary').off('click.forum');
            $textarea.off('input.forum click.forum focus.forum');
            
            // BBCode buttons
            $('.editor-btn[data-tag]').on('click.forum', (e) => {
                e.preventDefault();
                const tag = $(e.currentTarget).data('tag');
                if (tag !== 'emoji') {
                    const selection = ForumSystem.getSelectedText($textarea[0]);
                    ForumSystem.insertBBCode($textarea[0], tag, selection);
                }
            });
            
            // Emoji button
            $('.editor-btn[data-tag="emoji"]').on('click.forum', (e) => {
                e.preventDefault();
                ForumSystem.showEmojiPicker($textarea);
            });
            
            // Live preview
            $('.preview-btn').on('click.forum', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const $preview = $('.editor-preview');
                
                if ($btn.hasClass('active')) {
                    $btn.removeClass('active').text('Live Preview');
                    $preview.hide();
                    $textarea.show();
                } else {
                    $btn.addClass('active').text('Esconder Preview');
                    ForumSystem.showPreview($textarea, $preview);
                }
            });
            
            // Submit form com integração API - SEM EVENTO DUPLICADO
            $('.reply-form-section form, .editor-footer .btn-primary').on('click.forum', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Verificar se não é apenas um clique na textarea
                if ($(e.target).is('textarea') || $(e.target).closest('.editor-textarea').length) {
                    return;
                }
                
                await this.submitReply($textarea);
            });
            
            // Character counter - SEM trigger no click
            $textarea.on('input.forum', () => {
                const length = $textarea.val().length;
                const maxLength = 5000;
                let $counter = $('.char-counter');
                
                if (!$counter.length) {
                    $('.editor-footer').prepend(`<div class="char-counter">${length}/${maxLength} caracteres</div>`);
                    $counter = $('.char-counter');
                } else {
                    $counter.text(`${length}/${maxLength} caracteres`);
                    
                    if (length > maxLength * 0.9) {
                        $counter.addClass('warning');
                    } else {
                        $counter.removeClass('warning');
                    }
                }
            });
            
            // Evitar submit ao clicar na textarea
            $textarea.on('click.forum focus.forum', (e) => {
                e.stopPropagation();
            });
        }
        
        // ========================================
        // EDITOR HELPER METHODS
        // ========================================
        
        static getSelectedText(textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            return textarea.value.substring(start, end);
        }
        
        static insertBBCode(textarea, tag, selectedText) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const beforeText = textarea.value.substring(0, start);
            const afterText = textarea.value.substring(end);
            
            let insertText = '';
            
            switch(tag) {
                case 'b':
                    insertText = `[b]${selectedText || 'texto em negrito'}[/b]`;
                    break;
                case 'i':
                    insertText = `[i]${selectedText || 'texto em itálico'}[/i]`;
                    break;
                case 'u':
                    insertText = `[u]${selectedText || 'texto sublinhado'}[/u]`;
                    break;
                case 's':
                    insertText = `[s]${selectedText || 'texto riscado'}[/s]`;
                    break;
                case 'url':
                    const url = prompt('Digite a URL:');
                    if (url) {
                        insertText = `[url=${url}]${selectedText || 'link'}[/url]`;
                    }
                    break;
                case 'img':
                    const imgUrl = prompt('Digite a URL da imagem:');
                    if (imgUrl) {
                        insertText = `[img]${imgUrl}[/img]`;
                    }
                    break;
                default:
                    insertText = `[${tag}]${selectedText}[/${tag}]`;
            }
            
            if (insertText) {
                textarea.value = beforeText + insertText + afterText;
                textarea.focus();
                
                // Set cursor position
                const newPos = start + insertText.length;
                textarea.setSelectionRange(newPos, newPos);
            }
        }
        
        static showEmojiPicker(textarea) {
            const emojis = ['😀', '😂', '😍', '🤔', '👍', '👎', '❤️', '🔥', '💯', '🎮', '🌴', '🚗', '🏙️', '💰', '🔫'];
            
            let emojiHtml = '<div class="emoji-picker">';
            emojis.forEach(emoji => {
                emojiHtml += `<span class="emoji-item" data-emoji="${emoji}">${emoji}</span>`;
            });
            emojiHtml += '</div>';
            
            const $picker = $(emojiHtml);
            
            // Position picker
            const offset = textarea.offset();
            $picker.css({
                position: 'absolute',
                top: offset.top - 50,
                left: offset.left,
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '6px',
                padding: '8px',
                boxShadow: '0 4px 15px rgba(0,0,0,0.1)',
                zIndex: 1000
            });
            
            $('body').append($picker);
            
            // Handle emoji selection
            $picker.on('click', '.emoji-item', function() {
                const emoji = $(this).data('emoji');
                const currentText = textarea.val();
                const cursorPos = textarea[0].selectionStart;
                
                const newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                textarea.val(newText);
                
                // Set cursor after emoji
                textarea[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
                textarea.focus();
                
                $picker.remove();
                
                // Usar o sistema de notificação existente
                if (typeof window.showNotification === 'function') {
                    window.showNotification('Emoji adicionado!', 'success');
                }
            });
            
            // Close picker when clicking outside
            $(document).one('click', function(e) {
                if (!$(e.target).closest('.emoji-picker, .editor-btn[data-tag="emoji"]').length) {
                    $picker.remove();
                }
            });
        }
        
        static showPreview(textarea, previewContainer) {
            if (!previewContainer.length) {
                previewContainer = $('<div class="editor-preview"></div>');
                textarea.after(previewContainer);
            }
            
            const text = textarea.val();
            const previewHtml = ForumSystem.parseBBCode(text);
            
            previewContainer.html(previewHtml).show();
            textarea.hide();
        }
        
        static parseBBCode(text) {
            if (!text) return '<p><em>Digite algo para ver o preview...</em></p>';
            
            // Basic BBCode parsing
            let html = text
                .replace(/\[b\](.*?)\[\/b\]/g, '<strong>$1</strong>')
                .replace(/\[i\](.*?)\[\/i\]/g, '<em>$1</em>')
                .replace(/\[u\](.*?)\[\/u\]/g, '<u>$1</u>')
                .replace(/\[s\](.*?)\[\/s\]/g, '<s>$1</s>')
                .replace(/\[url=(.*?)\](.*?)\[\/url\]/g, '<a href="$1" target="_blank">$2</a>')
                .replace(/\[img\](.*?)\[\/img\]/g, '<img src="$1" style="max-width: 100%; height: auto;">')
                .replace(/\[quote="(.*?)"\](.*?)\[\/quote\]/g, '<blockquote><strong>$1 disse:</strong><br>$2</blockquote>')
                .replace(/\[quote\](.*?)\[\/quote\]/g, '<blockquote>$1</blockquote>')
                .replace(/\n/g, '<br>');
            
            return `<div class="preview-content">${html}</div>`;
        }
        
        parseBBCodeToHTML(text) {
            // Método para converter BBCode em HTML para exibição
            return ForumSystem.parseBBCode(text);
        }
        
        async submitReply(textarea) {
            const content = textarea.val().trim();
            
            if (!content) {
                this.showNotification('Digite uma mensagem antes de enviar!', 'warning');
                textarea.focus();
                return;
            }
            
            if (content.length < 10) {
                this.showNotification('A mensagem deve ter pelo menos 10 caracteres!', 'warning');
                textarea.focus();
                return;
            }
            
            // Verificar se já está processando para evitar envios duplos
            if (this.isSubmitting) {
                console.log('Já está enviando, ignorando...');
                return;
            }
            
            this.isSubmitting = true;
            
            // SEMPRE verificar o campo hidden antes de enviar
            const currentTopicId = this.getCurrentTopicId();
            
            if (!currentTopicId) {
                this.showNotification('Erro: ID do tópico não encontrado. Verifique se existe o campo <input name="topic_id">.', 'error');
                console.error('Elementos na página:', {
                    'input[name="topic_id"]': $('input[name="topic_id"]'),
                    'valor_do_campo': $('input[name="topic_id"]').val(),
                    'url_atual': window.location.pathname
                });
                this.isSubmitting = false;
                return;
            }
            
            // Atualizar o currentTopicId se mudou
            this.currentTopicId = currentTopicId;
            
            console.log('Enviando resposta:', {
                content: content,
                topic_id: this.currentTopicId,
                content_length: content.length,
                campo_hidden_valor: $('input[name="topic_id"]').val()
            });
            
            // Disable form
            $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', true);
            $('.btn-primary').html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
            
            try {
                const data = {
                    type: 'reply',
                    content: content,
                    topic_id: this.currentTopicId
                };
                
                console.log('Dados sendo enviados para API:', data);
                
                const result = await this.apiRequest('', 'POST', data);
                
                this.showNotification('Resposta enviada com sucesso!', 'success');
                
                // Reset form
                textarea.val('');
                $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', false);
                $('.btn-primary').html('<i class="fa fa-paper-plane"></i> Enviar');
                
                // Simular adição do novo comentário sem recarregar
                this.addNewReplyToPage(content);
                
            } catch (error) {
                $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', false);
                $('.btn-primary').html('<i class="fa fa-paper-plane"></i> Enviar');
                this.showNotification('Erro ao enviar resposta: ' + error.message, 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
        
        addNewReplyToPage(content) {
            // Adicionar o novo comentário na página sem recarregar
            const newReplyHtml = `
                <div class="reply-section new-reply" style="opacity: 0;">
                    <aside class="reply-user-sidebar">
                        <div class="user-card">
                            <div class="user-header">
                                <div class="username">
                                    Você
                                    <i class="fa fa-check-circle verified" title="Usuário Verificado"></i>
                                </div>
                                <div class="user-title">Membro</div>
                            </div>
                            
                            <div class="user-avatar">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRkYwMDdGIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjUgODBDMjUgNjcuNSAzNi41IDU3IDUwIDU3Uzc1IDY3LjUgNzUgODBIMjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K" alt="Você">
                                <div class="user-status online" title="Online"></div>
                            </div>
                            
                            <div class="user-badges">
                                <div class="badge simpatico-badge" title="Membro">
                                    <i class="fa fa-user"></i>
                                    Membro
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-number">0</span>
                                    <span class="stat-label">curtidas</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="reply-content">
                        <div class="reply-header">
                            <div class="reply-info">
                                <span class="reply-title">Re: ${$('.topic-title').text() || 'Tópico'}</span>
                                <div class="reply-actions">
                                    <div class="reply-time">
                                        <i class="fa fa-clock"></i>
                                        agora mesmo
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reply-body">
                            <div class="reply-text">
                                ${this.parseBBCodeToHTML(content)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const $newReply = $(newReplyHtml);
            $('.reply-form-section').before($newReply);
            
            // Animate in
            $newReply.animate({opacity: 1}, 600);
            
            // Scroll to new reply
            $('html, body').animate({
                scrollTop: $newReply.offset().top - 100
            }, 800);
        }
        
        // ========================================
        // PAGE ACTIONS
        // ========================================
        
        initPageActions() {
            // Breadcrumb navigation
            $('.breadcrumb-item').on('click', function(e) {
                if ($(this).attr('href') && $(this).attr('href') !== '#') {
                    return; // Let normal navigation happen
                }
                
                e.preventDefault();
                const text = $(this).text().trim();
                if (text !== 'Teorias sobre o final de GTA VI') {
                    // Usar sistema de notificação existente
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(`Navegando para: ${text}`, 'info');
                    }
                }
            });
            
            // Page action buttons
            $('.info-btn').on('click', function() {
                ForumSystem.showInfoModal();
            });
            
            $('.help-btn').on('click', function() {
                ForumSystem.showHelpModal();
            });
            
            $('.report-btn').on('click', function() {
                ForumSystem.showReportModal();
            });
            
            // Forum rules and BBCode buttons
            $('.btn-secondary').on('click', function() {
                if ($(this).text().includes('Regras')) {
                    ForumSystem.showRulesModal();
                }
            });
            
            $('.btn[class*="BBCode"]').on('click', function() {
                ForumSystem.showBBCodeHelp();
            });
        }
        
        updatePagination(meta) {
            if (!meta || !meta.total_pages || meta.total_pages <= 1) {
                $('.pagination').hide();
                return;
            }
            
            const { current_page, total_pages, has_prev, has_next } = meta;
            
            let paginationHtml = '<div class="pagination">';
            
            // Previous button
            if (has_prev) {
                paginationHtml += `<a href="#" class="pagination-btn" data-page="${current_page - 1}"><i class="fa fa-angle-left"></i></a>`;
            } else {
                paginationHtml += `<span class="pagination-btn disabled"><i class="fa fa-angle-left"></i></span>`;
            }
            
            // Page numbers
            for (let i = 1; i <= total_pages; i++) {
                if (i === current_page) {
                    paginationHtml += `<span class="pagination-btn active">${i}</span>`;
                } else {
                    paginationHtml += `<a href="#" class="pagination-btn" data-page="${i}">${i}</a>`;
                }
            }
            
            // Next button
            if (has_next) {
                paginationHtml += `<a href="#" class="pagination-btn" data-page="${current_page + 1}"><i class="fa fa-angle-right"></i></a>`;
            } else {
                paginationHtml += `<span class="pagination-btn disabled"><i class="fa fa-angle-right"></i></span>`;
            }
            
            paginationHtml += '</div>';
            
            $('.pagination').html(paginationHtml);
            
            // Handle pagination clicks
            $('.pagination-btn[data-page]').on('click', async (e) => {
                e.preventDefault();
                const page = parseInt($(e.currentTarget).data('page'));
                if (page && page !== current_page) {
                    this.currentPage = page;
                    await this.loadTopicData();
                }
            });
        }
        
        // ========================================
        // MODAL SYSTEMS (mantendo os originais)
        // ========================================
        
        static showInfoModal() {
            const modalContent = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-info-circle"></i> Informações do Tópico</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Autor:</strong> TheoryMaster
                                </div>
                                <div class="info-item">
                                    <strong>Criado em:</strong> 17 de abril de 2025
                                </div>
                                <div class="info-item">
                                    <strong>Última resposta:</strong> há 1 dia
                                </div>
                                <div class="info-item">
                                    <strong>Respostas:</strong> 2
                                </div>
                                <div class="info-item">
                                    <strong>Visualizações:</strong> 1,247
                                </div>
                                <div class="info-item">
                                    <strong>Status:</strong> <span class="status-closed">Fechado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            ForumSystem.showModal(modalContent);
        }
        
        static showHelpModal() {
            const modalContent = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-question-circle"></i> Ajuda do Fórum</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="help-sections">
                                <div class="help-section">
                                    <h4>Como postar</h4>
                                    <p>Use o editor na parte inferior da página para escrever sua resposta. Você pode usar BBCode para formatar o texto.</p>
                                </div>
                                <div class="help-section">
                                    <h4>BBCode básico</h4>
                                    <ul>
                                        <li><code>[b]texto[/b]</code> - <strong>Negrito</strong></li>
                                        <li><code>[i]texto[/i]</code> - <em>Itálico</em></li>
                                        <li><code>[url=link]texto[/url]</code> - Link</li>
                                        <li><code>[img]url[/img]</code> - Imagem</li>
                                    </ul>
                                </div>
                                <div class="help-section">
                                    <h4>Regras importantes</h4>
                                    <ul>
                                        <li>Seja respeitoso com outros membros</li>
                                        <li>Não faça spam ou flood</li>
                                        <li>Mantenha o tópico relacionado ao assunto</li>
                                        <li>Use a função de busca antes de criar novos tópicos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            ForumSystem.showModal(modalContent);
        }
        
        static showReportModal() {
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
                                        <option>Spam ou conteúdo irrelevante</option>
                                        <option>Linguagem ofensiva</option>
                                        <option>Conteúdo inapropriado</option>
                                        <option>Violação das regras</option>
                                        <option>Problema técnico</option>
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
            ForumSystem.showModal($modal);
            
            // Handle form submission
            $modal.find('.report-form').on('submit', function(e) {
                e.preventDefault();
                // Usar sistema de notificação existente
                if (typeof window.showNotification === 'function') {
                    window.showNotification('Relatório enviado! Nossa equipe irá analisar.', 'success');
                }
                ForumSystem.closeModal();
            });
        }
        
        static showRulesModal() {
            const modalContent = `
                <div class="modal-overlay">
                    <div class="modal-content large">
                        <div class="modal-header">
                            <h3><i class="fa fa-gavel"></i> Regras do Fórum</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="rules-content">
                                <div class="rule-section">
                                    <h4>1. Respeito e Cortesia</h4>
                                    <p>Trate todos os membros com respeito. Não toleramos ofensas, discriminação ou assédio de qualquer tipo.</p>
                                </div>
                                
                                <div class="rule-section">
                                    <h4>2. Conteúdo Apropriado</h4>
                                    <p>Mantenha as discussões relacionadas ao GTA VI e temas pertinentes. Evite conteúdo adulto, violento ou inapropriado.</p>
                                </div>
                                
                                <div class="rule-section">
                                    <h4>3. Sem Spam</h4>
                                    <p>Não faça posts repetitivos, irrelevantes ou promocionais. Uma mensagem por vez é suficiente.</p>
                                </div>
                                
                                <div class="rule-section">
                                    <h4>4. Use a Busca</h4>
                                    <p>Antes de criar um novo tópico, verifique se já existe discussão sobre o assunto.</p>
                                </div>
                                
                                <div class="rule-section">
                                    <h4>5. Fontes Confiáveis</h4>
                                    <p>Ao compartilhar informações, cite fontes confiáveis e evite espalhar rumores não confirmados.</p>
                                </div>
                                
                                <div class="rule-section warning">
                                    <h4>⚠️ Consequências</h4>
                                    <p>O descumprimento das regras pode resultar em advertência, suspensão temporária ou banimento permanente.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            ForumSystem.showModal(modalContent);
        }
        
        static showBBCodeHelp() {
            const modalContent = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-code"></i> Guia BBCode</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="bbcode-guide">
                                <div class="bbcode-section">
                                    <h4>Formatação de Texto</h4>
                                    <div class="bbcode-example">
                                        <code>[b]Negrito[/b]</code> → <strong>Negrito</strong>
                                    </div>
                                    <div class="bbcode-example">
                                        <code>[i]Itálico[/i]</code> → <em>Itálico</em>
                                    </div>
                                    <div class="bbcode-example">
                                        <code>[u]Sublinhado[/u]</code> → <u>Sublinhado</u>
                                    </div>
                                    <div class="bbcode-example">
                                        <code>[s]Riscado[/s]</code> → <s>Riscado</s>
                                    </div>
                                </div>
                                
                                <div class="bbcode-section">
                                    <h4>Links e Imagens</h4>
                                    <div class="bbcode-example">
                                        <code>[url=https://leonidabrasil.com]Link[/url]</code>
                                    </div>
                                    <div class="bbcode-example">
                                        <code>[img]https://exemplo.com/imagem.jpg[/img]</code>
                                    </div>
                                </div>
                                
                                <div class="bbcode-section">
                                    <h4>Citações</h4>
                                    <div class="bbcode-example">
                                        <code>[quote="Usuário"]Texto citado[/quote]</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            ForumSystem.showModal(modalContent);
        }
        
        static showModal(content) {
            const $modal = $(content);
            $('body').append($modal);
            
            // Animate in
            setTimeout(() => {
                $modal.addClass('show');
            }, 10);
            
            // Close handlers
            $modal.find('.modal-close').on('click', ForumSystem.closeModal);
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('modal-overlay')) {
                    ForumSystem.closeModal();
                }
            });
            
            // ESC key
            $(document).on('keydown.modal', function(e) {
                if (e.key === 'Escape') {
                    ForumSystem.closeModal();
                }
            });
        }
        
        static closeModal() {
            const $modal = $('.modal-overlay');
            $modal.removeClass('show');
            
            setTimeout(() => {
                $modal.remove();
                $(document).off('keydown.modal');
            }, 300);
        }
        
        // ========================================
        // USER INTERACTIONS
        // ========================================
        
        initUserInteractions() {
            // User avatar hover
            $('.user-avatar').hover(
                function() {
                    $(this).find('.user-status').addClass('pulse');
                },
                function() {
                    $(this).find('.user-status').removeClass('pulse');
                }
            );
            
            // Badge hover effects
            $('.badge').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );
            
            // User card click
            $('.user-card').on('click', '.username', function() {
                const username = $(this).text().trim();
                if (typeof window.showNotification === 'function') {
                    window.showNotification(`Visualizando perfil de ${username}`, 'info');
                }
            });
            
            // Status indicator tooltip
            $('.user-status').each(function() {
                const title = $(this).attr('title');
                if (title && typeof $(this).tooltip === 'function') {
                    $(this).tooltip({
                        placement: 'top',
                        container: 'body'
                    });
                }
            });
        }
        
        // ========================================
        // TOPIC MANAGEMENT
        // ========================================
        
        async loadTopics(categoryId = null, page = 1) {
            try {
                let endpoint = `page=${page}`;
                if (categoryId) {
                    endpoint += `&category=${categoryId}`;
                }
                
                const result = await this.apiRequest(endpoint);
                this.renderTopicsList(result.data);
                this.updatePagination(result.meta);
                
                return result;
            } catch (error) {
                console.error('Error loading topics:', error);
                this.showNotification('Erro ao carregar tópicos', 'error');
            }
        }
        
        renderTopicsList(topics) {
            const $container = $('.topics-list, .forum-topics');
            if (!$container.length) return;
            
            $container.empty();
            
            topics.forEach(topic => {
                const topicHtml = this.buildTopicHTML(topic);
                $container.append(topicHtml);
            });
        }
        
        buildTopicHTML(topic) {
            return `
                <div class="topic-item ${topic.is_pinned ? 'pinned' : ''}" data-topic-id="${topic.id}">
                    <div class="topic-icon">
                        ${topic.is_pinned ? '<i class="fa fa-thumbtack"></i>' : '<i class="fa fa-comment"></i>'}
                    </div>
                    <div class="topic-content">
                        <h3 class="topic-title">
                            <a href="/forum/topico/${topic.id}">${topic.title}</a>
                            ${topic.is_locked ? '<i class="fa fa-lock"></i>' : ''}
                        </h3>
                        <div class="topic-meta">
                            <span class="topic-author">por ${topic.author_name}</span>
                            <span class="topic-category">${topic.category_name}</span>
                            <span class="topic-time">${topic.time_ago}</span>
                        </div>
                        <div class="topic-excerpt">${topic.excerpt || ''}</div>
                    </div>
                    <div class="topic-stats">
                        <div class="stat">
                            <span class="stat-number">${topic.replies_count}</span>
                            <span class="stat-label">respostas</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">${topic.formatted_views}</span>
                            <span class="stat-label">visualizações</span>
                        </div>
                    </div>
                    <div class="topic-last-post">
                        ${topic.last_reply_author ? `
                            <div class="last-reply-author">${topic.last_reply_author}</div>
                            <div class="last-reply-time">${topic.last_reply_time_ago}</div>
                        ` : `
                            <div class="last-reply-author">${topic.author_name}</div>
                            <div class="last-reply-time">${topic.time_ago}</div>
                        `}
                    </div>
                </div>
            `;
        }
        
        // ========================================
        // SEARCH FUNCTIONALITY
        // ========================================
        
        async searchForum(query) {
            try {
                const result = await this.apiRequest(`action=search&q=${encodeURIComponent(query)}`);
                this.renderSearchResults(result.data);
                return result;
            } catch (error) {
                console.error('Error searching forum:', error);
                this.showNotification('Erro na busca', 'error');
            }
        }
        
        renderSearchResults(results) {
            const $container = $('.search-results');
            if (!$container.length) return;
            
            $container.empty();
            
            if (results.length === 0) {
                $container.html('<div class="no-results">Nenhum resultado encontrado.</div>');
                return;
            }
            
            results.forEach(result => {
                const resultHtml = `
                    <div class="search-result">
                        <h4><a href="${result.url}">${result.highlight}</a></h4>
                        <div class="result-meta">
                            <span class="result-type">${this.getResultTypeLabel(result.type)}</span>
                            <span class="result-author">por ${result.author_name}</span>
                            <span class="result-time">${result.time_ago}</span>
                        </div>
                        <div class="result-excerpt">${result.excerpt_highlight}</div>
                    </div>
                `;
                $container.append(resultHtml);
            });
        }
        
        getResultTypeLabel(type) {
            const labels = {
                'topic': 'Tópico',
                'reply': 'Resposta'
            };
            return labels[type] || type;
        }
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    // Initialize forum system
    const forumSystem = new ForumSystem();
    
    // Add forum-specific styles (apenas os modals)
    const forumStyles = `
        <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show {
            opacity: 1;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.show .modal-content {
            transform: scale(1);
        }
        
        .modal-content.large {
            max-width: 700px;
        }
        
        .modal-header {
            background: var(--color-primary, #007cba);
            color: white;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .info-item {
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .status-closed {
            color: #dc3545;
            font-weight: 600;
        }
        
        .help-sections,
        .rules-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .help-section,
        .rule-section {
            padding: 12px;
            border-left: 4px solid var(--color-primary, #007cba);
            background: #f8f9fa;
            border-radius: 0 4px 4px 0;
        }
        
        .rule-section.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .help-section h4,
        .rule-section h4 {
            margin: 0 0 8px 0;
            color: var(--color-primary, #007cba);
            font-size: 14px;
        }
        
        .bbcode-guide {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .bbcode-section h4 {
            margin: 0 0 8px 0;
            color: var(--color-primary, #007cba);
        }
        
        .bbcode-example {
            margin: 4px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .bbcode-example code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 2px;
            font-family: monospace;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 16px;
        }
        
        .emoji-picker {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 4px;
        }
        
        .emoji-item {
            padding: 8px;
            text-align: center;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s ease;
        }
        
        .emoji-item:hover {
            background: #f8f9fa;
        }
        
        .char-counter {
            font-size: 11px;
            color: #6c757d;
        }
        
        .char-counter.warning {
            color: #dc3545;
            font-weight: 600;
        }
        
        .editor-preview {
            border: 1px solid #e9ecef;
            border-top: none;
            border-bottom: none;
            padding: 12px;
            background: white;
            min-height: 120px;
        }
        
        .preview-content {
            font-size: 13px;
            line-height: 1.6;
        }
        
        .preview-content blockquote {
            background: #f8f9fa;
            border-left: 4px solid var(--color-primary, #007cba);
            padding: 8px 12px;
            margin: 8px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .user-status.pulse {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .badge.hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .new-reply {
            border-left: 4px solid var(--color-success, #28a745);
        }
        
        /* Topic and search result styles */
        .topic-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
        }
        
        .topic-item.pinned {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .topic-icon {
            font-size: 18px;
            color: #6c757d;
        }
        
        .topic-content h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        
        .topic-meta {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            gap: 12px;
        }
        
        .topic-stats {
            text-align: center;
        }
        
        .stat {
            margin-bottom: 4px;
        }
        
        .stat-number {
            display: block;
            font-weight: 600;
            font-size: 16px;
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
        }
        
        .search-result {
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-result h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        
        .result-meta {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .result-excerpt {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        </style>
    `;
    
    $('head').append(forumStyles);
    
    // Welcome message for forum usando sistema existente
    setTimeout(() => {
        if (typeof window.showNotification === 'function') {
            window.showNotification('Bem-vindo ao Fórum Leonida Brasil! 💬', 'info');
        }
    }, 1500);
    
    console.log('🎮 Forum system loaded successfully with API integration!');
});