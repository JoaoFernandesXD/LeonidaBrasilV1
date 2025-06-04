/* ========================================
   MISSION PAGE JAVASCRIPT - LEONIDA BRASIL
   ======================================== */

$(document).ready(function() {
    'use strict';

    // ========================================
    // VARIABLES AND CONSTANTS
    // ========================================
    
    const ANIMATION_SPEED = 300;
    const SCROLL_OFFSET = 80;
    const LIGHTBOX_FADE_SPEED = 200;
    
    let currentStrategy = 'stealth';
    let currentRelatedTab = 'missions';
    let favorited = false;
    let currentGalleryIndex = 0;
    
    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    function smoothScrollTo(target, offset = SCROLL_OFFSET) {
        const targetElement = $(target);
        if (targetElement.length) {
            $('html, body').animate({
                scrollTop: targetElement.offset().top - offset
            }, 600, 'swing');
        }
    }
    
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = $(`
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
        
        if (!$('#notification-container').length) {
            $('body').append('<div id="notification-container"></div>');
        }
        
        $('#notification-container').append(notification);
        
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);
        
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
    
    function updatePageTitle(suffix = '') {
        const baseTitle = 'Assalto ao Banco Central - Leonida Brasil';
        document.title = suffix ? `${baseTitle} - ${suffix}` : baseTitle;
    }
    
    // ========================================
    // PAGE ACTIONS
    // ========================================
    
    // Favorite Button
    $('.favorite-btn').on('click', function() {
        const $this = $(this);
        const $icon = $this.find('i');
        const $text = $this.find('span').length ? $this.find('span') : $this.contents().filter(function() {
            return this.nodeType === 3;
        });
        
        favorited = !favorited;
        
        if (favorited) {
            $this.addClass('favorited');
            $icon.removeClass('fa-heart').addClass('fa-heart');
            $this.css('background', 'var(--color-danger)');
            showNotification('Missão adicionada aos favoritos!', 'success');
        } else {
            $this.removeClass('favorited');
            $this.css('background', '');
            showNotification('Missão removida dos favoritos.', 'info');
        }
        
        // Add pulse animation
        $this.addClass('pulse');
        setTimeout(() => {
            $this.removeClass('pulse');
        }, 300);
    });
    
    // Share Button
    $('.share-btn').on('click', function() {
        if (navigator.share) {
            navigator.share({
                title: 'Assalto ao Banco Central - GTA VI',
                text: 'Confira esta missão épica de GTA VI no Leonida Brasil!',
                url: window.location.href
            });
        } else {
            // Fallback - copy to clipboard
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showNotification('Link copiado para a área de transferência!', 'success');
            }).catch(() => {
                showNotification('Não foi possível copiar o link.', 'error');
            });
        }
    });
    
    // Edit Button
    $('.edit-btn, .edit-section').on('click', function() {
        showEditModal();
    });
    
    // ========================================
    // SIDEBAR NAVIGATION
    // ========================================
    
    function initSidebarNavigation() {
        const $navLinks = $('.mission-nav-list .nav-link');
        const $sections = $('.mission-section');
        
        // Click navigation
        $navLinks.on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            const targetSection = $(target);
            
            if (targetSection.length) {
                // Update active nav link
                $navLinks.removeClass('active');
                $(this).addClass('active');
                
                // Smooth scroll to section
                smoothScrollTo(target);
                
                // Update URL hash
                history.pushState(null, null, target);
                
                // Update page title
                const sectionTitle = $(this).text().trim();
                updatePageTitle(sectionTitle);
            }
        });
        
        // Scroll spy
        $(window).on('scroll', debounce(function() {
            const scrollPos = $(window).scrollTop() + SCROLL_OFFSET + 50;
            
            $sections.each(function() {
                const $section = $(this);
                const sectionTop = $section.offset().top;
                const sectionBottom = sectionTop + $section.outerHeight();
                const sectionId = $section.attr('id');
                
                if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                    $navLinks.removeClass('active');
                    $(`.nav-link[href="#${sectionId}"]`).addClass('active');
                }
            });
        }, 100));
        
        // Handle initial hash
        if (window.location.hash) {
            const target = window.location.hash;
            const $targetSection = $(target);
            if ($targetSection.length) {
                setTimeout(() => {
                    smoothScrollTo(target);
                    $navLinks.removeClass('active');
                    $(`.nav-link[href="${target}"]`).addClass('active');
                }, 500);
            }
        }
    }
    
    // ========================================
    // STRATEGY TABS
    // ========================================
    
    function initStrategyTabs() {
        $('.strategy-tab').on('click', function() {
            const strategy = $(this).data('strategy');
            
            if (strategy !== currentStrategy) {
                // Update tabs
                $('.strategy-tab').removeClass('active');
                $(this).addClass('active');
                
                // Update panels
                $('.strategy-panel').removeClass('active');
                $(`.strategy-panel[data-panel="${strategy}"]`).addClass('active');
                
                currentStrategy = strategy;
                
                // Analytics tracking
                trackStrategyView(strategy);
                
                // Show notification
                const strategyNames = {
                    stealth: 'Stealth',
                    aggressive: 'Agressiva',
                    smart: 'Inteligente'
                };
                showNotification(`Estratégia ${strategyNames[strategy]} selecionada`, 'info', 2000);
            }
        });
    }
    
    function trackStrategyView(strategy) {
        // Simulate analytics tracking
        console.log(`Strategy viewed: ${strategy}`);
        
        // Update strategy popularity (simulation)
        updateStrategyPopularity(strategy);
    }
    
    function updateStrategyPopularity(strategy) {
        // Simulate updating strategy popularity stats
        const popularityData = {
            stealth: '25%',
            aggressive: '45%',
            smart: '30%'
        };
        
        // Could update UI elements here if needed
        console.log(`Strategy popularity: ${strategy} - ${popularityData[strategy]}`);
    }
    
    // ========================================
    // RELATED CONTENT TABS
    // ========================================
    
    function initRelatedTabs() {
        $('.related-tab').on('click', function() {
            const tab = $(this).data('tab');
            
            if (tab !== currentRelatedTab) {
                // Update tabs
                $('.related-tab').removeClass('active');
                $(this).addClass('active');
                
                // Update panels
                $('.related-panel').removeClass('active');
                $(`.related-panel[data-panel="${tab}"]`).addClass('active');
                
                currentRelatedTab = tab;
                
                // Show loading animation briefly
                const $panel = $(`.related-panel[data-panel="${tab}"]`);
                $panel.addClass('loading');
                setTimeout(() => {
                    $panel.removeClass('loading');
                }, 500);
            }
        });
    }
    
    // ========================================
    // GALLERY FUNCTIONALITY
    // ========================================
    
    function initGallery() {
        const $galleryItems = $('.gallery-item');
        const galleryImages = [];
        
        // Collect gallery images
        $galleryItems.each(function(index) {
            const $img = $(this).find('img');
            galleryImages.push({
                src: $img.attr('src'),
                alt: $img.attr('alt'),
                type: $(this).find('.gallery-type').text()
            });
        });
        
        // Gallery item click
        $galleryItems.on('click', function() {
            currentGalleryIndex = $(this).index();
            openLightbox(galleryImages[currentGalleryIndex]);
        });
        
        // Gallery view button click
        $('.gallery-view').on('click', function(e) {
            e.stopPropagation();
            const $galleryItem = $(this).closest('.gallery-item');
            currentGalleryIndex = $galleryItem.index();
            openLightbox(galleryImages[currentGalleryIndex]);
        });
    }
    
    function openLightbox(imageData) {
        // Remove any existing lightbox first
        $('.lightbox-overlay').remove();
        $('body').removeClass('lightbox-open');
        
        const lightboxHtml = `
            <div class="lightbox-overlay">
                <div class="lightbox-container">
                    <button class="lightbox-close" type="button">
                        <i class="fa fa-times"></i>
                    </button>
                    <img src="${imageData.src}" alt="${imageData.alt}" class="lightbox-image">
                    <div class="lightbox-caption">
                        <strong>${imageData.alt}</strong>
                        <br>
                        <span class="lightbox-type">${imageData.type}</span>
                    </div>
                    <div class="lightbox-navigation">
                        <button class="lightbox-prev" type="button">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <button class="lightbox-next" type="button">
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(lightboxHtml);
        $('body').addClass('lightbox-open');
        
        // Force reflow
        $('.lightbox-overlay')[0].offsetHeight;
        
        setTimeout(() => {
            $('.lightbox-overlay').addClass('show');
        }, 10);
        
        // Lightbox controls
        initLightboxControls();
    }
    
    function initLightboxControls() {
        const $overlay = $('.lightbox-overlay');
        
        // Close lightbox
        $overlay.find('.lightbox-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeLightbox();
        });
        
        $overlay.on('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        
        // Prevent image click from closing
        $overlay.find('.lightbox-container').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Keyboard navigation
        $(document).on('keydown.lightbox', function(e) {
            switch(e.key) {
                case 'Escape':
                    e.preventDefault();
                    closeLightbox();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    navigateGallery('prev');
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    navigateGallery('next');
                    break;
            }
        });
        
        // Navigation buttons
        $overlay.find('.lightbox-prev').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navigateGallery('prev');
        });
        
        $overlay.find('.lightbox-next').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navigateGallery('next');
        });
        
        // Image load handling
        const $img = $overlay.find('.lightbox-image');
        $img.on('load', function() {
            $(this).addClass('loaded');
        }).on('error', function() {
            $(this).attr('src', '/assets/images/placeholder.jpg');
        });
    }
    
    function navigateGallery(direction) {
        const $galleryItems = $('.gallery-item');
        const totalImages = $galleryItems.length;
        
        if (totalImages <= 1) return;
        
        if (direction === 'prev') {
            currentGalleryIndex = currentGalleryIndex > 0 ? currentGalleryIndex - 1 : totalImages - 1;
        } else {
            currentGalleryIndex = currentGalleryIndex < totalImages - 1 ? currentGalleryIndex + 1 : 0;
        }
        
        const $currentItem = $galleryItems.eq(currentGalleryIndex);
        const imageData = {
            src: $currentItem.find('img').attr('src'),
            alt: $currentItem.find('img').attr('alt'),
            type: $currentItem.find('.gallery-type').text() || 'Imagem'
        };
        
        const $lightboxImg = $('.lightbox-image');
        const $lightboxCaption = $('.lightbox-caption');
        
        // Add loading state
        $lightboxImg.removeClass('loaded');
        
        $lightboxImg.fadeOut(LIGHTBOX_FADE_SPEED, function() {
            $lightboxImg.attr('src', imageData.src).attr('alt', imageData.alt);
            $lightboxCaption.html(`
                <strong>${imageData.alt}</strong>
                <br>
                <span class="lightbox-type">${imageData.type}</span>
            `);
            $lightboxImg.fadeIn(LIGHTBOX_FADE_SPEED);
        });
    }
    
    function closeLightbox() {
        $('.lightbox-overlay').removeClass('show');
        setTimeout(() => {
            $('.lightbox-overlay').remove();
            $('body').removeClass('lightbox-open');
        }, 300);
        $(document).off('keydown.lightbox');
    }
    
    // ========================================
    // OBJECTIVE INTERACTIONS
    // ========================================
    
    function initObjectives() {
        $('.objective-item').on('click', function() {
            $(this).toggleClass('expanded');
            
            // Add completion simulation for demo
            if ($(this).hasClass('primary')) {
                simulateObjectiveProgress($(this));
            }
        });
        
        // Objective completion simulation
        $('.objective-item').each(function(index) {
            const $objective = $(this);
            const delay = index * 1000; // Stagger animations
            
            setTimeout(() => {
                $objective.addClass('fade-in-up visible');
            }, delay);
        });
    }
    
    function simulateObjectiveProgress($objective) {
        const $status = $objective.find('.objective-status .status-badge');
        const currentStatus = $status.text().trim();
        
        if (currentStatus === 'Obrigatório') {
            $status.removeClass('required').addClass('in-progress')
                   .css('background', 'var(--color-warning)')
                   .text('Em Progresso');
        } else if (currentStatus === 'Em Progresso') {
            $status.removeClass('in-progress').addClass('completed')
                   .css('background', 'var(--color-success)')
                   .text('Concluído');
            
            // Add celebration effect
            createCelebrationEffect($objective);
        }
    }
    
    function createCelebrationEffect($element) {
        const colors = ['#ff4d6d', '#ff0080', '#74b9ff', '#00d084'];
        
        for (let i = 0; i < 10; i++) {
            const $particle = $('<div class="celebration-particle"></div>');
            $particle.css({
                position: 'absolute',
                width: '6px',
                height: '6px',
                borderRadius: '50%',
                backgroundColor: colors[Math.floor(Math.random() * colors.length)],
                pointerEvents: 'none',
                zIndex: 9999
            });
            
            const rect = $element[0].getBoundingClientRect();
            const startX = rect.left + rect.width / 2;
            const startY = rect.top + rect.height / 2;
            
            $particle.css({
                left: startX + 'px',
                top: startY + 'px'
            });
            
            $('body').append($particle);
            
            // Animate particle
            const angle = (Math.PI * 2 * i) / 10;
            const distance = 50 + Math.random() * 50;
            const endX = startX + Math.cos(angle) * distance;
            const endY = startY + Math.sin(angle) * distance;
            
            $particle.animate({
                left: endX + 'px',
                top: endY + 'px',
                opacity: 0
            }, 1000, function() {
                $particle.remove();
            });
        }
    }
    
    // ========================================
    // TIMELINE INTERACTIONS
    // ========================================
    
    function initTimeline() {
        const $timelineItems = $('.timeline-item');
        
        // Add intersection observer for timeline animations
        if ('IntersectionObserver' in window) {
            const timelineObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('animate-in');
                    }
                });
            }, {
                threshold: 0.3
            });
            
            $timelineItems.each(function() {
                timelineObserver.observe(this);
            });
        }
        
        // Timeline item click interactions
        $timelineItems.on('click', function() {
            const $this = $(this);
            const $marker = $this.find('.timeline-marker');
            
            // Add pulse effect
            $marker.addClass('pulse-effect');
            setTimeout(() => {
                $marker.removeClass('pulse-effect');
            }, 600);
            
            // Show phase details
            showPhaseDetails($this);
        });
    }
    
    function showPhaseDetails($timelineItem) {
        const phase = $timelineItem.find('h4').text();
        const description = $timelineItem.find('p').text();
        const duration = $timelineItem.find('.timeline-duration').text();
        
        showNotification(`Fase: ${phase} - ${duration}`, 'info', 4000);
    }
    
    // ========================================
    // REWARDS INTERACTIONS
    // ========================================
    
    function initRewards() {
        $('.reward-item, .consequence-item').on('mouseenter', function() {
            $(this).find('.reward-icon, .consequence-icon').addClass('bounce-effect');
        }).on('mouseleave', function() {
            $(this).find('.reward-icon, .consequence-icon').removeClass('bounce-effect');
        });
        
        // Reward click interactions
        $('.reward-item').on('click', function() {
            const $this = $(this);
            const rewardName = $this.find('h4').text();
            const rewardAmount = $this.find('.reward-amount').text();
            
            if (rewardAmount) {
                showNotification(`Recompensa: ${rewardName} - ${rewardAmount}`, 'success', 3000);
            } else {
                showNotification(`Recompensa: ${rewardName}`, 'success', 3000);
            }
            
            // Add collection effect
            createRewardCollectionEffect($this);
        });
    }
    
    function createRewardCollectionEffect($element) {
        const $icon = $element.find('.reward-icon');
        $icon.addClass('reward-collected');
        
        setTimeout(() => {
            $icon.removeClass('reward-collected');
        }, 1000);
    }
    
    // ========================================
    // TIPS AND INTERACTIONS
    // ========================================
    
    function initTips() {
        $('.tip-item').on('click', function() {
            const tipText = $(this).find('.tip-text').text();
            showNotification(`Dica: ${tipText}`, 'info', 4000);
            
            $(this).addClass('tip-highlighted');
            setTimeout(() => {
                $(this).removeClass('tip-highlighted');
            }, 2000);
        });
        
        // Random tip system
        let tipInterval;
        
        function showRandomTip() {
            const tips = [
                'Salve o jogo antes de tentar missões difíceis!',
                'Use fones de ouvido para uma experiência mais imersiva.',
                'Explore diferentes estratégias para descobrir a que mais combina com você.',
                'Pratique no modo livre antes de encarar missões principais.',
                'Mantenha sempre munição e coletes reserva.'
            ];
            
            const randomTip = tips[Math.floor(Math.random() * tips.length)];
            showNotification(`💡 ${randomTip}`, 'info', 5000);
        }
        
        // Show random tip every 2 minutes
        tipInterval = setInterval(showRandomTip, 120000);
        
        // Clear interval on page unload
        $(window).on('beforeunload', function() {
            clearInterval(tipInterval);
        });
    }
    
    // ========================================
    // EDIT MODAL
    // ========================================
    
    function showEditModal() {
        // Remove any existing modal first
        $('.edit-modal-overlay').remove();
        
        const modalHtml = `
            <div class="edit-modal-overlay">
                <div class="edit-modal">
                    <div class="modal-header">
                        <h3>
                            <i class="fa fa-edit"></i>
                            Sugerir Edição
                        </h3>
                        <button class="modal-close" type="button">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="edit-form">
                            <div class="form-group">
                                <label for="edit-section">Seção:</label>
                                <select id="edit-section" name="section" required>
                                    <option value="">Selecione uma seção</option>
                                    <option value="overview">Visão Geral</option>
                                    <option value="objectives">Objetivos</option>
                                    <option value="strategy">Estratégias</option>
                                    <option value="rewards">Recompensas</option>
                                    <option value="timeline">Cronograma</option>
                                    <option value="other">Outra</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-type">Tipo de Edição:</label>
                                <select id="edit-type" name="type" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="correction">Correção</option>
                                    <option value="addition">Adição</option>
                                    <option value="update">Atualização</option>
                                    <option value="removal">Remoção</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit-description">Descrição:</label>
                                <textarea id="edit-description" name="description" rows="4" 
                                    placeholder="Descreva sua sugestão de edição..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="edit-source">Fonte (opcional):</label>
                                <input type="text" id="edit-source" name="source" 
                                    placeholder="Link ou referência que comprova a informação">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline modal-cancel">
                                    <i class="fa fa-times"></i>
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-paper-plane"></i>
                                    Enviar Sugestão
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('body').addClass('modal-open');
        
        // Force reflow
        $('.edit-modal-overlay')[0].offsetHeight;
        
        setTimeout(() => {
            $('.edit-modal-overlay').addClass('show');
        }, 10);
        
        initEditModalControls();
    }
    
    function initEditModalControls() {
        const $overlay = $('.edit-modal-overlay');
        
        // Close modal
        $overlay.find('.modal-close, .modal-cancel').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeEditModal();
        });
        
        $overlay.on('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Form submission
        $overlay.find('.edit-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                section: $('#edit-section').val(),
                type: $('#edit-type').val(),
                description: $('#edit-description').val().trim(),
                source: $('#edit-source').val().trim()
            };
            
            if (!formData.section) {
                showNotification('Por favor, selecione uma seção.', 'error');
                $('#edit-section').focus();
                return;
            }
            
            if (!formData.type) {
                showNotification('Por favor, selecione o tipo de edição.', 'error');
                $('#edit-type').focus();
                return;
            }
            
            if (!formData.description) {
                showNotification('Por favor, descreva sua sugestão.', 'error');
                $('#edit-description').focus();
                return;
            }
            
            // Simulate form submission
            submitEditSuggestion(formData);
        });
        
        // Keyboard close
        $(document).on('keydown.editModal', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
        
        // Focus first input
        setTimeout(() => {
            $('#edit-section').focus();
        }, 100);
    }
    
    function submitEditSuggestion(formData) {
        // Simulate API call
        const $submitBtn = $('.edit-form button[type="submit"]');
        const originalHtml = $submitBtn.html();
        
        $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Enviando...').prop('disabled', true);
        
        setTimeout(() => {
            console.log('Edit suggestion submitted:', formData);
            showNotification('Sugestão enviada com sucesso! Obrigado pela contribuição.', 'success');
            closeEditModal();
        }, 2000);
    }
    
    function closeEditModal() {
        $('.edit-modal-overlay').removeClass('show');
        setTimeout(() => {
            $('.edit-modal-overlay').remove();
            $('body').removeClass('modal-open');
        }, 300);
        $(document).off('keydown.editModal');
    }
    
    // ========================================
    // SCROLL ANIMATIONS
    // ========================================
    
    function initScrollAnimations() {
        if ('IntersectionObserver' in window) {
            const animationObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('animate-in');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            // Observe mission sections
            $('.mission-section').each(function() {
                animationObserver.observe(this);
            });
            
            // Observe sidebar widgets
            $('.sidebar-widget').each(function() {
                animationObserver.observe(this);
            });
        }
    }
    
    // ========================================
    // PERFORMANCE OPTIMIZATIONS
    // ========================================
    
    function optimizePerformance() {
        // Lazy load images
        $('img[data-src]').each(function() {
            const $img = $(this);
            const src = $img.data('src');
            
            $img.attr('src', src).removeAttr('data-src');
        });
        
        // Debounce scroll events
        let ticking = false;
        
        function updateScrollDependentElements() {
            // Update scroll progress
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height();
            const winHeight = $(window).height();
            const scrollPercent = scrollTop / (docHeight - winHeight);
            
            // Update any scroll-dependent UI elements here
            $('.scroll-progress').css('width', (scrollPercent * 100) + '%');
            
            ticking = false;
        }
        
        $(window).on('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateScrollDependentElements);
                ticking = true;
            }
        });
    }
    
    // ========================================
    // ACCESSIBILITY ENHANCEMENTS
    // ========================================
    
    function initAccessibility() {
        // Keyboard navigation for tabs
        $('.strategy-tab, .related-tab').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Focus management for modals
        $(document).on('shown.modal', '.edit-modal-overlay', function() {
            $(this).find('input, textarea, select').first().focus();
        });
        
        // Skip links
        if (!$('.skip-link').length) {
            $('body').prepend(`
                <a href="#main-content" class="skip-link">
                    Pular para o conteúdo principal
                </a>
            `);
        }
        
        // ARIA labels for interactive elements
        $('.gallery-item').attr('aria-label', 'Abrir imagem na galeria');
        $('.strategy-tab').attr('aria-label', function() {
            return `Selecionar estratégia ${$(this).find('span').text()}`;
        });
    }
    
    // ========================================
    // ANALYTICS AND TRACKING
    // ========================================
    
    function initAnalytics() {
        // Track page view
        trackEvent('page_view', {
            page: 'mission_detail',
            mission: 'bank_heist'
        });
        
        // Track interaction events
        $('.btn').on('click', function() {
            const action = $(this).find('span').text() || $(this).text();
            trackEvent('button_click', {
                action: action.trim(),
                location: 'mission_page'
            });
        });
        
        // Track scroll depth
        let maxScrollDepth = 0;
        $(window).on('scroll', debounce(function() {
            const scrollDepth = Math.round(($(window).scrollTop() / ($(document).height() - $(window).height())) * 100);
            
            if (scrollDepth > maxScrollDepth) {
                maxScrollDepth = scrollDepth;
                
                // Track scroll milestones
                if (maxScrollDepth >= 25 && maxScrollDepth < 50) {
                    trackEvent('scroll_depth', { depth: '25%' });
                } else if (maxScrollDepth >= 50 && maxScrollDepth < 75) {
                    trackEvent('scroll_depth', { depth: '50%' });
                } else if (maxScrollDepth >= 75 && maxScrollDepth < 100) {
                    trackEvent('scroll_depth', { depth: '75%' });
                } else if (maxScrollDepth >= 100) {
                    trackEvent('scroll_depth', { depth: '100%' });
                }
            }
        }, 1000));
        
        // Track time on page
        const startTime = Date.now();
        $(window).on('beforeunload', function() {
            const timeOnPage = Math.round((Date.now() - startTime) / 1000);
            trackEvent('time_on_page', {
                duration: timeOnPage,
                page: 'mission_detail'
            });
        });
    }
    
    function trackEvent(eventName, data = {}) {
        // Simulate analytics tracking
        console.log(`Analytics Event: ${eventName}`, data);
        
        // Here you would integrate with your analytics service
        // Example: gtag('event', eventName, data);
        // Example: analytics.track(eventName, data);
    }
    
    // ========================================
    // SEARCH FUNCTIONALITY
    // ========================================
    
    function initSearch() {
        const $searchInput = $('.search-input');
        
        if ($searchInput.length) {
            $searchInput.on('input', debounce(function() {
                const query = $(this).val().toLowerCase().trim();
                
                if (query.length >= 2) {
                    performPageSearch(query);
                } else {
                    clearSearchHighlights();
                }
            }, 300));
        }
    }
    
    function performPageSearch(query) {
        clearSearchHighlights();
        
        const $searchableElements = $('.mission-section h2, .mission-section h3, .mission-section h4, .mission-section p, .objective-info h4, .step-content h4');
        let matchCount = 0;
        
        $searchableElements.each(function() {
            const $element = $(this);
            const text = $element.text().toLowerCase();
            
            if (text.includes(query)) {
                highlightSearchResult($element, query);
                matchCount++;
            }
        });
        
        if (matchCount > 0) {
            showNotification(`${matchCount} resultado(s) encontrado(s) para "${query}"`, 'info', 3000);
            
            // Scroll to first result
            const $firstResult = $('.search-highlight').first();
            if ($firstResult.length) {
                smoothScrollTo($firstResult.closest('.mission-section'));
            }
        } else {
            showNotification(`Nenhum resultado encontrado para "${query}"`, 'warning', 3000);
        }
    }
    
    function highlightSearchResult($element, query) {
        const originalText = $element.text();
        const regex = new RegExp(`(${query})`, 'gi');
        const highlightedText = originalText.replace(regex, '<mark class="search-highlight">$1</mark>');
        
        $element.html(highlightedText);
    }
    
    function clearSearchHighlights() {
        $('.search-highlight').each(function() {
            const $parent = $(this).parent();
            $parent.text($parent.text());
        });
    }
    
    // ========================================
    // DARK MODE SUPPORT
    // ========================================
    
    function initDarkMode() {
        // Check for saved theme preference or default to 'light'
        const currentTheme = localStorage.getItem('theme') || 
                           (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
        
        // Theme toggle functionality (if toggle exists)
        $('.theme-toggle').on('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            showNotification(`Tema ${newTheme === 'dark' ? 'escuro' : 'claro'} ativado`, 'info', 2000);
        });
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });
    }
    
    // ========================================
    // PROGRESSIVE WEB APP FEATURES
    // ========================================
    
    function initPWAFeatures() {
        // Service Worker registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('SW registered: ', registration);
                }).catch(function(registrationError) {
                    console.log('SW registration failed: ', registrationError);
                });
            });
        }
        
        // Install prompt
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button if it exists
            $('.install-app-btn').show().on('click', function() {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        trackEvent('app_installed');
                    }
                    deferredPrompt = null;
                });
            });
        });
        
        // Handle app installed
        window.addEventListener('appinstalled', (evt) => {
            trackEvent('app_installed');
            showNotification('App instalado com sucesso!', 'success');
        });
    }
    
    // ========================================
    // ERROR HANDLING
    // ========================================
    
    function initErrorHandling() {
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            trackEvent('javascript_error', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno
            });
        });
        
        // Handle failed image loads
        $('img').on('error', function() {
            $(this).attr('src', '/assets/images/placeholder.jpg');
        });
        
        // Handle AJAX errors
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
            showNotification('Erro de conexão. Tente novamente.', 'error');
            
            trackEvent('ajax_error', {
                url: settings.url,
                status: xhr.status,
                error: thrownError
            });
        });
    }
    
    // ========================================
    // OFFLINE SUPPORT
    // ========================================
    
    function initOfflineSupport() {
        // Check online status
        function updateOnlineStatus() {
            if (navigator.onLine) {
                $('.offline-indicator').removeClass('visible');
                showNotification('Conexão restabelecida!', 'success', 2000);
            } else {
                if (!$('.offline-indicator').length) {
                    $('body').append(`
                        <div class="offline-indicator">
                            <i class="fa fa-wifi"></i>
                            Você está offline
                        </div>
                    `);
                }
                $('.offline-indicator').addClass('visible');
                showNotification('Você está offline. Algumas funcionalidades podem estar limitadas.', 'warning', 5000);
            }
        }
        
        // Listen for online/offline events
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Initial check
        if (!navigator.onLine) {
            updateOnlineStatus();
        }
    }
    
    // ========================================
    // SOCIAL SHARING
    // ========================================
    
    function initSocialSharing() {
        // Social share buttons
        $('.social-share-btn').on('click', function() {
            const platform = $(this).data('platform');
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            const description = encodeURIComponent('Confira esta missão épica de GTA VI!');
            
            let shareUrl = '';
            
            switch(platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
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
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
                trackEvent('social_share', { platform: platform });
            }
        });
    }
    
    // ========================================
    // PRINT FUNCTIONALITY
    // ========================================
    
    function initPrintFunctionality() {
        $('.print-btn').on('click', function() {
            // Prepare page for printing
            $('body').addClass('printing');
            
            // Hide non-essential elements
            $('.sidebar-widget:not(.mission-nav)', '.back-to-hub').hide();
            
            // Print
            window.print();
            
            // Restore after print
            setTimeout(() => {
                $('body').removeClass('printing');
                $('.sidebar-widget', '.back-to-hub').show();
            }, 1000);
            
            trackEvent('page_printed', { page: 'mission_detail' });
        });
    }
    
    // ========================================
    // PERFORMANCE MONITORING
    // ========================================
    
    function initPerformanceMonitoring() {
        // Monitor page load performance
        window.addEventListener('load', function() {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                const loadTime = perfData.loadEventEnd - perfData.fetchStart;
                
                trackEvent('page_performance', {
                    load_time: Math.round(loadTime),
                    dom_content_loaded: Math.round(perfData.domContentLoadedEventEnd - perfData.fetchStart),
                    first_paint: Math.round(performance.getEntriesByType('paint')[0]?.startTime || 0)
                });
            }, 0);
        });
        
        // Monitor memory usage (if available)
        if ('memory' in performance) {
            setInterval(() => {
                const memInfo = performance.memory;
                if (memInfo.usedJSHeapSize > memInfo.jsHeapSizeLimit * 0.9) {
                    console.warn('High memory usage detected');
                    trackEvent('high_memory_usage', {
                        used: memInfo.usedJSHeapSize,
                        limit: memInfo.jsHeapSizeLimit
                    });
                }
            }, 30000);
        }
    }
    
    // ========================================
    // EASTER EGGS AND SPECIAL FEATURES
    // ========================================
    
    function initEasterEggs() {
        // Konami Code
        let konamiCode = [];
        const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
        
        $(document).on('keydown', function(e) {
            konamiCode.push(e.keyCode);
            if (konamiCode.length > konamiSequence.length) {
                konamiCode.shift();
            }
            
            if (konamiCode.join(',') === konamiSequence.join(',')) {
                activateSecretMode();
                konamiCode = [];
            }
        });
        
        // Secret click sequence on logo
        let logoClicks = 0;
        $('.main-logo').on('click', function() {
            logoClicks++;
            if (logoClicks >= 10) {
                showSecretMessage();
                logoClicks = 0;
            }
            
            setTimeout(() => {
                logoClicks = 0;
            }, 5000);
        });
    }
    
    function activateSecretMode() {
        $('body').addClass('secret-mode');
        showNotification('🎮 Modo Secreto Ativado! Easter Egg encontrado!', 'success', 5000);
        
        // Add special effects
        createFireworks();
        
        trackEvent('easter_egg_activated', { type: 'konami_code' });
    }
    
    function showSecretMessage() {
        const messages = [
            '🎯 Você descobriu um segredo!',
            '🕵️ Detetive digital detectado!',
            '🎮 Verdadeiro fã de GTA VI!',
            '⭐ Parabéns pela persistência!',
            '🔍 Explorador nato!'
        ];
        
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        showNotification(randomMessage, 'success', 4000);
        
        trackEvent('easter_egg_activated', { type: 'logo_clicks' });
    }
    
    function createFireworks() {
        const colors = ['#ff4d6d', '#ff0080', '#74b9ff', '#00d084', '#fdcb6e'];
        
        for (let i = 0; i < 20; i++) {
            setTimeout(() => {
                const firework = $('<div class="firework"></div>');
                firework.css({
                    position: 'fixed',
                    width: '4px',
                    height: '4px',
                    backgroundColor: colors[Math.floor(Math.random() * colors.length)],
                    borderRadius: '50%',
                    pointerEvents: 'none',
                    zIndex: 9999,
                    left: Math.random() * window.innerWidth + 'px',
                    top: Math.random() * window.innerHeight + 'px'
                });
                
                $('body').append(firework);
                
                firework.animate({
                    opacity: 0,
                    top: '-=100px'
                }, 2000, function() {
                    firework.remove();
                });
            }, i * 100);
        }
    }
    
    // ========================================
    // GAMIFICATION FEATURES
    // ========================================
    
    function initGamification() {
        let userScore = parseInt(localStorage.getItem('missionPageScore') || '0');
        let achievements = JSON.parse(localStorage.getItem('missionAchievements') || '[]');
        
        // Track user interactions for scoring
        $('.mission-section').on('click', function() {
            addScore(5, 'Seção explorada');
        });
        
        $('.strategy-tab').on('click', function() {
            addScore(10, 'Estratégia analisada');
        });
        
        $('.objective-item').on('click', function() {
            addScore(15, 'Objetivo estudado');
        });
        
        $('.gallery-item').on('click', function() {
            addScore(8, 'Galeria explorada');
        });
        
        function addScore(points, reason) {
            userScore += points;
            localStorage.setItem('missionPageScore', userScore.toString());
            
            showNotification(`+${points} pontos - ${reason}`, 'info', 2000);
            
            checkAchievements();
            trackEvent('gamification_score', { points: points, reason: reason, total: userScore });
        }
        
        function checkAchievements() {
            const newAchievements = [];
            
            if (userScore >= 100 && !achievements.includes('explorer')) {
                newAchievements.push({
                    id: 'explorer',
                    name: 'Explorador',
                    description: 'Explorou amplamente a missão',
                    icon: '🔍'
                });
                achievements.push('explorer');
            }
            
            if (userScore >= 250 && !achievements.includes('strategist')) {
                newAchievements.push({
                    id: 'strategist',
                    name: 'Estrategista',
                    description: 'Analisou todas as estratégias',
                    icon: '🧠'
                });
                achievements.push('strategist');
            }
            
            if (userScore >= 500 && !achievements.includes('master')) {
                newAchievements.push({
                    id: 'master',
                    name: 'Mestre das Missões',
                    description: 'Dominou completamente a missão',
                    icon: '🏆'
                });
                achievements.push('master');
            }
            
            newAchievements.forEach(achievement => {
                showAchievementUnlocked(achievement);
            });
            
            localStorage.setItem('missionAchievements', JSON.stringify(achievements));
        }
        
        function showAchievementUnlocked(achievement) {
            const achievementHtml = `
                <div class="achievement-notification">
                    <div class="achievement-icon">${achievement.icon}</div>
                    <div class="achievement-content">
                        <div class="achievement-title">Conquista Desbloqueada!</div>
                        <div class="achievement-name">${achievement.name}</div>
                        <div class="achievement-desc">${achievement.description}</div>
                    </div>
                </div>
            `;
            
            $('body').append(achievementHtml);
            
            setTimeout(() => {
                $('.achievement-notification').addClass('show');
            }, 100);
            
            setTimeout(() => {
                $('.achievement-notification').removeClass('show');
                setTimeout(() => {
                    $('.achievement-notification').remove();
                }, 500);
            }, 5000);
            
            trackEvent('achievement_unlocked', { achievement: achievement.id });
        }
        
        // Show current score
        if (userScore > 0) {
            setTimeout(() => {
                showNotification(`Sua pontuação atual: ${userScore} pontos`, 'info', 3000);
            }, 2000);
        }
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    function init() {
        console.log('Initializing Mission Page...');
        
        // Core functionality
        initSidebarNavigation();
        initStrategyTabs();
        initRelatedTabs();
        initGallery();
        initObjectives();
        initTimeline();
        initRewards();
        initTips();
        
        // Enhanced features
        initScrollAnimations();
        initAccessibility();
        initAnalytics();
        initSearch();
        initDarkMode();
        initPWAFeatures();
        initErrorHandling();
        initOfflineSupport();
        initSocialSharing();
        initPrintFunctionality();
        
        // Special features
        initEasterEggs();
        initGamification();
        
        // Performance
        optimizePerformance();
        initPerformanceMonitoring();
        
        // Show welcome message
        setTimeout(() => {
            showNotification('Bem-vindo à missão Assalto ao Banco Central! 🎮', 'info', 4000);
        }, 1000);
        
        console.log('Mission Page initialized successfully!');
    }
    
    // Start initialization
    init();
    
    // ========================================
    // PUBLIC API
    // ========================================
    
    // Expose some functions for external use
    window.MissionPage = {
        showNotification: showNotification,
        smoothScrollTo: smoothScrollTo,
        trackEvent: trackEvent,
        openLightbox: openLightbox,
        showEditModal: showEditModal,
        addScore: function(points, reason) {
            // Public method for external scoring
            const userScore = parseInt(localStorage.getItem('missionPageScore') || '0');
            localStorage.setItem('missionPageScore', (userScore + points).toString());
            showNotification(`+${points} pontos - ${reason}`, 'success', 2000);
        }
    };
    
    // ========================================
    // CLEANUP
    // ========================================
    
    $(window).on('beforeunload', function() {
        // Cleanup intervals and observers
        if (typeof tipInterval !== 'undefined') {
            clearInterval(tipInterval);
        }
        
        // Cleanup event listeners
        $(document).off('.mission');
        $(window).off('.mission');
        
        console.log('Mission Page cleaned up');
    });
});

// ========================================
// CSS ANIMATIONS CLASSES
// ========================================

// Add dynamic CSS for animations
const animationCSS = `
<style>
.pulse {
    animation: pulse 0.3s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.bounce-effect {
    animation: bounce 0.6s ease;
}

@keyframes bounce {
    0%, 20%, 53%, 80%, 100% { transform: translateY(0); }
    40%, 43% { transform: translateY(-10px); }
    70% { transform: translateY(-5px); }
    90% { transform: translateY(-2px); }
}

.reward-collected {
    animation: rewardCollected 1s ease;
}

@keyframes rewardCollected {
    0% { transform: scale(1) rotate(0deg); }
    50% { transform: scale(1.2) rotate(180deg); }
    100% { transform: scale(1) rotate(360deg); }
}

.tip-highlighted {
    background: var(--color-accent) !important;
    color: white !important;
    transition: all 0.3s ease;
}

.pulse-effect {
    animation: pulseGlow 0.6s ease;
}

@keyframes pulseGlow {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 77, 109, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(255, 77, 109, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 77, 109, 0); }
}

.fade-in-up {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.fade-in-up.visible {
    opacity: 1;
    transform: translateY(0);
}

.animate-in {
    opacity: 1;
    transform: translateY(0);
}

.search-highlight {
    background: var(--color-accent);
    color: white;
    padding: 2px 4px;
    border-radius: 3px;
}

.offline-indicator {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-warning);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    z-index: 10001;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.offline-indicator.visible {
    opacity: 1;
}

.celebration-particle {
    position: absolute;
    pointer-events: none;
    z-index: 9999;
}

.secret-mode {
    animation: secretGlow 2s ease-in-out infinite alternate;
}

@keyframes secretGlow {
    from { filter: hue-rotate(0deg); }
    to { filter: hue-rotate(360deg); }
}

.firework {
    box-shadow: 0 0 6px currentColor;
}

.achievement-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: white;
    padding: 20px;
    border-radius: 16px;
    text-align: center;
    z-index: 10002;
    opacity: 0;
    transition: all 0.5s ease;
    min-width: 300px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.achievement-notification.show {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.achievement-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.achievement-title {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    opacity: 0.9;
}

.achievement-name {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.achievement-desc {
    font-size: 14px;
    opacity: 0.8;
}

@media (prefers-reduced-motion: reduce) {
    .pulse,
    .bounce-effect,
    .reward-collected,
    .pulse-effect,
    .fade-in-up,
    .celebration-particle,
    .secret-mode,
    .firework,
    .achievement-notification {
        animation: none !important;
        transition: opacity 0.1s ease !important;
    }
}

/* Dark mode variables */
[data-theme="dark"] {
    --bg-light: #2d2d2d;
    --bg-lighter: #383838;
    --text-dark: #ffffff;
    --text-medium: #cccccc;
    --text-light: #999999;
    --border-light: #404040;
}

/* Print styles */
@media print {
    .notification,
    .lightbox-overlay,
    .edit-modal-overlay,
    .offline-indicator,
    .achievement-notification {
        display: none !important;
    }
    
    .mission-section {
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .strategy-panel:not(.active),
    .related-panel:not(.active) {
        display: block !important;
    }
    
    .secret-mode {
        filter: none !important;
    }
}
</style>
`;

// Inject the CSS
$('head').append(animationCSS);