// ========================================
    // DISCUSSION AND GALLERY BUTTONS
    // ========================================
    
    $('.gallery-btn').click(function() {
        // Scroll to gallery section
        const gallerySection = $('.gallery-section');
        if (gallerySection.length) {
            $('html, body').animate({
                scrollTop: gallerySection.offset().top - 100
            }, 600);
        }
    });
    
    $('.discuss-btn').click(function() {
        // Show modal for forum redirect or simulate forum navigation
        showNotification('Redirecionando para discussões sobre Jason...', 'info');
        setTimeout(() => {
            // In real implementation: window.open('forum.html?topic=jason', '_blank');
            console.log('Navigate to forum discussions about Jason');
        }, 1000);
    });
    
    $('.view-all-gallery').click(function() {
        // Show modal for gallery redirect
        showNotification('Redirecionando para galeria completa...', 'info');
        setTimeout(() => {
            // In real implementation: window.open('galeria.html?character=jason', '_blank');
            console.log('Navigate to full gallery page');
        }, 1000);
    });

    // ========================================
    // TAG INTERACTIONS
    // ========================================
    
    $('.tag-item').click(function() {
        const tag = $(this).text();
        showNotification(`Buscando conteúdo relacionado a: ${tag}`, 'info');
        // In real implementation: window.open(`hub.html?search=${encodeURIComponent(tag)}`, '_blank');
    });

    // ========================================
    // RATING SYSTEM
    // ========================================
    
    let userRating = 0;
    
    function createRatingStars() {
        // Check if rating already exists
        if ($('.character-rating').length > 0) {
            return;
        }
        
        const ratingHTML = `
            <div class="character-rating">
                <span class="rating-label">Avaliar personagem:</span>
                <div class="rating-stars" data-rating="${userRating}">
                    ${[1,2,3,4,5].map(i => 
                        `<span class="star ${i <= userRating ? 'active' : ''}" data-rating="${i}">
                            <i class="fa fa-star"></i>
                        </span>`
                    ).join('')}
                </div>
                <span class="rating-text">${getRatingText(userRating)}</span>
            </div>
        `;
        
        $('.character-actions').after(ratingHTML);
        
        // Handle star clicks (use event delegation to avoid duplicates)
        $(document).off('click.rating').on('click.rating', '.star', function() {
            const rating = parseInt($(this).data('rating'));
            userRating = rating;
            updateRatingDisplay();
            showNotification(`Você avaliou Jason com ${rating} estrela${rating > 1 ? 's' : ''}!`, 'success');
        });
        
        // Handle star hover (use event delegation)
        $(document).off('mouseenter.rating mouseleave.rating')
            .on('mouseenter.rating', '.star', function() {
                const hoverRating = parseInt($(this).data('rating'));
                $('.star').each(function(index) {
                    $(this).toggleClass('hover', index < hoverRating);
                });
            })
            .on('mouseleave.rating', '.rating-stars', function() {
                $('.star').removeClass('hover');
            });
    }
    
    function updateRatingDisplay() {
        $('.star').each(function(index) {
            $(this).toggleClass('active', index < userRating);
        });
        $('.rating-text').text(getRatingText(userRating));
    }
    
    function getRatingText(rating) {
        const texts = {
            0: 'Não avaliado',
            1: 'Muito Ruim',
            2: 'Ruim',
            3: 'Regular',
            4: 'Bom',
            5: 'Excelente'
        };
        return texts[rating] || 'Não avaliado';
    }
    
    // Add rating system to the page (only if it doesn't exist)
    if (!$('.character-rating').length) {
        createRatingStars();
    }

    // ========================================
    // NOTIFICATION SYSTEM
    // ========================================
    
    function showNotification(message, type = 'info') {
        const notificationId = 'notification-' + Date.now();
        const notificationHTML = `
            <div class="notification ${type}" id="${notificationId}">
                <div class="notification-content">
                    <i class="fa ${getNotificationIcon(type)}"></i>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Create notification container if it doesn't exist
        if (!$('#notification-container').length) {
            $('body').append('<div id="notification-container"></div>');
        }
        
        $('#notification-container').append(notificationHTML);
        
        const $notification = $(`#${notificationId}`);
        
        // Show notification
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);
        
        // Auto hide after 4 seconds
        setTimeout(() => {
            hideNotification($notification);
        }, 4000);
        
        // Handle close button
        $notification.find('.notification-close').click(() => {
            hideNotification($notification);
        });
    }
    
    function hideNotification($notification) {
        $notification.removeClass('show');
        setTimeout(() => {
            $notification.remove();
        }, 300);
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

    // ========================================
    // KEYBOARD NAVIGATION
    // ========================================
    
    $(document).keydown(function(e) {
        // ESC key to close modals
        if (e.keyCode === 27) {
            if ($('.lightbox-overlay').length) {
                closeLightbox();
            }
            if ($('.edit-modal-overlay').length) {
                closeEditModal();
            }
        }
    });

    // ========================================
    // SECTION ANIMATIONS
    // ========================================
    
    // Add entrance animations
    
    
    function initAnimations() {
        $('.character-section').each(function(index) {
            $(this).css({
                opacity: 0,
                transform: 'translateY(30px)'
            });
            
            setTimeout(() => {
                $(this).animate({
                    opacity: 1,
                    transform: 'translateY(0)'
                }, 600);
            }, index * 100);
        });
    }
    
    
    /* ========================================
   CHARACTER PAGE JAVASCRIPT - LEONIDA BRASIL
   ======================================== */

$(document).ready(function() {
    // ========================================
    // NAVIGATION AND SCROLLING
    // ========================================
    
    // Add IDs to sections for navigation
    $('.overview-section').attr('id', 'overview');
    $('.abilities-section').attr('id', 'abilities');
    $('.timeline-section').attr('id', 'timeline');
    $('.related-section').attr('id', 'related');
    $('.gallery-section').attr('id', 'gallery');
    
    // Smooth scrolling for navigation links
    $('.character-nav-list .nav-link').click(function(e) {
        e.preventDefault();
        
        const targetId = $(this).attr('href');
        const targetSection = $(targetId);
        
        if (targetSection.length) {
            $('html, body').animate({
                scrollTop: targetSection.offset().top - 100
            }, 600);
            
            // Update active navigation
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        }
    });
    
    // Update navigation on scroll
    $(window).scroll(function() {
        const scrollPos = $(window).scrollTop();
        
        $('.character-section').each(function() {
            const section = $(this);
            const sectionTop = section.offset().top - 150;
            const sectionBottom = sectionTop + section.outerHeight();
            const sectionId = section.attr('id');
            
            if (sectionId && scrollPos >= sectionTop && scrollPos < sectionBottom) {
                $('.nav-link').removeClass('active');
                $(`.nav-link[href="#${sectionId}"]`).addClass('active');
            }
        });
    });

    // ========================================
    // FAVORITE FUNCTIONALITY
    // ========================================
    
    // Use session storage instead of localStorage for demo
    let isFavorited = false;
    
    function updateFavoriteButton() {
        const $favoriteBtn = $('.favorite-btn');
        const $icon = $favoriteBtn.find('.fa');
        const $text = $favoriteBtn.find('span');
        
        if (isFavorited) {
            $favoriteBtn.addClass('favorited');
            $icon.removeClass('fa-heart').addClass('fa-heart-broken');
            if ($text.length) $text.text('Remover Favorito');
            $favoriteBtn.attr('title', 'Remover dos Favoritos');
        } else {
            $favoriteBtn.removeClass('favorited');
            $icon.removeClass('fa-heart-broken').addClass('fa-heart');
            if ($text.length) $text.text('Favoritar');
            $favoriteBtn.attr('title', 'Adicionar aos Favoritos');
        }
    }
    
    $('.favorite-btn').click(function() {
        isFavorited = !isFavorited;
        updateFavoriteButton();
        
        // Animation effect
        $(this).addClass('pulse');
        setTimeout(() => $(this).removeClass('pulse'), 300);
        
        // Show notification
        showNotification(
            isFavorited ? 'Jason adicionado aos favoritos!' : 'Jason removido dos favoritos!',
            isFavorited ? 'success' : 'info'
        );
    });
    
    // Initialize favorite button
    updateFavoriteButton();


    // ========================================
    // SHARE FUNCTIONALITY
    // ========================================
    
    $('.share-btn').click(function() {
        // Create a simple share URL
        const url = window.location.href;
        const title = 'Jason - Protagonista | HUB Leonida';
        
        // Try native sharing first
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Confira as informações sobre Jason, protagonista masculino de GTA VI',
                url: url
            }).then(() => {
                showNotification('Link compartilhado com sucesso!', 'success');
            }).catch(() => {
                fallbackShare();
            });
        } else {
            fallbackShare();
        }
    });
    
    function fallbackShare() {
        const url = window.location.href;
        
        // Try modern clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                showNotification('Link copiado para a área de transferência!', 'success');
            }).catch(() => {
                legacyCopy(url);
            });
        } else {
            legacyCopy(url);
        }
    }
    
    function legacyCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('Link copiado para a área de transferência!', 'success');
        } catch (err) {
            showNotification('Não foi possível copiar o link', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    // ========================================
    // GALLERY FUNCTIONALITY
    // ========================================
    
    $('.gallery-item').click(function() {
        const imageSrc = $(this).find('img').attr('src');
        const imageAlt = $(this).find('img').attr('alt');
        openLightbox(imageSrc, imageAlt);
    });
    
    function openLightbox(src, alt) {
        const lightboxHTML = `
            <div class="lightbox-overlay" id="lightboxOverlay">
                <div class="lightbox-container">
                    <button class="lightbox-close" id="lightboxClose">
                        <i class="fa fa-times"></i>
                    </button>
                    <div class="lightbox-content">
                        <img src="${src}" alt="${alt}" class="lightbox-image">
                        <div class="lightbox-caption">${alt}</div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(lightboxHTML);
        
        // Show lightbox with animation
        setTimeout(() => {
            $('#lightboxOverlay').addClass('show');
        }, 10);
        
        // Close lightbox handlers
        $('#lightboxClose').click(function() {
            closeLightbox();
        });
        
        $('#lightboxOverlay').click(function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        
        // ESC key to close
        $(document).on('keyup.lightbox', function(e) {
            if (e.keyCode === 27) {
                closeLightbox();
            }
        });
    }
    
    function closeLightbox() {
        $('#lightboxOverlay').removeClass('show');
        setTimeout(() => {
            $('#lightboxOverlay').remove();
            $(document).off('keyup.lightbox');
        }, 300);
    }

    // ========================================
    // ABILITY BARS ANIMATION
    // ========================================
    
    function animateAbilityBars() {
        $('.ability-card').each(function(index) {
            const $card = $(this);
            const $fill = $card.find('.ability-fill');
            const targetWidth = $fill.get(0).style.width || '0%';
            
            // Reset width and animate
            $fill.css('width', '0%');
            
            setTimeout(() => {
                $fill.animate({
                    width: targetWidth
                }, 1000 + (index * 200));
            }, 500);
        });
    }
    
    // Trigger animation when abilities section comes into view
    let abilitiesAnimated = false;
    
    $(window).scroll(function() {
        if (!abilitiesAnimated) {
            const abilitiesSection = $('.abilities-section');
            if (abilitiesSection.length) {
                const sectionTop = abilitiesSection.offset().top;
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                if (scrollTop + windowHeight > sectionTop + 100) {
                    animateAbilityBars();
                    abilitiesAnimated = true;
                }
            }
        }
    });

    // ========================================
    // EDIT SUGGESTIONS
    // ========================================
    
    $('.edit-btn, .edit-section').click(function() {
        showEditModal();
    });
    
    function showEditModal() {
        // Remove existing modal if any
        $('#editModalOverlay').remove();
        
        const modalHTML = `
            <div class="edit-modal-overlay" id="editModalOverlay">
                <div class="edit-modal">
                    <div class="modal-header">
                        <h3>
                            <i class="fa fa-edit"></i>
                            Sugerir Edição
                        </h3>
                        <button class="modal-close" id="editModalClose">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form class="edit-form" id="editForm">
                            <div class="form-group">
                                <label for="editType">Tipo de Edição:</label>
                                <select name="editType" id="editType" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="info">Informação Incorreta</option>
                                    <option value="missing">Informação Faltando</option>
                                    <option value="image">Nova Imagem</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Seção:</label>
                                <select name="section" id="section">
                                    <option value="overview">Visão Geral</option>
                                    <option value="abilities">Habilidades</option>
                                    <option value="timeline">Linha do Tempo</option>
                                    <option value="related">Relacionados</option>
                                    <option value="gallery">Galeria</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="description">Descrição da Sugestão:</label>
                                <textarea name="description" id="description" rows="4" placeholder="Descreva sua sugestão de edição..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="source">Fonte (opcional):</label>
                                <input type="url" name="source" id="source" placeholder="Link para fonte da informação">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" id="cancelEdit">Cancelar</button>
                                <button type="submit" class="btn btn-primary" id="submitEdit">Enviar Sugestão</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Show modal with animation
        setTimeout(() => {
            $('#editModalOverlay').addClass('show');
        }, 10);
        
        // Close modal handlers (use event delegation)
        $(document).off('click.editModal').on('click.editModal', '#editModalClose, #cancelEdit', function() {
            closeEditModal();
        });
        
        $(document).off('click.editModalOverlay').on('click.editModalOverlay', '#editModalOverlay', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Handle form submission (use event delegation)
        $(document).off('submit.editForm').on('submit.editForm', '#editForm', function(e) {
            e.preventDefault();
            
            const formData = {
                character: 'Jason',
                editType: $('#editType').val(),
                section: $('#section').val(),
                description: $('#description').val(),
                source: $('#source').val(),
                timestamp: new Date().toISOString()
            };
            
            submitEditSuggestion(formData);
        });
    }
    
    function closeEditModal() {
        $('#editModalOverlay').removeClass('show');
        setTimeout(() => {
            $('#editModalOverlay').remove();
        }, 300);
    }
    
    function submitEditSuggestion(data) {
        // Show loading state
        const $submitBtn = $('#editForm button[type="submit"]');
        const originalText = $submitBtn.html();
        $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Enviando...').prop('disabled', true);
        
        // Simulate API delay
        setTimeout(() => {
            closeEditModal();
            showNotification('Sugestão enviada com sucesso! Obrigado pela contribuição.', 'success');
            
            // Log for development (would be actual API call in production)
            console.log('Edit suggestion submitted:', data);
        }, 1500);
    }

    // ========================================
    // DISCUSSION AND GALLERY BUTTONS
    // ========================================
    
    $('.gallery-btn').click(function() {
        // Scroll to gallery section
        const gallerySection = $('.gallery-section');
        if (gallerySection.length) {
            $('html, body').animate({
                scrollTop: gallerySection.offset().top - 100
            }, 600);
        }
    });
    
    $('.discuss-btn').click(function() {
        // Redirect to forum discussions about Jason
        window.open('forum.html?topic=jason', '_blank');
    });
    
    $('.view-all-gallery').click(function() {
        // Redirect to full gallery page
        window.open('galeria.html?character=jason', '_blank');
    });

    // ========================================
    // TAG INTERACTIONS
    // ========================================
    
    $('.tag-item').click(function() {
        const tag = $(this).text();
        const searchUrl = `hub.html?search=${encodeURIComponent(tag)}`;
        window.open(searchUrl, '_blank');
    });

    // ========================================
    // STATISTICS TRACKING
    // ========================================
    
    // Track page view
    trackPageView();
    
    // Track time spent on page
    let timeOnPage = 0;
    const startTime = Date.now();
    
    $(window).on('beforeunload', function() {
        timeOnPage = Math.round((Date.now() - startTime) / 1000);
        trackTimeOnPage(timeOnPage);
    });
    
    function trackPageView() {
        // Increment view count in localStorage for demo
        let viewCount = parseInt(localStorage.getItem('character-jason-views') || '0');
        viewCount++;
        localStorage.setItem('character-jason-views', viewCount);
        
        // Update display if needed
        console.log(`Jason page views: ${viewCount}`);
    }
    
    function trackTimeOnPage(seconds) {
        console.log(`Time spent on Jason page: ${seconds} seconds`);
        // In a real application, this would be sent to analytics
    }

    // ========================================
    // LAZY LOADING FOR IMAGES
    // ========================================
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            }
        });
    });
    
    // Observe all images with data-src attribute
    $('img[data-src]').each(function() {
        imageObserver.observe(this);
    });

    // ========================================
    // RATING SYSTEM
    // ========================================
    
    let userRating = parseInt(localStorage.getItem('character-jason-rating') || '0');
    
    function createRatingStars() {
        const ratingHTML = `
            <div class="character-rating">
                <span class="rating-label">Avaliar personagem:</span>
                <div class="rating-stars" data-rating="${userRating}">
                    ${[1,2,3,4,5].map(i => 
                        `<span class="star ${i <= userRating ? 'active' : ''}" data-rating="${i}">
                            <i class="fa fa-star"></i>
                        </span>`
                    ).join('')}
                </div>
                <span class="rating-text">${getRatingText(userRating)}</span>
            </div>
        `;
        
        $('.character-actions').after(ratingHTML);
        
        // Handle star clicks
        $('.star').click(function() {
            const rating = parseInt($(this).data('rating'));
            userRating = rating;
            localStorage.setItem('character-jason-rating', rating);
            updateRatingDisplay();
            showNotification(`Você avaliou Jason com ${rating} estrela${rating > 1 ? 's' : ''}!`, 'success');
        });
        
        // Handle star hover
        $('.star').hover(
            function() {
                const hoverRating = parseInt($(this).data('rating'));
                $('.star').each(function(index) {
                    $(this).toggleClass('hover', index < hoverRating);
                });
            },
            function() {
                $('.star').removeClass('hover');
            }
        );
    }
    
    function updateRatingDisplay() {
        $('.star').each(function(index) {
            $(this).toggleClass('active', index < userRating);
        });
        $('.rating-text').text(getRatingText(userRating));
    }
    
    function getRatingText(rating) {
        const texts = {
            0: 'Não avaliado',
            1: 'Muito Ruim',
            2: 'Ruim',
            3: 'Regular',
            4: 'Bom',
            5: 'Excelente'
        };
        return texts[rating] || 'Não avaliado';
    }
    
    // Add rating system to the page
    createRatingStars();

    // ========================================
    // NOTIFICATION SYSTEM
    // ========================================
    
    function showNotification(message, type = 'info') {
        const notificationId = 'notification-' + Date.now();
        const notificationHTML = `
            <div class="notification ${type}" id="${notificationId}">
                <div class="notification-content">
                    <i class="fa ${getNotificationIcon(type)}"></i>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Create notification container if it doesn't exist
        if (!$('#notification-container').length) {
            $('body').append('<div id="notification-container"></div>');
        }
        
        $('#notification-container').append(notificationHTML);
        
        const $notification = $(`#${notificationId}`);
        
        // Show notification
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            hideNotification($notification);
        }, 5000);
        
        // Handle close button
        $notification.find('.notification-close').click(() => {
            hideNotification($notification);
        });
    }
    
    function hideNotification($notification) {
        $notification.removeClass('show');
        setTimeout(() => {
            $notification.remove();
        }, 300);
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

    // ========================================
    // KEYBOARD NAVIGATION
    // ========================================
    
    $(document).keydown(function(e) {
        // ESC key to close modals
        if (e.keyCode === 27) {
            $('.lightbox-overlay, .edit-modal-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        }
        
        // Arrow keys for gallery navigation
        if ($('.lightbox-overlay').length) {
            if (e.keyCode === 37) { // Left arrow
                // Previous image logic could go here
            } else if (e.keyCode === 39) { // Right arrow
                // Next image logic could go here
            }
        }
    });

    // ========================================
    // PERFORMANCE MONITORING
    // ========================================
    
    // Monitor page load performance
    window.addEventListener('load', function() {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log(`Page load time: ${loadTime}ms`);
        
        // Track loading performance for optimization
        if (loadTime > 3000) {
            console.warn('Page load time is above optimal threshold');
        }
    });

    // ========================================
    // INITIALIZATION COMPLETE
    // ========================================
    
    console.log('Character page initialized successfully');
    
    // Add entrance animations
    $('.character-section').each(function(index) {
        $(this).css({
            opacity: 0,
            transform: 'translateY(30px)'
        }).delay(index * 100).animate({
            opacity: 1
        }, 600).css('transform', 'translateY(0)');
    });
});