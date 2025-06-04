// ========================================
// PROFILE SETTINGS FUNCTIONALITY - LEONIDA BRASIL
// ========================================

$(document).ready(function() {
    // Initialize all components
    initializeAvatarUpload();
    initializeFormValidation();
    initializeTagsInput();
    initializePlatformSelection();
    initializePrivacySettings();
    initializeProgressTracking();
    initializeQuickActions();
    initializeAutoSave();
    initializeKeyboardShortcuts();

    // ========================================
    // AVATAR UPLOAD FUNCTIONALITY
    // ========================================
    
    function initializeAvatarUpload() {
        const $avatarInput = $('#avatarInput');
        const $currentAvatar = $('#currentAvatar');
        const $uploadBtn = $('#uploadAvatarBtn');
        const $removeBtn = $('#removeAvatarBtn');
        const $avatarOverlay = $('.current-avatar');

        // Upload button click
        $uploadBtn.click(function() {
            $avatarInput.click();
        });

        // Avatar overlay click
        $avatarOverlay.click(function() {
            $avatarInput.click();
        });

        // File input change
        $avatarInput.change(function() {
            const file = this.files[0];
            if (file) {
                validateAndPreviewAvatar(file);
            }
        });

        // Remove avatar button
        $removeBtn.click(function() {
            if (confirm('Tem certeza que deseja remover sua foto de perfil?')) {
                resetAvatar();
                showNotification('Foto de perfil removida com sucesso!', 'success');
                updateProgress();
            }
        });

        // Drag and drop functionality
        $avatarOverlay.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        $avatarOverlay.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        $avatarOverlay.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                validateAndPreviewAvatar(files[0]);
            }
        });

        function validateAndPreviewAvatar(file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('Formato de arquivo não suportado. Use JPG, PNG ou GIF.', 'error');
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Arquivo muito grande. O tamanho máximo é 5MB.', 'error');
                return;
            }

            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                $currentAvatar.attr('src', e.target.result);
                showNotification('Foto de perfil atualizada! Lembre-se de salvar as alterações.', 'success');
                updateProgress();
            };
            reader.readAsDataURL(file);
        }

        function resetAvatar() {
            $currentAvatar.attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSJ1cmwoI2dyYWRpZW50KSIvPgo8ZGVmcz4KPGxpbmVhckdyYWRpZW50IGlkPSJncmFkaWVudCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNGRjAwN0YiLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjMDBCRkZGIi8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHR4dCB4PSI2MCIgeT0iNjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkF2YXRhcjwvdGV4dD4KPC9zdmc+');
            $avatarInput.val('');
        }
    }

    // ========================================
    // FORM VALIDATION
    // ========================================
    
    function initializeFormValidation() {
        const $form = $('#profileSettingsForm');
        const $username = $('#username');
        const $bio = $('#bio');

        // Username validation
        $username.on('input', function() {
            const username = $(this).val();
            const $counter = $(this).siblings('.field-info').find('.char-counter');
            const $status = $(this).siblings('.field-info').find('.field-status');
            
            $counter.text(`${username.length}/20`);
            
            // Simulate username availability check
            if (username.length >= 3) {
                setTimeout(() => {
                    const isAvailable = Math.random() > 0.3; // 70% chance of being available
                    if (isAvailable) {
                        $status.removeClass('unavailable').addClass('available')
                               .html('<i class="fa fa-check"></i> Disponível');
                    } else {
                        $status.removeClass('available').addClass('unavailable')
                               .html('<i class="fa fa-times"></i> Indisponível');
                    }
                }, 500);
            } else {
                $status.removeClass('available unavailable').text('');
            }
            
            updateProgress();
        });

        // Bio character counter
        $bio.on('input', function() {
            const bio = $(this).val();
            const $counter = $(this).siblings('.field-info').find('.char-counter');
            $counter.text(`${bio.length}/500`);
            updateProgress();
        });

        // Form submission
        $form.submit(function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                saveProfile();
            }
        });

        function validateForm() {
            let isValid = true;
            const requiredFields = ['username'];
            
            requiredFields.forEach(field => {
                const $field = $(`#${field}`);
                const value = $field.val().trim();
                
                if (!value) {
                    showFieldError($field, 'Este campo é obrigatório');
                    isValid = false;
                } else {
                    clearFieldError($field);
                }
            });

            return isValid;
        }

        function showFieldError($field, message) {
            $field.addClass('error');
            let $error = $field.siblings('.field-error');
            if ($error.length === 0) {
                $error = $('<div class="field-error"></div>');
                $field.after($error);
            }
            $error.text(message);
        }

        function clearFieldError($field) {
            $field.removeClass('error');
            $field.siblings('.field-error').remove();
        }
    }

    // ========================================
    // TAGS INPUT FUNCTIONALITY
    // ========================================
    
    function initializeTagsInput() {
        const $tagsInput = $('#favoriteGamesInput');
        const $tagsList = $('#favoriteGamesList');
        const $tagsContainer = $('.tags-input-container');
        let tags = [];
        const maxTags = 10;

        $tagsInput.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTag();
            } else if (e.key === 'Backspace' && $(this).val() === '' && tags.length > 0) {
                removeTag(tags.length - 1);
            }
        });

        $tagsInput.on('blur', function() {
            if ($(this).val().trim()) {
                addTag();
            }
        });

        function addTag() {
            const tagText = $tagsInput.val().trim();
            
            if (!tagText) return;
            
            if (tags.length >= maxTags) {
                showNotification(`Você só pode adicionar até ${maxTags} jogos favoritos.`, 'warning');
                return;
            }
            
            if (tags.includes(tagText.toLowerCase())) {
                showNotification('Este jogo já está na sua lista.', 'warning');
                $tagsInput.val('');
                return;
            }

            tags.push(tagText.toLowerCase());
            renderTags();
            $tagsInput.val('');
            updateProgress();
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            updateProgress();
        }

        function renderTags() {
            $tagsList.empty();
            
            tags.forEach((tag, index) => {
                const $tag = $(`
                    <div class="tag-item" data-index="${index}">
                        <span>${tag}</span>
                        <button type="button" class="tag-remove" title="Remover">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `);
                
                $tag.find('.tag-remove').click(function() {
                    removeTag(index);
                });
                
                $tagsList.append($tag);
            });
        }

        // Pre-populate with some games for demo
        const demoGames = ['Red Dead Redemption 2', 'Cyberpunk 2077'];
        tags = demoGames.map(game => game.toLowerCase());
        renderTags();
    }

    // ========================================
    // PLATFORM SELECTION
    // ========================================
    
    function initializePlatformSelection() {
        const $platformCheckboxes = $('.platform-checkbox input');
        
        $platformCheckboxes.change(function() {
            updateProgress();
        });

        // Pre-select PC for demo
        $('input[value="pc"]').prop('checked', true);
    }

    // ========================================
    // PRIVACY SETTINGS
    // ========================================
    
    function initializePrivacySettings() {
        const $privacyOptions = $('input[name="profile_visibility"]');
        const $notificationToggles = $('input[name^="notifications"]');
        const $contentToggles = $('input[name^="content"]');

        $privacyOptions.change(function() {
            const selectedValue = $(this).val();
            showNotification(`Visibilidade do perfil alterada para: ${getVisibilityLabel(selectedValue)}`, 'info');
        });

        $notificationToggles.change(function() {
            const isEnabled = $(this).is(':checked');
            const settingName = $(this).closest('.toggle-label').find('.toggle-title').text();
            const status = isEnabled ? 'ativadas' : 'desativadas';
            showNotification(`Notificações de ${settingName.toLowerCase()} ${status}`, 'info');
        });

        $contentToggles.change(function() {
            const isEnabled = $(this).is(':checked');
            const settingName = $(this).closest('.toggle-label').find('.toggle-title').text();
            const status = isEnabled ? 'habilitado' : 'desabilitado';
            showNotification(`${settingName} ${status}`, 'info');
        });

        function getVisibilityLabel(value) {
            const labels = {
                'public': 'Público',
                'members': 'Apenas membros',
                'private': 'Privado'
            };
            return labels[value] || value;
        }
    }

    // ========================================
    // PROGRESS TRACKING
    // ========================================
    
    function initializeProgressTracking() {
        updateProgress();
        
        // Update progress when form fields change
        $('input, textarea, select').on('input change', function() {
            setTimeout(updateProgress, 100); // Small delay to ensure DOM is updated
        });
    }

    function updateProgress() {
        const sections = {
            avatar: calculateAvatarProgress(),
            basic: calculateBasicInfoProgress(),
            social: calculateSocialProgress(),
            gaming: calculateGamingProgress(),
            privacy: calculatePrivacyProgress()
        };

        const totalProgress = Object.values(sections).reduce((sum, progress) => sum + progress, 0) / Object.keys(sections).length;
        
        // Update main progress bar
        $('.progress-fill').css('width', `${totalProgress}%`);
        $('#progressPercentage').text(Math.round(totalProgress));

        // Update sidebar progress items
        updateSidebarProgress(sections);
        
        // Update section statuses
        updateSectionStatuses(sections);
    }

    function calculateAvatarProgress() {
        const $avatar = $('#currentAvatar');
        const isDefaultAvatar = $avatar.attr('src').includes('data:image/svg+xml');
        return isDefaultAvatar ? 0 : 100;
    }

    function calculateBasicInfoProgress() {
        const fields = ['username', 'displayName', 'bio', 'website', 'location'];
        const filledFields = fields.filter(field => {
            const value = $(`#${field}`).val().trim();
            return value !== '';
        });
        return (filledFields.length / fields.length) * 100;
    }

    function calculateSocialProgress() {
        const socialFields = ['discord', 'twitter', 'instagram', 'youtube', 'twitch', 'steam'];
        const filledFields = socialFields.filter(field => {
            const value = $(`#${field}`).val().trim();
            return value !== '';
        });
        return filledFields.length > 0 ? 100 : 0; // Optional section
    }

    function calculateGamingProgress() {
        let progress = 0;
        let totalWeight = 4;
        let currentWeight = 0;

        // Check platforms
        if ($('.platform-checkbox input:checked').length > 0) {
            currentWeight += 1;
        }

        // Check favorite GTA
        if ($('#favoriteGta').val()) {
            currentWeight += 1;
        }

        // Check gaming time
        if ($('#gamingTime').val()) {
            currentWeight += 1;
        }

        // Check favorite games tags
        if ($('#favoriteGamesList .tag-item').length > 0) {
            currentWeight += 1;
        }

        return (currentWeight / totalWeight) * 100;
    }

    function calculatePrivacyProgress() {
        // Privacy settings are pre-configured, so always 100%
        return 100;
    }

    function updateSidebarProgress(sections) {
        Object.keys(sections).forEach(section => {
            const $item = $(`.progress-item[data-section="${section}"]`);
            const progress = sections[section];
            
            $item.removeClass('completed incomplete optional important');
            
            if (section === 'privacy') {
                $item.addClass('important');
            } else if (section === 'social') {
                $item.addClass('optional');
            } else if (progress === 100) {
                $item.addClass('completed');
            } else {
                $item.addClass('incomplete');
            }

            // Update status text
            const $status = $item.find('.progress-status');
            if (section === 'basic') {
                const filledFields = Math.round((progress / 100) * 5);
                $status.text(`${filledFields}/5 campos`);
            } else if (section === 'gaming') {
                const filledFields = Math.round((progress / 100) * 4);
                $status.text(`${filledFields}/4 campos`);
            } else if (progress === 100) {
                $status.text('Concluído');
            } else if (section === 'social') {
                $status.text('Opcional');
            } else {
                $status.text('Incompleto');
            }
        });
    }

    function updateSectionStatuses(sections) {
        // Update section header statuses
        $('.avatar-section .section-status').removeClass('incomplete complete')
            .addClass(sections.avatar === 100 ? 'complete' : 'incomplete')
            .html(sections.avatar === 100 ? '<i class="fa fa-check-circle"></i> Completo' : '<i class="fa fa-exclamation-circle"></i> Incompleto');
    }

    // ========================================
    // QUICK ACTIONS
    // ========================================
    
    function initializeQuickActions() {
        $('#importFromSocial').click(function() {
            simulateImportFromSteam();
        });

        $('#generateBio').click(function() {
            generateRandomBio();
        });

        $('#findFriends').click(function() {
            showNotification('Funcionalidade de busca de amigos será implementada em breve!', 'info');
        });

        $('#backupSettings').click(function() {
            backupSettings();
        });

        function simulateImportFromSteam() {
            showNotification('Importando dados do Steam...', 'info');
            
            setTimeout(() => {
                // Simulate importing favorite games
                const steamGames = ['Counter-Strike 2', 'Dota 2', 'Half-Life: Alyx'];
                const $tagsList = $('#favoriteGamesList');
                
                steamGames.forEach(game => {
                    const $tag = $(`
                        <div class="tag-item">
                            <span>${game}</span>
                            <button type="button" class="tag-remove" title="Remover">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    `);
                    $tagsList.append($tag);
                });

                // Check PC platform
                $('input[value="pc"]').prop('checked', true);
                
                updateProgress();
                showNotification('Dados importados do Steam com sucesso!', 'success');
            }, 2000);
        }

        function generateRandomBio() {
            const bios = [
                "Gamer apaixonado por mundo aberto e aventuras épicas. Aguardando ansiosamente pelo lançamento de GTA VI!",
                "Veterano da série GTA desde Vice City. Especialista em teorias e easter eggs. Vamos descobrir os segredos de Leonida juntos!",
                "Criador de conteúdo focado em gaming. Amo explorar cada detalhe dos jogos da Rockstar. GTA VI não pode chegar logo!",
                "Fã incondicional da franquia GTA. Sempre em busca das últimas novidades e discussões sobre o futuro de Vice City."
            ];
            
            const randomBio = bios[Math.floor(Math.random() * bios.length)];
            $('#bio').val(randomBio);
            
            // Update character counter
            $('#bio').trigger('input');
            
            showNotification('Biografia gerada automaticamente!', 'success');
        }

        function backupSettings() {
            const settings = {
                username: $('#username').val(),
                displayName: $('#displayName').val(),
                bio: $('#bio').val(),
                website: $('#website').val(),
                location: $('#location').val(),
                social: {
                    discord: $('#discord').val(),
                    twitter: $('#twitter').val(),
                    instagram: $('#instagram').val(),
                    youtube: $('#youtube').val(),
                    twitch: $('#twitch').val(),
                    steam: $('#steam').val()
                },
                gaming: {
                    platforms: $('.platform-checkbox input:checked').map(function() {
                        return $(this).val();
                    }).get(),
                    favoriteGta: $('#favoriteGta').val(),
                    gamingTime: $('#gamingTime').val(),
                    favoriteGames: $('#favoriteGamesList .tag-item span').map(function() {
                        return $(this).text();
                    }).get()
                },
                privacy: {
                    visibility: $('input[name="profile_visibility"]:checked').val(),
                    notifications: {
                        comments: $('input[name="notifications[comments]"]').is(':checked'),
                        mentions: $('input[name="notifications[mentions]"]').is(':checked'),
                        messages: $('input[name="notifications[messages]"]').is(':checked'),
                        newsletters: $('input[name="notifications[newsletters]"]').is(':checked')
                    },
                    content: {
                        spoilers: $('input[name="content[spoilers]"]').is(':checked'),
                        mature: $('input[name="content[mature]"]').is(':checked')
                    }
                }
            };

            const dataStr = JSON.stringify(settings, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = 'leonida-brasil-configuracoes.json';
            link.click();
            
            URL.revokeObjectURL(url);
            showNotification('Backup das configurações baixado com sucesso!', 'success');
        }
    }

    // ========================================
    // AUTO SAVE FUNCTIONALITY
    // ========================================
    
    function initializeAutoSave() {
        let autoSaveTimer;
        let hasUnsavedChanges = false;

        // Track changes in form
        $('input, textarea, select').on('input change', function() {
            hasUnsavedChanges = true;
            
            // Clear existing timer
            clearTimeout(autoSaveTimer);
            
            // Set new timer for auto-save
            autoSaveTimer = setTimeout(() => {
                if (hasUnsavedChanges) {
                    autoSaveData();
                }
            }, 30000); // Auto-save after 30 seconds of inactivity
        });

        // Save as draft button
        $('#saveAsDraft').click(function() {
            saveAsDraft();
        });

        // Warning on page unload
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return 'Você tem alterações não salvas. Deseja realmente sair da página?';
            }
        });

        function autoSaveData() {
            const formData = collectFormData();
            localStorage.setItem('leonida_profile_draft', JSON.stringify(formData));
            hasUnsavedChanges = false;
            
            // Show subtle notification
            showAutoSaveNotification();
        }

        function saveAsDraft() {
            const formData = collectFormData();
            localStorage.setItem('leonida_profile_draft', JSON.stringify(formData));
            hasUnsavedChanges = false;
            
            showNotification('Rascunho salvo com sucesso!', 'success');
        }

        function showAutoSaveNotification() {
            const $notification = $(`
                <div class="auto-save-notification">
                    <i class="fa fa-cloud"></i>
                    Rascunho salvo automaticamente
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 2000);
        }

        // Load draft on page load
        loadDraft();

        function loadDraft() {
            const draft = localStorage.getItem('leonida_profile_draft');
            if (draft) {
                try {
                    const data = JSON.parse(draft);
                    
                    if (confirm('Foi encontrado um rascunho salvo. Deseja carregar as alterações?')) {
                        populateFormWithData(data);
                        showNotification('Rascunho carregado com sucesso!', 'info');
                        updateProgress();
                    }
                } catch (e) {
                    console.error('Erro ao carregar rascunho:', e);
                }
            }
        }
    }

    // ========================================
    // KEYBOARD SHORTCUTS
    // ========================================
    
    function initializeKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveProfile();
            }
            
            // Ctrl/Cmd + D to save draft
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                $('#saveAsDraft').click();
            }
            
            // Escape to cancel/close modals
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    // ========================================
    // BUTTON ACTIONS
    // ========================================
    
    // Preview Profile button
    $('#previewProfile').click(function() {
        const formData = collectFormData();
        openProfilePreview(formData);
    });

    // Reset Settings button
    $('#resetSettings').click(function() {
        if (confirm('Tem certeza que deseja restaurar todas as configurações para os valores padrão? Esta ação não pode ser desfeita.')) {
            resetAllSettings();
        }
    });

    // Cancel Changes button
    $('#cancelChanges').click(function() {
        if (confirm('Tem certeza que deseja cancelar as alterações? Todas as mudanças não salvas serão perdidas.')) {
            location.reload();
        }
    });

    // Save Profile button (form submission)
    $('#saveProfile').click(function() {
        $('#profileSettingsForm').submit();
    });

    // Progress item clicks (scroll to section)
    $('.progress-item').click(function() {
        const section = $(this).data('section');
        scrollToSection(section);
    });

    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    function collectFormData() {
        return {
            username: $('#username').val(),
            displayName: $('#displayName').val(),
            bio: $('#bio').val(),
            website: $('#website').val(),
            location: $('#location').val(),
            social: {
                discord: $('#discord').val(),
                twitter: $('#twitter').val(),
                instagram: $('#instagram').val(),
                youtube: $('#youtube').val(),
                twitch: $('#twitch').val(),
                steam: $('#steam').val()
            },
            gaming: {
                platforms: $('.platform-checkbox input:checked').map(function() {
                    return $(this).val();
                }).get(),
                favoriteGta: $('#favoriteGta').val(),
                gamingTime: $('#gamingTime').val(),
                favoriteGames: $('#favoriteGamesList .tag-item span').map(function() {
                    return $(this).text();
                }).get()
            },
            privacy: {
                visibility: $('input[name="profile_visibility"]:checked').val(),
                notifications: {
                    comments: $('input[name="notifications[comments]"]').is(':checked'),
                    mentions: $('input[name="notifications[mentions]"]').is(':checked'),
                    messages: $('input[name="notifications[messages]"]').is(':checked'),
                    newsletters: $('input[name="notifications[newsletters]"]').is(':checked')
                },
                content: {
                    spoilers: $('input[name="content[spoilers]"]').is(':checked'),
                    mature: $('input[name="content[mature]"]').is(':checked')
                }
            }
        };
    }

    function populateFormWithData(data) {
        // Basic info
        $('#username').val(data.username || '');
        $('#displayName').val(data.displayName || '');
        $('#bio').val(data.bio || '');
        $('#website').val(data.website || '');
        $('#location').val(data.location || '');

        // Social media
        if (data.social) {
            Object.keys(data.social).forEach(platform => {
                $(`#${platform}`).val(data.social[platform] || '');
            });
        }

        // Gaming preferences
        if (data.gaming) {
            // Platforms
            $('.platform-checkbox input').prop('checked', false);
            if (data.gaming.platforms) {
                data.gaming.platforms.forEach(platform => {
                    $(`.platform-checkbox input[value="${platform}"]`).prop('checked', true);
                });
            }

            // Selects
            $('#favoriteGta').val(data.gaming.favoriteGta || '');
            $('#gamingTime').val(data.gaming.gamingTime || '');

            // Favorite games tags
            if (data.gaming.favoriteGames) {
                const $tagsList = $('#favoriteGamesList');
                $tagsList.empty();
                
                data.gaming.favoriteGames.forEach((game, index) => {
                    const $tag = $(`
                        <div class="tag-item" data-index="${index}">
                            <span>${game}</span>
                            <button type="button" class="tag-remove" title="Remover">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    `);
                    
                    $tag.find('.tag-remove').click(function() {
                        $(this).closest('.tag-item').remove();
                        updateProgress();
                    });
                    
                    $tagsList.append($tag);
                });
            }
        }

        // Privacy settings
        if (data.privacy) {
            // Visibility
            if (data.privacy.visibility) {
                $(`input[name="profile_visibility"][value="${data.privacy.visibility}"]`).prop('checked', true);
            }

            // Notifications
            if (data.privacy.notifications) {
                Object.keys(data.privacy.notifications).forEach(key => {
                    $(`input[name="notifications[${key}]"]`).prop('checked', data.privacy.notifications[key]);
                });
            }

            // Content preferences
            if (data.privacy.content) {
                Object.keys(data.privacy.content).forEach(key => {
                    $(`input[name="content[${key}]"]`).prop('checked', data.privacy.content[key]);
                });
            }
        }

        // Trigger input events to update counters
        $('#username, #bio').trigger('input');
    }

    function saveProfile() {
        const $saveBtn = $('#saveProfile');
        const originalText = $saveBtn.html();
        
        // Show loading state
        $saveBtn.html('<i class="fa fa-spinner fa-spin"></i> Salvando...').prop('disabled', true);
        
        // Simulate API call
        setTimeout(() => {
            const formData = collectFormData();
            
            // Simulate successful save
            localStorage.removeItem('leonida_profile_draft'); // Clear draft
            
            $saveBtn.html('<i class="fa fa-check"></i> Salvo!').removeClass('btn-primary').addClass('btn-success');
            
            showNotification('Perfil atualizado com sucesso!', 'success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                $saveBtn.html(originalText).removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
            }, 2000);
            
            // Update page URL to remove unsaved changes warning
            $(window).off('beforeunload');
            
        }, 2000);
    }

    function resetAllSettings() {
        // Clear all form fields
        $('#profileSettingsForm')[0].reset();
        
        // Clear avatar
        $('#currentAvatar').attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSJ1cmwoI2dyYWRpZW50KSIvPgo8ZGVmcz4KPGxpbmVhckdyYWRpZW50IGlkPSJncmFkaWVudCIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+CjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNGRjAwN0YiLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjMDBCRkZGIi8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHR4dCB4PSI2MCIgeT0iNjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkF2YXRhcjwvdGV4dD4KPC9zdmc+');
        
        // Clear tags
        $('#favoriteGamesList').empty();
        
        // Reset to default values
        $('input[name="profile_visibility"][value="public"]').prop('checked', true);
        $('input[name="notifications[comments]"]').prop('checked', true);
        $('input[name="notifications[mentions]"]').prop('checked', true);
        $('input[name="notifications[messages]"]').prop('checked', true);
        
        // Clear local storage
        localStorage.removeItem('leonida_profile_draft');
        
        // Update progress
        updateProgress();
        
        showNotification('Configurações restauradas para os valores padrão!', 'success');
    }

    function openProfilePreview(data) {
        // Create preview modal
        const $modal = $(`
            <div class="preview-modal" id="profilePreviewModal">
                <div class="modal-overlay"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>
                            <i class="fa fa-eye"></i>
                            Preview do Perfil
                        </h3>
                        <button class="modal-close">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-content">
                        <div class="preview-profile">
                            <div class="preview-header">
                                <div class="preview-avatar">
                                    <img src="${$('#currentAvatar').attr('src')}" alt="Avatar">
                                </div>
                                <div class="preview-info">
                                    <h2>${data.username || 'Username'}</h2>
                                    ${data.displayName ? `<div class="preview-display-name">${data.displayName}</div>` : ''}
                                    ${data.bio ? `<div class="preview-bio">${data.bio}</div>` : ''}
                                    ${data.location ? `<div class="preview-location"><i class="fa fa-map-marker-alt"></i> ${data.location}</div>` : ''}
                                    ${data.website ? `<div class="preview-website"><i class="fa fa-link"></i> <a href="${data.website}" target="_blank">${data.website}</a></div>` : ''}
                                </div>
                            </div>
                            <div class="preview-details">
                                ${generateSocialPreview(data.social)}
                                ${generateGamingPreview(data.gaming)}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline close-preview">Fechar</button>
                        <button class="btn btn-primary">Salvar Perfil</button>
                    </div>
                </div>
            </div>
        `);

        $('body').append($modal);
        
        setTimeout(() => {
            $modal.addClass('show');
        }, 10);

        // Close modal events
        $modal.find('.modal-close, .modal-overlay, .close-preview').click(function() {
            $modal.removeClass('show');
            setTimeout(() => $modal.remove(), 300);
        });
    }

    function generateSocialPreview(social) {
        if (!social || Object.values(social).every(val => !val)) {
            return '';
        }

        let html = '<div class="preview-section"><h4>Redes Sociais</h4><div class="social-links">';
        
        Object.keys(social).forEach(platform => {
            if (social[platform]) {
                html += `<a href="#" class="social-link ${platform}"><i class="fab fa-${platform}"></i></a>`;
            }
        });
        
        html += '</div></div>';
        return html;
    }

    function generateGamingPreview(gaming) {
        if (!gaming) return '';

        let html = '<div class="preview-section"><h4>Gaming</h4>';
        
        if (gaming.platforms && gaming.platforms.length > 0) {
            html += '<div class="platforms">Plataformas: ' + gaming.platforms.join(', ') + '</div>';
        }
        
        if (gaming.favoriteGta) {
            html += '<div class="favorite-gta">GTA Favorito: ' + gaming.favoriteGta + '</div>';
        }
        
        if (gaming.favoriteGames && gaming.favoriteGames.length > 0) {
            html += '<div class="favorite-games">Jogos favoritos: ' + gaming.favoriteGames.join(', ') + '</div>';
        }
        
        html += '</div>';
        return html;
    }

    function scrollToSection(section) {
        const sectionMap = {
            'avatar': '.avatar-section',
            'basic': '.avatar-section',
            'social': '.social-section',
            'gaming': '.gaming-section',
            'privacy': '.privacy-section'
        };

        const $target = $(sectionMap[section]);
        if ($target.length) {
            $('html, body').animate({
                scrollTop: $target.offset().top - 100
            }, 500);

            // Highlight section
            $target.addClass('highlight');
            setTimeout(() => $target.removeClass('highlight'), 2000);
        }
    }

    function closeAllModals() {
        $('.preview-modal.show').removeClass('show');
        setTimeout(() => $('.preview-modal').remove(), 300);
    }

    // ========================================
    // NOTIFICATION SYSTEM
    // ========================================
    
    function showNotification(message, type = 'info') {
        const $notification = $(`
            <div class="notification ${type}">
                <div class="notification-content">
                    <i class="fa ${getNotificationIcon(type)}"></i>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        `);

        $('#notification-container').append($notification);

        setTimeout(() => {
            $notification.addClass('show');
        }, 100);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        }, 5000);

        // Close button
        $notification.find('.notification-close').click(function() {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        });
    }

    function getNotificationIcon(type) {
        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    // ========================================
    // INITIALIZATION COMPLETE
    // ========================================
    
    // Create notification container if it doesn't exist
    if ($('#notification-container').length === 0) {
        $('body').append('<div id="notification-container"></div>');
    }

    // Add auto-save notification styles
    const autoSaveStyles = `
        <style>
        .auto-save-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 9999;
        }
        
        .auto-save-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .highlight {
            animation: highlightSection 2s ease;
        }
        
        @keyframes highlightSection {
            0% { background-color: transparent; }
            50% { background-color: rgba(255, 0, 127, 0.1); }
            100% { background-color: transparent; }
        }
        
        .field-error {
            color: var(--color-danger);
            font-size: 12px;
            margin-top: 4px;
        }
        
        .form-group input.error,
        .form-group textarea.error,
        .form-group select.error {
            border-color: var(--color-danger);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .current-avatar.drag-over {
            transform: scale(1.05);
            box-shadow: 0 0 0 3px rgba(255, 0, 127, 0.3);
        }
        </style>
    `;
    
    $('head').append(autoSaveStyles);

    // Show welcome message
    setTimeout(() => {
        showNotification('Bem-vindo às configurações do seu perfil! Complete todas as seções para maximizar sua experiência na comunidade.', 'info');
    }, 1000);

    console.log('🎮 Leonida Brasil - Profile Settings initialized successfully!');
});