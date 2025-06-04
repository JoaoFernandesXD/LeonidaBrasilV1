/**
 * Leonida Brasil - Forum.js
 * JavaScript específico para funcionalidades do fórum
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // FORUM SYSTEM
    // ========================================
    
    class ForumSystem {
        constructor() {
            this.init();
        }
        
        init() {
            this.initPostActions();
            this.initReplyActions();
            this.initEditor();
            this.initPageActions();
            this.initUserInteractions();
        }
        
        // ========================================
        // POST ACTIONS
        // ========================================
        
        initPostActions() {
            // Like button
            $('.like-btn').on('click', function() {
                const $btn = $(this);
                const isLiked = $btn.hasClass('liked');
                
                $btn.toggleClass('liked');
                
                if (isLiked) {
                    $btn.removeClass('liked').html('<i class="fa fa-thumbs-up"></i>');
                    NotificationSystem.show('Curtida removida', 'info');
                } else {
                    $btn.addClass('liked').html('<i class="fa fa-thumbs-up"></i> Curtido');
                    NotificationSystem.show('Post curtido!', 'success');
                }
            });
            
            // Quote button
            $('.quote-btn').on('click', function() {
                const postText = $(this).closest('.post-content, .reply-content').find('.post-text, .reply-text').text().trim();
                const username = $(this).closest('.forum-content, .reply-section').find('.username').first().text().trim();
                
                const quote = `[quote="${username}"]${postText.substring(0, 200)}${postText.length > 200 ? '...' : ''}[/quote]\n\n`;
                
                const $textarea = $('.editor-textarea');
                const currentText = $textarea.val();
                $textarea.val(quote + currentText).focus();
                
                NotificationSystem.show('Citação adicionada ao editor', 'success');
                
                // Scroll to editor
                $('html, body').animate({
                    scrollTop: $('.reply-form-section').offset().top - 100
                }, 500);
            });
            
            // Check button (mark as resolved)
            $('.check-btn').on('click', function() {
                const $btn = $(this);
                const isResolved = $btn.hasClass('resolved');
                
                if (isResolved) {
                    $btn.removeClass('resolved').html('<i class="fa fa-check"></i>');
                    NotificationSystem.show('Marcação de resolvido removida', 'info');
                } else {
                    $btn.addClass('resolved').html('<i class="fa fa-check"></i> Resolvido');
                    NotificationSystem.show('Tópico marcado como resolvido!', 'success');
                }
            });
        }
        
        // ========================================
        // REPLY ACTIONS
        // ========================================
        
        initReplyActions() {
            // Report buttons
            $('.btn-danger').on('click', function(e) {
                e.preventDefault();
                const postType = $(this).closest('.topic-header').length ? 'tópico' : 'resposta';
                
                if (confirm(`Tem certeza que deseja denunciar este ${postType}?`)) {
                    NotificationSystem.show(`${postType.charAt(0).toUpperCase() + postType.slice(1)} denunciado! Nossa equipe irá analisar.`, 'warning');
                }
            });
            
            // Citation buttons
            $('.btn-warning').on('click', function(e) {
                e.preventDefault();
                ForumSystem.prototype.initPostActions.call(this);
            });
        }
        
        // ========================================
        // EDITOR FUNCTIONALITY
        // ========================================
        
        initEditor() {
            const $textarea = $('.editor-textarea');
            const $toolbar = $('.editor-toolbar');
            
            // BBCode buttons
            $('.editor-btn[data-tag]').on('click', function() {
                const tag = $(this).data('tag');
                const selection = ForumSystem.getSelectedText($textarea[0]);
                
                ForumSystem.insertBBCode($textarea[0], tag, selection);
            });
            
            // Emoji button
            $('.editor-btn[data-tag="emoji"]').on('click', function() {
                ForumSystem.showEmojiPicker($textarea);
            });
            
            // Live preview
            $('.preview-btn').on('click', function() {
                const $btn = $(this);
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
            
            // Auto-save draft
            let saveTimer;
            $textarea.on('input', function() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => {
                    ForumSystem.saveDraft($textarea.val());
                }, 2000);
            });
            
            // Load saved draft
            ForumSystem.loadDraft($textarea);
            
            // Submit form
            $('.reply-form-section form, .editor-footer .btn-primary').on('click', function(e) {
                e.preventDefault();
                ForumSystem.submitReply($textarea);
            });
            
            // Character counter
            $textarea.on('input', function() {
                const length = $(this).val().length;
                const maxLength = 5000;
                const $counter = $('.char-counter');
                
                if (!$counter.length) {
                    $('.editor-footer').prepend(`<div class="char-counter">${length}/${maxLength} caracteres</div>`);
                } else {
                    $counter.text(`${length}/${maxLength} caracteres`);
                    
                    if (length > maxLength * 0.9) {
                        $counter.addClass('warning');
                    } else {
                        $counter.removeClass('warning');
                    }
                }
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
                NotificationSystem.show('Emoji adicionado!', 'success');
            });
            
            // Close picker when clicking outside
            $(document).on('click', function(e) {
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
                .replace(/\[quote\](.*?)\[\/quote\]/g, '<blockquote>$2</blockquote>')
                .replace(/\n/g, '<br>');
            
            return `<div class="preview-content">${html}</div>`;
        }
        
        static saveDraft(content) {
            try {
                localStorage.setItem('leonida_forum_draft', content);
                $('.char-counter').after('<span class="draft-saved">Rascunho salvo</span>');
                setTimeout(() => {
                    $('.draft-saved').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            } catch (e) {
                // localStorage not available
            }
        }
        
        static loadDraft(textarea) {
            try {
                const draft = localStorage.getItem('leonida_forum_draft');
                if (draft && draft.trim()) {
                    textarea.val(draft);
                    NotificationSystem.show('Rascunho carregado', 'info');
                }
            } catch (e) {
                // localStorage not available
            }
        }
        
        static submitReply(textarea) {
            const content = textarea.val().trim();
            
            if (!content) {
                NotificationSystem.show('Digite uma mensagem antes de enviar!', 'warning');
                textarea.focus();
                return;
            }
            
            if (content.length < 10) {
                NotificationSystem.show('A mensagem deve ter pelo menos 10 caracteres!', 'warning');
                textarea.focus();
                return;
            }
            
            // Disable form
            $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', true);
            $('.btn-primary').html('<i class="fa fa-spinner fa-spin"></i> Enviando...');
            
            // Simulate submission
            setTimeout(() => {
                NotificationSystem.show('Resposta enviada com sucesso!', 'success');
                
                // Clear draft
                try {
                    localStorage.removeItem('leonida_forum_draft');
                } catch (e) {}
                
                // Reset form
                textarea.val('');
                $('.editor-textarea, .editor-btn, .btn-primary').prop('disabled', false);
                $('.btn-primary').html('<i class="fa fa-paper-plane"></i> Enviar');
                
                // Add new reply to page (simulation)
                ForumSystem.addNewReply(content);
                
            }, 2000);
        }
        
        static addNewReply(content) {
            const newReply = `
                <div class="reply-section new-reply" style="opacity: 0;">
                    <aside class="reply-user-sidebar">
                        <div class="user-card">
                            <div class="user-header">
                                <div class="username">
                                    Usuário
                                    <i class="fa fa-check-circle verified" title="Usuário Verificado"></i>
                                </div>
                                <div class="user-title">Membro</div>
                            </div>
                            
                            <div class="user-avatar">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRkYwMDdGIi8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjUgODBDMjUgNjcuNSAzNi41IDU3IDUwIDU3Uzc1IDY3LjUgNzUgODBIMjVaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K" alt="Usuário">
                                <div class="user-status online" title="Online"></div>
                            </div>
                            
                            <div class="user-badges">
                                <div class="badge simpatico-badge" title="Novo Membro">
                                    <i class="fa fa-user"></i>
                                    Novo Membro
                                </div>
                            </div>
                            
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-number">1</span>
                                    <span class="stat-label">mensagens</span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="reply-content">
                        <div class="reply-header">
                            <div class="reply-info">
                                <span class="reply-title">Re: Teorias sobre o final de GTA VI</span>
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
                                        agora
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reply-body">
                            <div class="reply-text">
                                <p>${content.replace(/\n/g, '</p><p>')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const $newReply = $(newReply);
            $('.reply-form-section').before($newReply);
            
            // Animate in
            $newReply.animate({opacity: 1}, 600);
            
            // Scroll to new reply
            $('html, body').animate({
                scrollTop: $newReply.offset().top - 100
            }, 800);
            
            // Re-initialize events for new reply
            setTimeout(() => {
                ForumSystem.prototype.initReplyActions();
            }, 100);
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
                    NotificationSystem.show(`Navegando para: ${text}`, 'info');
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
        
        // ========================================
        // MODAL SYSTEMS
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
                NotificationSystem.show('Relatório enviado! Nossa equipe irá analisar.', 'success');
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
                NotificationSystem.show(`Visualizando perfil de ${username}`, 'info');
            });
            
            // Status indicator tooltip
            $('.user-status').each(function() {
                const title = $(this).attr('title');
                if (title) {
                    $(this).tooltip({
                        placement: 'top',
                        container: 'body'
                    });
                }
            });
        }
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    // Initialize forum system
    const forumSystem = new ForumSystem();
    
    // Add forum-specific styles
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
            background: var(--color-primary);
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
            border-left: 4px solid var(--color-primary);
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
            color: var(--color-primary);
            font-size: 14px;
        }
        
        .bbcode-guide {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .bbcode-section h4 {
            margin: 0 0 8px 0;
            color: var(--color-primary);
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
            border-left: 4px solid var(--color-primary);
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
            border-left: 4px solid var(--color-success);
        }
        
        .draft-saved {
            color: var(--color-success);
            font-size: 11px;
            margin-left: 8px;
            opacity: 0;
            animation: fadeInOut 2s ease;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        </style>
    `;
    
    $('head').append(forumStyles);
    
    // Welcome message for forum
    setTimeout(() => {
        NotificationSystem.show('Bem-vindo ao Fórum Leonida Brasil! 💬', 'info');
    }, 1500);
    
    console.log('🎮 Forum system loaded successfully!');
});