/* ========================================
   VEHICLE PAGE JAVASCRIPT - LEONIDA BRASIL
   ======================================== */

   $(document).ready(function() {
    
    // ========================================
    // CUSTOMIZATION TABS
    // ========================================
    
    $('.customization-tab').on('click', function() {
        const category = $(this).data('category');
        
        // Remove active class from all tabs and panels
        $('.customization-tab').removeClass('active');
        $('.customization-panel').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Show corresponding panel
        $(`[data-panel="${category}"]`).addClass('active');
        
        // Add animation
        $(`[data-panel="${category}"]`).css('opacity', '0').animate({opacity: 1}, 300);
    });
    
    // ========================================
    // GALLERY FUNCTIONALITY
    // ========================================
    
    $('.gallery-view').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $galleryItem = $(this).closest('.gallery-item');
        const imageSrc = $galleryItem.find('img').attr('src');
        const imageAlt = $galleryItem.find('img').attr('alt') || 'Imagem do veículo';
        
        showLightbox(imageSrc, imageAlt);
    });
    
    function showLightbox(src, caption) {
        // Remove any existing lightbox
        $('.lightbox-overlay').remove();
        
        const lightboxHtml = `
            <div class="lightbox-overlay">
                <div class="lightbox-container">
                    <button class="lightbox-close" title="Fechar">
                        <i class="fa fa-times"></i>
                    </button>
                    <img src="${src}" alt="${caption}" class="lightbox-image">
                    <div class="lightbox-caption">${caption}</div>
                </div>
            </div>
        `;
        
        // Add to body
        $('body').append(lightboxHtml);
        
        // Prevent body scroll
        $('body').addClass('lightbox-open');
        
        // Show lightbox with animation
        setTimeout(() => {
            $('.lightbox-overlay').addClass('show');
        }, 10);
        
        // Close lightbox events
        $('.lightbox-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeLightbox();
        });
        
        $('.lightbox-overlay').on('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        
        // Close with ESC key
        $(document).on('keydown.lightbox', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeLightbox();
            }
        });
    }
    
    function closeLightbox() {
        $('.lightbox-overlay').removeClass('show');
        $('body').removeClass('lightbox-open');
        
        setTimeout(() => {
            $('.lightbox-overlay').remove();
            $(document).off('keydown.lightbox');
        }, 300);
    }
    
    // ========================================
    // FAVORITE FUNCTIONALITY
    // ========================================
    
    $('.favorite-btn').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        const vehicleId = getVehicleId();
        
        if ($btn.hasClass('favorited')) {
            // Remove from favorites
            $btn.removeClass('favorited');
            $icon.removeClass('fa-heart').addClass('fa-heart-o');
            $btn.find('span').text('Favoritar');
            showNotification('Removido dos favoritos!', 'info');
            
            // Update localStorage
            removeFavorite(vehicleId);
        } else {
            // Add to favorites
            $btn.addClass('favorited');
            $icon.removeClass('fa-heart-o').addClass('fa-heart');
            $btn.find('span').text('Favoritado');
            $btn.addClass('pulse');
            setTimeout(() => $btn.removeClass('pulse'), 600);
            showNotification('Adicionado aos favoritos!', 'success');
            
            // Update localStorage
            addFavorite(vehicleId);
        }
    });
    
    // ========================================
    // SHARE FUNCTIONALITY
    // ========================================
    
    $('.share-btn').on('click', function() {
        const vehicleName = $('.vehicle-title h1').text();
        const url = window.location.href;
        
        if (navigator.share) {
            navigator.share({
                title: `${vehicleName} - Leonida Brasil`,
                text: `Confira este veículo incrível de GTA VI: ${vehicleName}`,
                url: url
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                showNotification('Link copiado para a área de transferência!', 'success');
            });
        }
    });
    
    // ========================================
    // COLOR SELECTION
    // ========================================
    
    $('.color-item').on('click', function() {
        $('.color-item').removeClass('selected');
        $(this).addClass('selected');
        
        const color = $(this).css('background-color');
        updateVehiclePreview('color', color);
        
        showNotification('Cor aplicada!', 'success');
    });
    
    // ========================================
    // WHEEL SELECTION
    // ========================================
    
    $('.wheel-item').on('click', function() {
        $('.wheel-item').removeClass('selected');
        $(this).addClass('selected');
        
        const wheelType = $(this).find('h5').text();
        updateVehiclePreview('wheels', wheelType);
        
        showNotification(`Rodas "${wheelType}" aplicadas!`, 'success');
    });
    
    // ========================================
    // UPGRADE SELECTION
    // ========================================
    
    $('.upgrade-item').on('click', function() {
        const $upgrade = $(this);
        const upgradeName = $upgrade.find('h5').text();
        const upgradePrice = $upgrade.find('.upgrade-price').text();
        
        if ($upgrade.hasClass('selected')) {
            $upgrade.removeClass('selected');
            showNotification(`"${upgradeName}" removido!`, 'info');
        } else {
            $upgrade.addClass('selected');
            showNotification(`"${upgradeName}" instalado por ${upgradePrice}!`, 'success');
        }
        
        updateTotalPrice();
    });
    
    // ========================================
    // TUNING SLIDERS
    // ========================================
    
    $('.tuning-slider input[type="range"]').on('input', function() {
        const value = $(this).val();
        const label = $(this).prev('label').text();
        
        // Visual feedback
        $(this).css('background', `linear-gradient(to right, var(--color-primary) 0%, var(--color-primary) ${value}%, var(--bg-lighter) ${value}%, var(--bg-lighter) 100%)`);
        
        // Update performance preview
        updatePerformancePreview($(this).attr('id'), value);
    });
    
    // ========================================
    // COMPARISON FUNCTIONALITY
    // ========================================
    
    $('.compare-btn').on('click', function() {
        // Add current vehicle to comparison
        const vehicleData = getCurrentVehicleData();
        addToComparison(vehicleData);
        
        showNotification('Veículo adicionado à comparação!', 'success');
    });
    
    // ========================================
    // SMOOTH SCROLLING FOR NAVIGATION
    // ========================================
    
    $('.nav-link').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).attr('href');
        
        if (targetId && targetId !== '#') {
            $('html, body').animate({
                scrollTop: $(targetId).offset().top - 80
            }, 600);
            
            // Update active nav item
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        }
    });
    
    // ========================================
    // SCROLL SPY FOR NAVIGATION
    // ========================================
    
    $(window).on('scroll', function() {
        const scrollTop = $(window).scrollTop();
        
        $('.vehicle-section').each(function() {
            const sectionTop = $(this).offset().top - 100;
            const sectionBottom = sectionTop + $(this).outerHeight();
            const sectionId = $(this).attr('id');
            
            if (scrollTop >= sectionTop && scrollTop < sectionBottom) {
                $('.nav-link').removeClass('active');
                $(`.nav-link[href="#${sectionId}"]`).addClass('active');
            }
        });
    });
    
    // ========================================
    // PERFORMANCE ANIMATIONS
    // ========================================
    
    function animatePerformanceBars() {
        $('.performance-fill').each(function() {
            const width = $(this).data('width') || $(this).css('width');
            $(this).css('width', '0').animate({width: width}, 1500);
        });
    }
    
    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    function getVehicleId() {
        // Extract vehicle ID from URL or data attribute
        return window.location.pathname.split('/').pop().replace('.html', '');
    }
    
    function addFavorite(vehicleId) {
        let favorites = JSON.parse(localStorage.getItem('vehicle_favorites') || '[]');
        if (!favorites.includes(vehicleId)) {
            favorites.push(vehicleId);
            localStorage.setItem('vehicle_favorites', JSON.stringify(favorites));
        }
    }
    
    function removeFavorite(vehicleId) {
        let favorites = JSON.parse(localStorage.getItem('vehicle_favorites') || '[]');
        favorites = favorites.filter(id => id !== vehicleId);
        localStorage.setItem('vehicle_favorites', JSON.stringify(favorites));
    }
    
    function updateVehiclePreview(type, value) {
        // This would update the main vehicle image with the selected customization
        console.log(`Updating ${type} to ${value}`);
        
        // In a real implementation, this would change the vehicle preview image
        // based on the selected customizations
    }
    
    function updateTotalPrice() {
        let totalPrice = 0;
        const basePrice = parseFloat($('.vehicle-meta .meta-item:contains("$")').text().replace(/[^0-9.-]+/g, ''));
        
        $('.upgrade-item.selected').each(function() {
            const upgradePrice = parseFloat($(this).find('.upgrade-price').text().replace(/[^0-9.-]+/g, ''));
            totalPrice += upgradePrice;
        });
        
        const finalPrice = basePrice + totalPrice;
        
        // Update price display
        if ($('.total-price').length) {
            $('.total-price').text(`$${finalPrice.toLocaleString()}`);
        }
    }
    
    function updatePerformancePreview(setting, value) {
        // Update performance bars based on tuning settings
        console.log(`Tuning ${setting} to ${value}%`);
        
        // This would recalculate and update performance metrics
        // based on the current tuning settings
    }
    
    function getCurrentVehicleData() {
        return {
            id: getVehicleId(),
            name: $('.vehicle-title h1').text(),
            class: $('.vehicle-subtitle').text(),
            image: $('.vehicle-image').attr('src'),
            stats: {
                acceleration: $('.performance-card:contains("Aceleração") .performance-rating').text(),
                speed: $('.performance-card:contains("Velocidade") .performance-rating').text(),
                handling: $('.performance-card:contains("Manobrabilidade") .performance-rating').text(),
                braking: $('.performance-card:contains("Frenagem") .performance-rating').text()
            }
        };
    }
    
    function addToComparison(vehicleData) {
        let comparison = JSON.parse(localStorage.getItem('vehicle_comparison') || '[]');
        
        // Remove if already exists
        comparison = comparison.filter(v => v.id !== vehicleData.id);
        
        // Add to beginning
        comparison.unshift(vehicleData);
        
        // Keep only last 3 vehicles
        if (comparison.length > 3) {
            comparison = comparison.slice(0, 3);
        }
        
        localStorage.setItem('vehicle_comparison', JSON.stringify(comparison));
    }
    
    function showNotification(message, type = 'info') {
        const notificationHtml = `
            <div class="notification notification-${type}">
                <div class="notification-content">
                    <i class="fa fa-${getNotificationIcon(type)}"></i>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Create container if it doesn't exist
        if (!$('#notification-container').length) {
            $('body').append('<div id="notification-container"></div>');
        }
        
        const $notification = $(notificationHtml);
        $('#notification-container').append($notification);
        
        // Show notification
        setTimeout(() => $notification.addClass('show'), 100);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        }, 5000);
        
        // Close button
        $notification.find('.notification-close').on('click', function() {
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        });
    }
    
    function getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    function initializePage() {
        // Check if vehicle is favorited
        const vehicleId = getVehicleId();
        const favorites = JSON.parse(localStorage.getItem('vehicle_favorites') || '[]');
        
        if (favorites.includes(vehicleId)) {
            $('.favorite-btn').addClass('favorited');
            $('.favorite-btn i').removeClass('fa-heart-o').addClass('fa-heart');
            $('.favorite-btn span').text('Favoritado');
        }
        
        // Animate performance bars when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animatePerformanceBars();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        const performanceSection = document.querySelector('.performance-section');
        if (performanceSection) {
            observer.observe(performanceSection);
        }
        
        // Initialize tuning sliders
        $('.tuning-slider input[type="range"]').each(function() {
            const value = $(this).val();
            $(this).css('background', `linear-gradient(to right, var(--color-primary) 0%, var(--color-primary) ${value}%, var(--bg-lighter) ${value}%, var(--bg-lighter) 100%)`);
        });
        
        // Set initial total price
        updateTotalPrice();
    }
    
    // Initialize everything when page loads
    initializePage();
    
    // ========================================
    // KEYBOARD SHORTCUTS
    // ========================================
    
    $(document).on('keydown', function(e) {
        // F - Favorite
        if (e.keyCode === 70 && !e.ctrlKey && !e.altKey) {
            e.preventDefault();
            $('.favorite-btn').click();
        }
        
        // S - Share
        if (e.keyCode === 83 && !e.ctrlKey && !e.altKey) {
            e.preventDefault();
            $('.share-btn').click();
        }
        
        // C - Compare
        if (e.keyCode === 67 && !e.ctrlKey && !e.altKey) {
            e.preventDefault();
            $('.compare-btn').click();
        }
        
        // Numbers 1-4 for customization tabs
        if (e.keyCode >= 49 && e.keyCode <= 52) {
            e.preventDefault();
            const tabIndex = e.keyCode - 49;
            $('.customization-tab').eq(tabIndex).click();
        }
    });
    
    // ========================================
    // LAZY LOADING FOR IMAGES
    // ========================================
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.dataset.src;
                
                if (src) {
                    img.src = src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            }
        });
    });
    
    // Observe all images with data-src
    $('img[data-src]').each(function() {
        imageObserver.observe(this);
    });
    
});

// ========================================
// EXTERNAL API FUNCTIONS (for future integration)
// ========================================

function loadVehicleData(vehicleId) {
    // Future: Load vehicle data from API
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve({
                id: vehicleId,
                name: 'Banshee Vice',
                manufacturer: 'Bravado',
                class: 'Super',
                // ... more data
            });
        }, 1000);
    });
}

function saveCustomization(vehicleId, customization) {
    // Future: Save customization to server
    console.log('Saving customization:', customization);
}

function loadUserPreferences() {
    // Future: Load user preferences from server
    return {
        favoriteVehicles: [],
        customizations: {},
        comparisons: []
    };
}