// ========================================
// CREATE TOPIC FUNCTIONALITY
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const form = document.getElementById('createTopicForm');
    const categoryCards = document.querySelectorAll('.category-card');
    const selectedCategoryInput = document.getElementById('selectedCategory');
    const titleInput = document.getElementById('topicTitle');
    const titleCounter = document.getElementById('titleCount');
    const titleSuggestions = document.getElementById('titleSuggestions');
    const tagsInput = document.getElementById('tagsInput');
    const tagsContainer = document.getElementById('tagsContainer');
    const tagsCounter = document.getElementById('tagsCount');
    const popularTags = document.querySelectorAll('.popular-tag');
    const contentEditor = document.getElementById('topicContent');
    const wordCount = document.getElementById('wordCount');
    const charCount = document.getElementById('charCount');
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const attachmentsList = document.getElementById('attachmentsList');
    const toolbarButtons = document.querySelectorAll('.toolbar-btn');
    const previewModal = document.getElementById('previewModal');
    const previewBtn = document.querySelector('.preview-btn');
    const closePreview = document.getElementById('closePreview');
    
    let selectedTags = [];
    let attachments = [];
    
    // ========================================
    // CATEGORY SELECTION
    // ========================================
    
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selection from other cards
            categoryCards.forEach(c => c.classList.remove('selected'));
            
            // Select current card
            this.classList.add('selected');
            
            // Update hidden input
            selectedCategoryInput.value = this.dataset.category;
            
            // Add selection animation
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
        
        // Keyboard accessibility
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
        
        card.setAttribute('tabindex', '0');
    });
    
    // ========================================
    // TITLE INPUT
    // ========================================
    
    titleInput.addEventListener('input', function() {
        const length = this.value.length;
        titleCounter.textContent = length;
        
        // Update counter color based on length
        if (length > 90) {
            titleCounter.style.color = 'var(--color-danger)';
        } else if (length > 70) {
            titleCounter.style.color = 'var(--color-warning)';
        } else {
            titleCounter.style.color = 'var(--text-light)';
        }
        
        // Show suggestions for improvement
        showTitleSuggestions(this.value);
    });
    
    function showTitleSuggestions(title) {
        const suggestions = [];
        
        if (title.length < 10) {
            suggestions.push('Título muito curto. Tente ser mais descritivo.');
        }
        
        if (title.toLowerCase().includes('help') || title.toLowerCase().includes('ajuda')) {
            suggestions.push('Considere ser mais específico sobre o que precisa.');
        }
        
        if (title.split(' ').length < 3) {
            suggestions.push('Adicione mais palavras para deixar o título mais claro.');
        }
        
        if (suggestions.length > 0) {
            document.getElementById('suggestionsList').innerHTML = suggestions.map(s => `<div>• ${s}</div>`).join('');
            titleSuggestions.style.display = 'block';
        } else {
            titleSuggestions.style.display = 'none';
        }
    }
    
    // ========================================
    // TAGS SYSTEM
    // ========================================
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            e.preventDefault();
            addTag(this.value.trim());
            this.value = '';
        }
        
        if (e.key === 'Backspace' && !this.value && selectedTags.length > 0) {
            removeTag(selectedTags.length - 1);
        }
    });
    
    popularTags.forEach(tag => {
        tag.addEventListener('click', function() {
            const tagText = this.dataset.tag;
            if (!selectedTags.includes(tagText)) {
                addTag(tagText);
            }
        });
    });
    
    function addTag(tagText) {
        if (selectedTags.length >= 5 || selectedTags.includes(tagText)) {
            return;
        }
        
        selectedTags.push(tagText);
        updateTagsDisplay();
        updateTagsCounter();
    }
    
    function removeTag(index) {
        selectedTags.splice(index, 1);
        updateTagsDisplay();
        updateTagsCounter();
    }
    
    function updateTagsDisplay() {
        // Remove existing tag elements (keep input)
        const existingTags = tagsContainer.querySelectorAll('.tag-item');
        existingTags.forEach(tag => tag.remove());
        
        // Add new tag elements before input
        selectedTags.forEach((tag, index) => {
            const tagElement = document.createElement('div');
            tagElement.className = 'tag-item';
            tagElement.innerHTML = `
                <span>${tag}</span>
                <button type="button" class="tag-remove" onclick="removeTagByIndex(${index})">
                    <i class="fa fa-times"></i>
                </button>
            `;
            tagsContainer.insertBefore(tagElement, tagsInput);
        });
    }
    
    function updateTagsCounter() {
        tagsCounter.textContent = `${selectedTags.length}/5 tags`;
        
        if (selectedTags.length >= 5) {
            tagsInput.disabled = true;
            tagsInput.placeholder = 'Máximo de tags atingido';
        } else {
            tagsInput.disabled = false;
            tagsInput.placeholder = 'Digite uma tag e pressione Enter...';
        }
    }
    
    // Global function for tag removal (called from dynamically created buttons)
    window.removeTagByIndex = function(index) {
        removeTag(index);
    };
    
    // ========================================
    // CONTENT EDITOR
    // ========================================
    
    contentEditor.addEventListener('input', function() {
        const text = this.value;
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        const chars = text.length;
        
        wordCount.textContent = words;
        charCount.textContent = chars;
    });
    
    // Toolbar functionality
    toolbarButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            handleToolbarAction(action);
        });
    });
    
    function handleToolbarAction(action) {
        const textarea = contentEditor;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        
        let replacement = '';
        
        switch (action) {
            case 'bold':
                replacement = `**${selectedText || 'texto em negrito'}**`;
                break;
            case 'italic':
                replacement = `*${selectedText || 'texto em itálico'}*`;
                break;
            case 'underline':
                replacement = `__${selectedText || 'texto sublinhado'}__`;
                break;
            case 'link':
                const url = prompt('Digite a URL:');
                if (url) {
                    replacement = `[${selectedText || 'texto do link'}](${url})`;
                }
                break;
            case 'image':
                const imageUrl = prompt('Digite a URL da imagem:');
                if (imageUrl) {
                    replacement = `![${selectedText || 'descrição da imagem'}](${imageUrl})`;
                }
                break;
            case 'quote':
                replacement = `> ${selectedText || 'citação'}`;
                break;
            case 'code':
                replacement = `\`${selectedText || 'código'}\``;
                break;
            case 'preview':
                showPreview();
                return;
        }
        
        if (replacement) {
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            textarea.focus();
            
            // Update counters
            const event = new Event('input');
            textarea.dispatchEvent(event);
        }
    }
    
    // ========================================
    // FILE UPLOAD
    // ========================================
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });
    
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleFiles(files);
    });
    
    function handleFiles(files) {
        files.forEach(file => {
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                showNotification('Arquivo muito grande. Limite de 10MB por arquivo.', 'error');
                return;
            }
            
            if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
                showNotification('Tipo de arquivo não suportado.', 'error');
                return;
            }
            
            attachments.push(file);
            addAttachmentToList(file);
        });
        
        if (attachments.length > 0) {
            attachmentsList.style.display = 'grid';
        }
    }
    
    function addAttachmentToList(file) {
        const attachmentElement = document.createElement('div');
        attachmentElement.className = 'attachment-item';
        
        const preview = file.type.startsWith('image/') ? 
            `<img src="${URL.createObjectURL(file)}" class="attachment-preview" alt="${file.name}">` :
            `<div class="attachment-preview" style="display: flex; align-items: center; justify-content: center; background: var(--bg-light);"><i class="fa fa-video" style="font-size: 24px; color: var(--text-light);"></i></div>`;
        
        attachmentElement.innerHTML = `
            ${preview}
            <div class="attachment-info">${file.name}</div>
            <button type="button" class="attachment-remove" onclick="removeAttachment('${file.name}')">
                <i class="fa fa-times"></i>
            </button>
        `;
        
        attachmentsList.appendChild(attachmentElement);
    }
    
    window.removeAttachment = function(fileName) {
        attachments = attachments.filter(file => file.name !== fileName);
        
        // Remove from DOM
        const attachmentElements = attachmentsList.querySelectorAll('.attachment-item');
        attachmentElements.forEach(element => {
            if (element.textContent.includes(fileName)) {
                element.remove();
            }
        });
        
        if (attachments.length === 0) {
            attachmentsList.style.display = 'none';
        }
    };
    
    // ========================================
    // PREVIEW MODAL
    // ========================================
    
    previewBtn.addEventListener('click', showPreview);
    
    function showPreview() {
        // Get form data
        const selectedCategory = document.querySelector('.category-card.selected');
        const title = titleInput.value || 'Título do seu tópico aparecerá aqui';
        const content = contentEditor.value || 'O conteúdo do seu tópico aparecerá aqui...';
        
        // Update preview content
        document.getElementById('previewCategory').textContent = selectedCategory ? 
            selectedCategory.querySelector('h4').textContent : 'Selecione uma categoria';
        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewContent').innerHTML = formatContent(content);
        
        // Update tags
        const previewTags = document.getElementById('previewTags');
        if (selectedTags.length > 0) {
            previewTags.innerHTML = selectedTags.map(tag => 
                `<span class="tag">${tag}</span>`).join('');
        } else {
            previewTags.innerHTML = '';
        }
        
        // Show modal
        previewModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function formatContent(content) {
        // Simple markdown-like formatting
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/__(.*?)__/g, '<u>$1</u>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
            .replace(/\n/g, '<br>');
    }
    
    closePreview.addEventListener('click', function() {
        previewModal.classList.remove('show');
        document.body.style.overflow = '';
    });
    
    document.getElementById('editTopic').addEventListener('click', function() {
        previewModal.classList.remove('show');
        document.body.style.overflow = '';
    });
    
    // ========================================
    // FORM SUBMISSION
    // ========================================
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        const submitBtn = document.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Publicando...';
        submitBtn.disabled = true;
        
        // Simulate form submission
        setTimeout(() => {
            showNotification('Tópico criado com sucesso!', 'success');
            
            // Reset form or redirect
            setTimeout(() => {
                window.location.href = 'forum.html';
            }, 2000);
        }, 2000);
    });
    
    function validateForm() {
        const errors = [];
        
        if (!selectedCategoryInput.value) {
            errors.push('Selecione uma categoria');
        }
        
        if (!titleInput.value.trim()) {
            errors.push('Digite um título para o tópico');
        }
        
        if (!contentEditor.value.trim()) {
            errors.push('Digite o conteúdo do tópico');
        }
        
        if (errors.length > 0) {
            showNotification(errors.join('<br>'), 'error');
            return false;
        }
        
        return true;
    }
    
    // ========================================
    // SAVE DRAFT
    // ========================================
    
    document.querySelector('.save-draft-btn').addEventListener('click', function() {
        const draftData = {
            category: selectedCategoryInput.value,
            title: titleInput.value,
            content: contentEditor.value,
            tags: selectedTags,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('topic_draft', JSON.stringify(draftData));
        showNotification('Rascunho salvo!', 'success');
    });
    
    // Load draft on page load
    function loadDraft() {
        const draft = localStorage.getItem('topic_draft');
        if (draft) {
            const draftData = JSON.parse(draft);
            
            // Ask user if they want to load the draft
            if (confirm('Encontramos um rascunho salvo. Deseja carregá-lo?')) {
                titleInput.value = draftData.title || '';
                contentEditor.value = draftData.content || '';
                selectedTags = draftData.tags || [];
                
                // Update displays
                titleInput.dispatchEvent(new Event('input'));
                contentEditor.dispatchEvent(new Event('input'));
                updateTagsDisplay();
                updateTagsCounter();
                
                // Select category
                if (draftData.category) {
                    const categoryCard = document.querySelector(`[data-category="${draftData.category}"]`);
                    if (categoryCard) {
                        categoryCard.click();
                    }
                }
                
                showNotification('Rascunho carregado!', 'info');
            }
        }
    }
    
    // Load draft after a short delay
    setTimeout(loadDraft, 1000);
    
    // ========================================
    // NOTIFICATIONS
    // ========================================
    
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fa ${type === 'success' ? 'fa-check-circle' : 
                               type === 'error' ? 'fa-exclamation-circle' : 
                               type === 'warning' ? 'fa-exclamation-triangle' : 
                               'fa-info-circle'}"></i>
                <div class="notification-message">${message}</div>
                <button class="notification-close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    // ========================================
    // CANCEL BUTTON
    // ========================================
    
    document.querySelector('.cancel-btn').addEventListener('click', function() {
        if (titleInput.value || contentEditor.value || selectedTags.length > 0) {
            if (confirm('Tem certeza que deseja cancelar? Todas as alterações serão perdidas.')) {
                window.location.href = 'forum.html';
            }
        } else {
            window.location.href = 'forum.html';
        }
    });
});
