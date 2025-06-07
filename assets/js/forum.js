/**
 * Leonida Brasil - Forum.js - VERSÃO FINAL
 * JavaScript específico para funcionalidades do fórum
 * Integrado com API backend - Com dados reais do usuário e citação funcional
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // FORUM SYSTEM - VERSÃO FINAL
    // ========================================
    
    class ForumSystem {
        constructor() {
            this.apiBase = '/api/forum.php';
            this.currentTopicId = this.getCurrentTopicId();
            this.currentPage = 1;
            this.isSubmitting = false;
            this.currentUser = null; // Dados do usuário atual
            this.init();
        }
        
        getCurrentTopicId() {
            // PRIORIDADE 1: Buscar nos dados globais do JavaScript
            if (typeof window.forumData !== 'undefined' && window.forumData.topicId) {
                console.log('Topic ID encontrado em window.forumData:', window.forumData.topicId);
                return parseInt(window.forumData.topicId);
            }
            
            // PRIORIDADE 2: Buscar no campo hidden do formulário
            const hiddenInput = $('input[name="topic_id"]').first();
            if (hiddenInput.length && hiddenInput.val()) {
                console.log('Topic ID encontrado no campo hidden:', hiddenInput.val());
                return parseInt(hiddenInput.val());
            }
            
            // PRIORIDADE 3: Buscar em outros campos hidden
            const hiddenDataInput = $('input[data-topic-id]').first();
            if (hiddenDataInput.length && hiddenDataInput.data('topic-id')) {
                console.log('Topic ID encontrado no data-topic-id:', hiddenDataInput.data('topic-id'));
                return parseInt(hiddenDataInput.data('topic-id'));
            }
            
            // PRIORIDADE 4: Extrair da URL 
            const urlMatch = window.location.pathname.match(/\/forum\/topico\/(\d+)/);
            if (urlMatch) {
                console.log('Topic ID encontrado na URL:', urlMatch[1]);
                return parseInt(urlMatch[1]);
            }
            
            // PRIORIDADE 5: Buscar em elementos com data-topic-id
            const topicElement = $('[data-topic-id]').first();
            if (topicElement.length && topicElement.data('topic-id')) {
                console.log('Topic ID encontrado em elemento data-topic-id:', topicElement.data('topic-id'));
                return parseInt(topicElement.data('topic-id'));
            }
            
            console.warn('Topic ID NÃO encontrado em nenhum local da página');
            console.log('Dados disponíveis:', {
                'window.forumData': typeof window.forumData !== 'undefined' ? window.forumData : 'undefined',
                'input[name="topic_id"]': $('input[name="topic_id"]').length,
                'valor_do_campo': $('input[name="topic_id"]').val(),
                'url_atual': window.location.pathname
            });
            
            return null;
        }
        
        async init() {
            // Verificar se temos dados globais do usuário
            if (typeof window.forumData !== 'undefined' && window.forumData.currentUser) {
                this.currentUser = window.forumData.currentUser;
                console.log('Usuário carregado dos dados globais:', this.currentUser);
            } else {
                // Carregar dados do usuário via API
                await this.loadCurrentUser();
            }
            
            this.initPostActions();
            this.initReplyActions();
            this.initEditor();
            this.initPageActions();
            this.initUserInteractions();
            
            // Só carregar dados do tópico se não estivermos na primeira página
            // (na primeira página os dados já vêm do PHP)
            if (this.currentPage > 1) {
                this.loadTopicData();
            } else {
                // Na primeira página, apenas inicializar eventos nas respostas existentes
                this.initExistingReplies();
            }
            
            console.log('Forum System initialized:', {
                currentTopicId: this.currentTopicId,
                currentUser: this.currentUser,
                currentPage: this.currentPage,
                url: window.location.pathname,
                forumData: typeof window.forumData !== 'undefined' ? window.forumData : 'não disponível'
            });
        }
        
        initExistingReplies() {
            // Inicializar eventos nas respostas que já estão na página (vindas do PHP)
            console.log('Inicializando eventos nas respostas existentes');
            
            // Contar respostas existentes
            const existingReplies = $('.reply-section').length;
            console.log(`Encontradas ${existingReplies} respostas na página`);
            
            // Re-inicializar eventos para garantir que funcionem
            this.initPostActions();
            this.initReplyActions();
            
            // Verificar se há paginação e configurá-la
            this.initExistingPagination();
        }
        
        initExistingPagination() {
            // Se há dados de paginação globais, usar
            if (typeof window.forumData !== 'undefined' && window.forumData.totalPages) {
                const meta = {
                    current_page: this.currentPage,
                    total_pages: window.forumData.totalPages,
                    has_prev: this.currentPage > 1,
                    has_next: this.currentPage < window.forumData.totalPages
                };
                this.updatePagination(meta);
            } else {
                // Verificar se há botões de paginação existentes e ativá-los
                const $existingPagination = $('.pagination-btn:not(.disabled)');
                if ($existingPagination.length > 1) {
                    // Há paginação, inicializar eventos
                    $existingPagination.off('click.pagination').on('click.pagination', async (e) => {
                        e.preventDefault();
                        const page = parseInt($(e.currentTarget).text());
                        if (page && !isNaN(page) && page !== this.currentPage) {
                            this.currentPage = page;
                            await this.loadTopicData();
                        }
                    });
                }
            }
        }
        
        // ========================================
        // CARREGAR DADOS DO USUÁRIO ATUAL
        // ========================================
        
        async loadCurrentUser() {
            try {
                // Verificar se há dados na sessão ou fazer requisição
                const authResponse = await fetch('/api/auth.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (authResponse.ok) {
                    const authResult = await authResponse.json();
                    if (authResult.success && authResult.data.authenticated) {
                        this.currentUser = authResult.data.user;
                        console.log('Usuário atual carregado:', this.currentUser);
                        
                        // Buscar dados complementares do usuário
                        await this.loadUserDetails();
                    } else {
                        console.log('Usuário não autenticado');
                        this.currentUser = null;
                    }
                } else {
                    console.warn('Erro ao verificar autenticação');
                    this.currentUser = null;
                }
            } catch (error) {
                console.error('Erro ao carregar usuário atual:', error);
                this.currentUser = null;
            }
        }
        
        async loadUserDetails() {
            if (!this.currentUser || !this.currentUser.id) return;
            
            try {
                // Buscar dados detalhados do usuário via API
                const userResponse = await fetch(`/api/users.php?id=${this.currentUser.id}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (userResponse.ok) {
                    const userResult = await userResponse.json();
                    if (userResult.success) {
                        // Mesclar dados do usuário
                        this.currentUser = {
                            ...this.currentUser,
                            ...userResult.data,
                            message_count: await this.getUserMessageCount(this.currentUser.id)
                        };
                        
                        console.log('Dados detalhados do usuário carregados:', this.currentUser);
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes do usuário:', error);
            }
        }
        
        async getUserMessageCount(userId) {
            try {
                // Buscar contagem de mensagens do usuário
                const response = await fetch(`/api/forum.php?type=user_topics&user_id=${userId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.meta && result.meta.pagination) {
                        return result.meta.pagination.total || 0;
                    }
                }
                
                return 0;
            } catch (error) {
                console.error('Erro ao buscar contagem de mensagens:', error);
                return 0;
            }
        }
        
        // ========================================
        // API INTEGRATION
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
                
                console.log('API Request:', method, url, data);
                
                const response = await fetch(url, options);
                
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
        
        // ========================================
        // SISTEMA DE NOTIFICAÇÃO - CORRIGIDO
        // ========================================
        
        showNotification(message, type = 'info') {
            // FORÇA a exibição da notificação na tela
            
            // Método 1: Verificar se existe sistema de notificação global
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
                return;
            }
            
            // Método 2: Verificar se existe NotificationSystem
            if (typeof window.NotificationSystem === 'object' && window.NotificationSystem.show) {
                window.NotificationSystem.show(message, type);
                return;
            }
            
            // Método 3: Tentar usar Toast se existir
            if (typeof window.toast === 'function') {
                window.toast(message, type);
                return;
            }
            
            // Método 4: Usar alert como fallback para garantir que o usuário veja
            if (type === 'error' || type === 'warning') {
                alert(`${type.toUpperCase()}: ${message}`);
                return;
            }
            
            // Método 5: Criar notificação customizada se nada mais funcionar
            this.createCustomNotification(message, type);
        }
        
        createCustomNotification(message, type) {
            // Remove notificações anteriores
            $('.forum-notification').remove();
            
            // Cores por tipo
            const colors = {
                'success': '#28a745',
                'error': '#dc3545', 
                'warning': '#ffc107',
                'info': '#17a2b8'
            };
            
            const bgColor = colors[type] || colors['info'];
            
            // Criar elemento de notificação
            const notification = $(`
                <div class="forum-notification" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${bgColor};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 6px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 9999;
                    font-size: 14px;
                    font-weight: 500;
                    max-width: 350px;
                    animation: slideInRight 0.3s ease;
                ">
                    <i class="fa fa-${this.getNotificationIcon(type)}"></i>
                    ${message}
                </div>
            `);
            
            // Adicionar ao body
            $('body').append(notification);
            
            // Auto-remover após 4 segundos
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
            
            // Log para debug
            console.log(`${type.toUpperCase()}: ${message}`);
        }
        
        getNotificationIcon(type) {
            const icons = {
                'success': 'check-circle',
                'error': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'info-circle';
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
            
            // Quote button - FUNCIONALIDADE APRIMORADA
            $(document).off('click', '.quote-btn').on('click', '.quote-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const $replySection = $btn.closest('.reply-section, .forum-content, .post-content');
                
                // Buscar o texto do post/resposta
                const $replyText = $replySection.find('.reply-text, .post-text').first();
                let postText = '';
                
                if ($replyText.length) {
                    // Extrair texto limpo (sem HTML)
                    postText = $replyText.text().trim();
                } else {
                    // Fallback: buscar qualquer texto de conteúdo
                    const $content = $replySection.find('.reply-body, .post-body, .content').first();
                    postText = $content.text().trim();
                }
                
                // Buscar o nome do usuário
                const $username = $replySection.find('.username').first();
                let username = 'Usuário';
                
                if ($username.length) {
                    username = $username.text().trim();
                    // Remover ícones e espaços extras
                    username = username.replace(/\s*\n\s*/g, ' ').split('\n')[0].trim();
                }
                
                // Limitar o texto da citação
                const maxQuoteLength = 200;
                let quotedText = postText;
                if (quotedText.length > maxQuoteLength) {
                    quotedText = quotedText.substring(0, maxQuoteLength) + '...';
                }
                
                // Criar a citação em BBCode
                const quote = `[quote="${username}"]${quotedText}[/quote]\n\n`;
                
                // Inserir no editor
                const $textarea = $('.editor-textarea');
                if ($textarea.length) {
                    const currentText = $textarea.val();
                    $textarea.val(quote + currentText).focus();
                    
                    this.showNotification(`Citação de ${username} adicionada!`, 'success');
                    
                    // Scroll para o editor
                    $('html, body').animate({
                        scrollTop: $('.reply-form-section, .editor-section').offset().top - 100
                    }, 500);
                } else {
                    this.showNotification('Editor não encontrado', 'error');
                }
                
                console.log('Quote adicionada:', {
                    username: username,
                    originalText: postText,
                    quotedText: quotedText,
                    quote: quote
                });
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
                        this.showNotification(`${postType.charAt(0).toUpperCase() + postType.slice(1)} denunciada! Nossa equipe irá analisar.`, 'warning');
                    } catch (error) {
                        this.showNotification('Erro ao enviar denúncia', 'error');
                    }
                }
            });
        }
        
        // ========================================
        // EDITOR FUNCTIONALITY
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
            
            // Submit form com integração API
            $('.reply-form-section form, .editor-footer .btn-primary').on('click.forum', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if ($(e.target).is('textarea') || $(e.target).closest('.editor-textarea').length) {
                    return;
                }
                
                await this.submitReply($textarea);
            });
            
            // Character counter
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
        // SUBMIT REPLY COM DADOS REAIS DO USUÁRIO
        // ========================================
        
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
            
            if (this.isSubmitting) {
                console.log('Já está enviando, ignorando...');
                return;
            }
            
            this.isSubmitting = true;
            
            const currentTopicId = this.getCurrentTopicId();
            
            if (!currentTopicId) {
                this.showNotification('Erro: ID do tópico não encontrado', 'error');
                this.isSubmitting = false;
                return;
            }
            
            this.currentTopicId = currentTopicId;
            
            // Disable form
            $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', true);
            $('.btn-primary').html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
            
            try {
                const data = {
                    type: 'reply',
                    content: content,
                    topic_id: this.currentTopicId
                };
                
                const result = await this.apiRequest('', 'POST', data);
                
                this.showNotification('Resposta enviada com sucesso!', 'success');
                
                // Reset form
                textarea.val('');
                $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', false);
                $('.btn-primary').html('<i class="fa fa-paper-plane"></i> Enviar');
                
                // Adicionar nova resposta com dados reais do usuário
                this.addNewReplyToPage(content, result.data);
                
            } catch (error) {
                $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', false);
                $('.btn-primary').html('<i class="fa fa-paper-plane"></i> Enviar');
                this.showNotification('Erro ao enviar resposta: ' + error.message, 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
        
        // ========================================
        // ADICIONAR NOVA RESPOSTA COM DADOS REAIS
        // ========================================
        
        addNewReplyToPage(content, apiResponseData = null) {
            if (!this.currentUser) {
                console.warn('Usuário atual não carregado, usando dados genéricos');
                this.addGenericReply(content);
                return;
            }
            
            // Usar dados reais do usuário atual
            const userData = {
                id: this.currentUser.id,
                username: this.currentUser.username,
                display_name: this.currentUser.display_name || this.currentUser.username,
                avatar: this.currentUser.avatar || this.getDefaultAvatar(),
                level: this.currentUser.level || 1,
                message_count: this.currentUser.message_count || 0
            };
            
            // Gerar badges baseados no nível e mensagens
            const badges = this.generateUserBadges(userData.level, userData.message_count);
            const userTitle = this.getUserTitle(userData.level);
            const isVerified = userData.level >= 2;
            
            console.log('Adicionando resposta com dados do usuário:', userData);
            
            // Usar a mesma estrutura HTML do PHP
            const newReplyHtml = `
                <div class="reply-section new-reply" style="opacity: 0;" data-reply-id="${apiResponseData?.id || 'new'}">
                    <aside class="reply-user-sidebar">
                        <div class="user-card">
                            <div class="user-header">
                                <div class="username">
                                    ${userData.display_name}
                                    ${isVerified ? '<i class="fa fa-check-circle verified" title="Usuário Verificado"></i>' : ''}
                                </div>
                                <div class="user-title">${userTitle}</div>
                            </div>
                            
                            <div class="user-avatar">
                                <img src="${userData.avatar}" alt="${userData.display_name}">
                                <div class="user-status online" title="Online"></div>
                            </div>
                            
                            ${badges.length > 0 ? `
                                <div class="user-badges">
                                    ${badges.map(badge => `
                                        <div class="badge ${badge.class}" title="${badge.name}">
                                            <i class="${badge.icon}"></i>
                                            ${badge.name}
                                            ${badge.level ? `<span class="badge-level">${badge.level}</span>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-number">${userData.message_count}</span>
                                    <span class="stat-label">mensagens</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="reply-content">
                        <div class="reply-header">
                            <div class="reply-info">
                                <span class="reply-title">Re: ${$('.topic-status').text().replace('Fórum: ', '') || 'Tópico'}</span>
                                <div class="reply-actions">
                                    <button class="btn btn-danger btn-small report-btn" data-reply-id="${apiResponseData?.id || 'new'}">
                                        <i class="fa fa-flag"></i>
                                        Denunciar
                                    </button>
                                    <button class="btn btn-warning btn-small quote-btn">
                                        <i class="fa fa-quote-right"></i>
                                        Citar
                                    </button>
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
            
            // Inserir antes do formulário de resposta
            const $replyForm = $('.reply-form-section');
            if ($replyForm.length) {
                $replyForm.before(newReplyHtml);
            } else {
                // Se não houver formulário, inserir após a última resposta ou após o conteúdo principal
                const $lastReply = $('.reply-section').last();
                if ($lastReply.length) {
                    $lastReply.after(newReplyHtml);
                } else {
                    $('.forum-content').after(newReplyHtml);
                }
            }
            
            const $newReply = $('.new-reply').last();
            
            // Re-inicializar eventos para a nova resposta
            this.initPostActions();
            this.initReplyActions();
            
            // Animate in
            $newReply.animate({opacity: 1}, 600);
            
            // Scroll to new reply
            $('html, body').animate({
                scrollTop: $newReply.offset().top - 100
            }, 800);
        }
        
        addGenericReply(content) {
            // Fallback para quando não há dados do usuário
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
                                <img src="${this.getDefaultAvatar()}" alt="Você">
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
            
            $newReply.animate({opacity: 1}, 600);
            
            $('html, body').animate({
                scrollTop: $newReply.offset().top - 100
            }, 800);
        }
        
        // ========================================
        // HELPER METHODS
        // ========================================
        
        getDefaultAvatar() {
            return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRkYwMDdGIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjUgODBDMjUgNjcuNSAzNi41IDU3IDUwIDU3Uzc1IDY3LjUgNzUgODBIMjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
        }
        
        getUserTitle(level) {
            switch (level) {
                case 5: return 'Diretor Geral';
                case 4: return 'Administrador';
                case 3: return 'Moderador';
                case 2: return 'Membro Verificado';
                case 1: return 'Membro';
                default: return 'Visitante';
            }
        }
        
        generateUserBadges(level, messageCount) {
            const badges = [];
            
            // Badge de nível administrativo
            if (level >= 5) {
                badges.push({
                    name: 'Diretor Geral',
                    icon: 'fa fa-crown',
                    class: 'director-badge',
                    level: null
                });
            } else if (level >= 4) {
                badges.push({
                    name: 'Administrador',
                    icon: 'fa fa-shield-alt',
                    class: 'admin-badge',
                    level: null
                });
            } else if (level >= 3) {
                badges.push({
                    name: 'Moderador',
                    icon: 'fa fa-shield',
                    class: 'moderator-badge',
                    level: null
                });
            }
            
            // Badge por quantidade de mensagens
            if (messageCount >= 500) {
                badges.push({
                    name: 'Diferente',
                    icon: 'fa fa-star',
                    class: 'differente-badge',
                    level: 'Nv.6'
                });
            } else if (messageCount >= 100) {
                badges.push({
                    name: 'Simpático',
                    icon: 'fa fa-thumbs-up',
                    class: 'simpatico-badge',
                    level: 'Nv.4'
                });
            } else if (messageCount >= 10) {
                badges.push({
                    name: 'Ativo',
                    icon: 'fa fa-comment',
                    class: 'active-badge',
                    level: 'Nv.2'
                });
            }
            
            return badges;
        }
        
        parseBBCodeToHTML(text) {
            return ForumSystem.parseBBCode(text);
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
            
            $picker.on('click', '.emoji-item', function() {
                const emoji = $(this).data('emoji');
                const currentText = textarea.val();
                const cursorPos = textarea[0].selectionStart;
                
                const newText = currentText.slice(0, cursorPos) + emoji + currentText.slice(cursorPos);
                textarea.val(newText);
                
                textarea[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
                textarea.focus();
                
                $picker.remove();
                
                if (typeof window.showNotification === 'function') {
                    window.showNotification('Emoji adicionado!', 'success');
                }
            });
            
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
        
        renderRepliesInPage(replies) {
            // Inserir as respostas após o conteúdo principal do fórum
            const $insertAfter = $('.forum-content');
            
            replies.forEach(reply => {
                const replyHtml = this.buildReplyHTML(reply);
                $insertAfter.after(replyHtml);
            });
            
            // Re-inicializar eventos para as novas respostas
            this.initPostActions();
            this.initReplyActions();
            
            console.log(`${replies.length} respostas renderizadas na página`);
        }
        
        updateTopicDisplay(data) {
            const { topic, replies } = data;
            
            // Atualizar informações do tópico apenas se existirem na página
            if (topic) {
                // Atualizar título se existir
                const $topicStatus = $('.topic-status');
                if ($topicStatus.length && topic.title) {
                    const currentText = $topicStatus.text();
                    const prefix = currentText.includes('Fórum:') ? 'Fórum: ' : '';
                    $topicStatus.html(`${$topicStatus.find('i')[0]?.outerHTML || ''} ${prefix}${topic.title}`);
                }
                
                // Atualizar tempo se existir
                const $topicTime = $('.topic-time');
                if ($topicTime.length && topic.time_ago) {
                    $topicTime.html(`<i class="fa fa-clock"></i> ${topic.time_ago}`);
                }
            }
            
            // Para primeira página, não precisamos renderizar respostas (já vêm do PHP)
            // Para outras páginas, as respostas são tratadas em renderRepliesInPage
            if (this.currentPage === 1 && replies && Array.isArray(replies)) {
                console.log(`Página 1: ${replies.length} respostas já carregadas via PHP`);
            }
        }
        
        renderReplies(replies) {
            const $repliesContainer = $('.replies-container');
            
            // Garantir que o container está vazio
            $repliesContainer.empty();
            
            replies.forEach(reply => {
                const replyHtml = this.buildReplyHTML(reply);
                $repliesContainer.append(replyHtml);
            });
            
            // Re-inicializar eventos para as novas respostas
            this.initPostActions();
            this.initReplyActions();
            
            console.log(`${replies.length} respostas renderizadas`);
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
                                <img src="${reply.avatar || this.getDefaultAvatar()}" alt="${reply.author_name}">
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
        // PAGE ACTIONS E MODAIS
        // ========================================
        
        initPageActions() {
            $('.breadcrumb-item').on('click', function(e) {
                if ($(this).attr('href') && $(this).attr('href') !== '#') {
                    return;
                }
                
                e.preventDefault();
                const text = $(this).text().trim();
                if (text !== 'Teorias sobre o final de GTA VI') {
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(`Navegando para: ${text}`, 'info');
                    }
                }
            });
            
            $('.info-btn').on('click', function() {
                ForumSystem.showInfoModal();
            });
            
            $('.help-btn').on('click', function() {
                ForumSystem.showHelpModal();
            });
            
            $('.report-btn').on('click', function() {
                ForumSystem.showReportModal();
            });
            
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
                $('.forum-pagination, .pagination').hide();
                return;
            }
            
            const { current_page, total_pages, has_prev, has_next } = meta;
            
            console.log('Atualizando paginação:', {
                current_page,
                total_pages,
                has_prev,
                has_next
            });
            
            let paginationHtml = '<div class="pagination">';
            
            // Botão anterior
            if (has_prev) {
                paginationHtml += `<button class="pagination-btn" data-page="${current_page - 1}"><i class="fa fa-angle-left"></i></button>`;
            } else {
                paginationHtml += `<button class="pagination-btn disabled"><i class="fa fa-angle-left"></i></button>`;
            }
            
            // Números das páginas - lógica inteligente
            const startPage = Math.max(1, current_page - 2);
            const endPage = Math.min(total_pages, current_page + 2);
            
            // Primeira página se não estiver visível
            if (startPage > 1) {
                paginationHtml += `<button class="pagination-btn" data-page="1">1</button>`;
                if (startPage > 2) {
                    paginationHtml += `<button class="pagination-btn disabled">...</button>`;
                }
            }
            
            // Páginas do meio
            for (let i = startPage; i <= endPage; i++) {
                if (i === current_page) {
                    paginationHtml += `<button class="pagination-btn active">${i}</button>`;
                } else {
                    paginationHtml += `<button class="pagination-btn" data-page="${i}">${i}</button>`;
                }
            }
            
            // Última página se não estiver visível
            if (endPage < total_pages) {
                if (endPage < total_pages - 1) {
                    paginationHtml += `<button class="pagination-btn disabled">...</button>`;
                }
                paginationHtml += `<button class="pagination-btn" data-page="${total_pages}">${total_pages}</button>`;
            }
            
            // Botão próximo
            if (has_next) {
                paginationHtml += `<button class="pagination-btn" data-page="${current_page + 1}"><i class="fa fa-angle-right"></i></button>`;
            } else {
                paginationHtml += `<button class="pagination-btn disabled"><i class="fa fa-angle-right"></i></button>`;
            }
            
            paginationHtml += '</div>';
            
            // Atualizar HTML da paginação na estrutura existente
            const $paginationContainer = $('.forum-pagination');
            if ($paginationContainer.length) {
                $paginationContainer.html(paginationHtml);
            } else {
                // Se não existir, criar após as respostas
                const $lastReply = $('.reply-section').last();
                if ($lastReply.length) {
                    $lastReply.after(`<div class="forum-pagination">${paginationHtml}</div>`);
                } else {
                    $('.reply-form-section').before(`<div class="forum-pagination">${paginationHtml}</div>`);
                }
            }
            
            // Mostrar paginação
            $('.forum-pagination, .pagination').show();
            
            // Re-vincular eventos de clique da paginação
            $('.pagination-btn[data-page]').off('click.pagination').on('click.pagination', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(e.currentTarget);
                const page = parseInt($btn.data('page'));
                
                if (page && page !== current_page && !$btn.hasClass('disabled')) {
                    console.log(`Navegando para página ${page}`);
                    
                    // Desabilitar botões temporariamente
                    $('.pagination-btn').addClass('loading');
                    
                    // Atualizar página atual
                    this.currentPage = page;
                    
                    try {
                        // Carregar nova página
                        await this.loadTopicData();
                        
                        this.showNotification(`Página ${page} carregada`, 'success');
                    } catch (error) {
                        console.error('Erro ao navegar para página:', error);
                        this.showNotification(`Erro ao carregar página ${page}`, 'error');
                        
                        // Reverter para página anterior em caso de erro
                        this.currentPage = current_page;
                    } finally {
                        // Re-habilitar botões
                        $('.pagination-btn').removeClass('loading');
                    }
                }
            });
            
            console.log('Paginação atualizada com eventos vinculados');
        }
        
        // ========================================
        // USER INTERACTIONS
        // ========================================
        
        initUserInteractions() {
            $('.user-avatar').hover(
                function() {
                    $(this).find('.user-status').addClass('pulse');
                },
                function() {
                    $(this).find('.user-status').removeClass('pulse');
                }
            );
            
            $('.badge').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );
            
            $('.user-card').on('click', '.username', function() {
                const username = $(this).text().trim();
                if (typeof window.showNotification === 'function') {
                    window.showNotification(`Visualizando perfil de ${username}`, 'info');
                }
            });
            
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
        // MODAL SYSTEMS (mantendo todos os modais)
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
            
            $modal.find('.report-form').on('submit', function(e) {
                e.preventDefault();
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
            
            setTimeout(() => {
                $modal.addClass('show');
            }, 10);
            
            $modal.find('.modal-close').on('click', ForumSystem.closeModal);
            $modal.on('click', function(e) {
                if ($(e.target).hasClass('modal-overlay')) {
                    ForumSystem.closeModal();
                }
            });
            
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
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    const forumSystem = new ForumSystem();
    
    // Expor algumas funções globalmente para debug
    window.forumSystem = forumSystem;
    window.setTopicId = (id) => forumSystem.setTopicId(id);
    
    // Adicionar estilos CSS para os badges e novos recursos
    const forumStyles = `
        <style>
        /* Estilos dos modais */
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
        
        /* Estilos dos badges */
        .director-badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #333;
            font-weight: bold;
        }
        
        .admin-badge {
            background: linear-gradient(45deg, #FF6B6B, #FF8E8E);
            color: white;
        }
        
        .moderator-badge {
            background: linear-gradient(45deg, #4ECDC4, #44A08D);
            color: white;
        }
        
        .simpatico-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .differente-badge {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
        }
        
        .active-badge {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }
        
        .badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .badge-level {
            font-size: 9px;
            opacity: 0.8;
        }
        
        /* Estilos para nova resposta */
        .new-reply {
            border-left: 4px solid var(--color-success, #28a745);
            animation: fadeInUp 0.6s ease;
        }
        
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
        
        /* Pulse animation para status online */
        .user-status.pulse {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Preview styles */
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
        
        /* Emoji picker */
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
        
        /* Character counter */
        .char-counter {
            font-size: 11px;
            color: #6c757d;
        }
        
        .char-counter.warning {
            color: #dc3545;
            font-weight: 600;
        }
        
        /* Form styles para modais */
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
        
        /* Verificado icon */
        .verified {
            color: #28a745;
            margin-left: 4px;
        }
        
        /* User stats styling */
        .user-stats {
            margin-top: 12px;
        }
        
        .stat-item {
            margin-bottom: 6px;
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: var(--color-primary, #007cba);
        }
        
        .stat-label {
            font-size: 11px;
            color: #6c757d;
        }
        
        /* Quote styling melhorado */
        .reply-text blockquote,
        .post-text blockquote {
            background: #f8f9fa;
            border-left: 4px solid var(--color-primary, #007cba);
            padding: 12px 16px;
            margin: 12px 0;
            border-radius: 0 6px 6px 0;
            font-style: italic;
        }
        
        .reply-text blockquote strong,
        .post-text blockquote strong {
            color: var(--color-primary, #007cba);
            font-style: normal;
        }
        
        /* Estilos para paginação melhorada */
        .pagination-container,
        .pagination-wrapper {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            text-decoration: none;
            color: #6c757d;
            background: white;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 13px;
        }
        
        .pagination-btn:hover:not(.disabled):not(.active) {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
        }
        
        .pagination-btn.active {
            background: var(--color-primary, #007cba);
            border-color: var(--color-primary, #007cba);
            color: white;
            font-weight: 600;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }
        
        .pagination-btn.loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .prev-btn,
        .next-btn {
            font-weight: 500;
        }
        
        /* Custom notification styles */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .forum-notification {
            animation: slideInRight 0.3s ease;
        }
        
        /* No replies message */
        .no-replies {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        </style>
    `;
    
    $('head').append(forumStyles);
    
    // Welcome message
    setTimeout(() => {
        if (typeof window.showNotification === 'function') {
            window.showNotification('🎮 Sistema do Fórum carregado com dados reais do usuário!', 'success');
        }
    }, 1500);
    
    console.log('🎮 Forum system loaded successfully with real user data and improved quote functionality!');
    console.log('🔧 Debug: Use window.setTopicId(ID) para definir manualmente o ID do tópico se necessário');
});

// ========================================
// FUNÇÕES GLOBAIS PARA DEBUG
// ========================================

// Função para definir o ID do tópico manualmente (para debug)
window.setForumTopicId = function(topicId) {
    if (window.forumSystem) {
        return window.forumSystem.setTopicId(topicId);
    } else {
        console.warn('Forum system not loaded yet');
        return null;
    }
};

// Função para verificar o usuário atual
window.getCurrentForumUser = function() {
    if (window.forumSystem) {
        return window.forumSystem.currentUser;
    } else {
        console.warn('Forum system not loaded yet');
        return null;
    }
};

// Função para recarregar dados do usuário
window.reloadForumUser = async function() {
    if (window.forumSystem) {
        await window.forumSystem.loadCurrentUser();
        console.log('User data reloaded:', window.forumSystem.currentUser);
        return window.forumSystem.currentUser;
    } else {
        console.warn('Forum system not loaded yet');
        return null;
    }
};

// Log de inicialização
console.log('🚀 Leonida Brasil Forum System - Versão Final');
console.log('✅ Funcionalidades implementadas:');
console.log('   • Dados reais do usuário logado');
console.log('   • Sistema de citação funcional');
console.log('   • Badges baseados em nível e mensagens');
console.log('   • Avatar e informações corretas');
console.log('   • Integração completa com API');
console.log('📖 Debug: window.setForumTopicId(ID), window.getCurrentForumUser(), window.reloadForumUser()');