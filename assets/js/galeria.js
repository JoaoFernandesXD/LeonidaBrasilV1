$(document).ready(function() {
    // Gallery functionality
    let currentView = 'grid';
    let currentCategory = 'all';
    let currentSort = 'newest';
    let currentTags = [];
    let currentPage = 1;
    let isLoading = false;

    // View switching
    $('.view-btn, .grid-btn, .list-btn').click(function() {
        const view = $(this).data('view');
        if (view && view !== currentView) {
            switchView(view);
        }
    });

    function switchView(view) {
        currentView = view;
        
        $('.view-btn, .grid-btn, .list-btn').removeClass('active');
        $(`[data-view="${view}"]`).addClass('active');
        
        if (view === 'grid') {
            $('#galleryGrid').addClass('active').show();
            $('#galleryList').removeClass('active').hide();
        } else if (view === 'list') {
            $('#galleryList').addClass('active').show();
            $('#galleryGrid').removeClass('active').hide();
        }
    }

    // Category filtering
    $('.filter-btn').click(function() {
        const category = $(this).data('category');
        if (category !== currentCategory) {
            filterByCategory(category);
        }
    });

    function filterByCategory(category) {
        currentCategory = category;
        
        $('.filter-btn').removeClass('active');
        $(`.filter-btn[data-category="${category}"]`).addClass('active');
        
        // Update results title and count
        if (category === 'all') {
            $('.gallery-results-title').text('Todos os itens');
            $('.gallery-results-count').text('(2,847 resultados)');
        } else {
            const categoryNames = {
                'screenshots': 'Screenshots',
                'videos': 'Vídeos',
                'fanart': 'Fan Art',
                'wallpapers': 'Wallpapers'
            };
            $('.gallery-results-title').text(categoryNames[category]);
            
            const counts = {
                'screenshots': '1,523',
                'videos': '384',
                'fanart': '567',
                'wallpapers': '373'
            };
            $('.gallery-results-count').text(`(${counts[category]} resultados)`);
        }
        
        filterGalleryItems();
    }

    // Tag filtering
    $('.tag-dropdown-btn').click(function() {
        $(this).parent().toggleClass('active');
    });

    $('.tag-checkbox input').change(function() {
        const tag = $(this).val();
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            if (!currentTags.includes(tag)) {
                currentTags.push(tag);
            }
        } else {
            currentTags = currentTags.filter(t => t !== tag);
        }
        
        filterGalleryItems();
    });

    // Popular tag clicks
    $('.popular-tag').click(function() {
        const tag = $(this).data('tag');
        
        // Check the corresponding checkbox
        $(`.tag-checkbox input[value="${tag}"]`).prop('checked', true).trigger('change');
        
        // Add visual feedback
        $(this).addClass('active');
        setTimeout(() => $(this).removeClass('active'), 300);
    });

    // Search functionality
    $('#gallerySearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterGalleryItems(searchTerm);
    });

    $('#searchBtn').click(function() {
        $('#gallerySearch').focus();
    });

    // Sort functionality
    $('#sortSelect').change(function() {
        currentSort = $(this).val();
        sortGalleryItems();
    });

    function filterGalleryItems(searchTerm = '') {
        $('.gallery-item, .list-item').each(function() {
            const item = $(this);
            const itemCategory = item.data('category');
            const itemTags = (item.data('tags') || '').split(',');
            const itemTitle = item.find('h3').text().toLowerCase();
            
            let show = true;
            
            // Category filter
            if (currentCategory !== 'all' && itemCategory !== currentCategory) {
                show = false;
            }
            
            // Tag filter
            if (currentTags.length > 0) {
                const hasMatchingTag = currentTags.some(tag => itemTags.includes(tag));
                if (!hasMatchingTag) {
                    show = false;
                }
            }
            
            // Search filter
            if (searchTerm && !itemTitle.includes(searchTerm)) {
                show = false;
            }
            
            if (show) {
                item.fadeIn(300);
            } else {
                item.fadeOut(300);
            }
        });
        
        updateResultsCount();
    }

    function sortGalleryItems() {
        const container = currentView === 'grid' ? $('#galleryGrid') : $('#galleryList');
        const items = container.children().get();
        
        items.sort(function(a, b) {
            switch (currentSort) {
                case 'newest':
                    // Sort by newest first (could be based on data attributes)
                    return 0;
                case 'oldest':
                    // Sort by oldest first
                    return 0;
                case 'popular':
                    // Sort by likes count
                    const likesA = parseInt($(a).find('.likes').text().replace(/[^\d]/g, '')) || 0;
                    const likesB = parseInt($(b).find('.likes').text().replace(/[^\d]/g, '')) || 0;
                    return likesB - likesA;
                case 'title':
                    // Sort alphabetically by title
                    const titleA = $(a).find('h3').text().toLowerCase();
                    const titleB = $(b).find('h3').text().toLowerCase();
                    return titleA.localeCompare(titleB);
                default:
                    return 0;
            }
        });
        
        container.append(items);
    }

    function updateResultsCount() {
        const visibleItems = $(currentView === 'grid' ? '#galleryGrid .gallery-item:visible' : '#galleryList .list-item:visible').length;
        $('#currentCount').text(visibleItems);
    }

    // Lightbox functionality
    $(document).on('click', '.zoom-btn, .play-btn', function(e) {
        e.preventDefault();
        const item = $(this).closest('.gallery-item, .list-item');
        openLightbox(item);
    });

    function openLightbox(item) {
        const img = item.find('img').first();
        const title = item.find('h3').text();
        const author = item.find('.author').text();
        const date = item.find('.date').text();
        const isVideo = item.find('.media-type').hasClass('video');
        
        $('#lightboxTitle').text(title);
        $('#lightboxMeta').text(`Por ${author} • ${date}`);
        
        if (isVideo) {
            $('#lightboxImage').hide();
            $('#lightboxVideo').show().attr('src', img.attr('src'));
        } else {
            $('#lightboxVideo').hide();
            $('#lightboxImage').show().attr('src', img.attr('src')).attr('alt', title);
        }
        
        $('#lightboxOverlay').addClass('show');
        $('body').addClass('lightbox-open');
    }

    $('#lightboxClose, #lightboxOverlay').click(function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });

    function closeLightbox() {
        $('#lightboxOverlay').removeClass('show');
        $('body').removeClass('lightbox-open');
        $('#lightboxVideo')[0].pause();
    }

    // Keyboard navigation for lightbox
    $(document).keydown(function(e) {
        if ($('#lightboxOverlay').hasClass('show')) {
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                navigateLightbox('prev');
            } else if (e.key === 'ArrowRight') {
                navigateLightbox('next');
            }
        }
    });

    $('#lightboxPrev').click(() => navigateLightbox('prev'));
    $('#lightboxNext').click(() => navigateLightbox('next'));

    function navigateLightbox(direction) {
        const currentItems = $(currentView === 'grid' ? '#galleryGrid .gallery-item:visible' : '#galleryList .list-item:visible');
        const currentTitle = $('#lightboxTitle').text();
        let currentIndex = -1;
        
        currentItems.each(function(index) {
            if ($(this).find('h3').text() === currentTitle) {
                currentIndex = index;
                return false;
            }
        });
        
        if (currentIndex !== -1) {
            let nextIndex;
            if (direction === 'prev') {
                nextIndex = currentIndex > 0 ? currentIndex - 1 : currentItems.length - 1;
            } else {
                nextIndex = currentIndex < currentItems.length - 1 ? currentIndex + 1 : 0;
            }
            
            openLightbox(currentItems.eq(nextIndex));
        }
    }

    // Favorite functionality
    $(document).on('click', '.favorite-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const icon = btn.find('i');
        
        if (icon.hasClass('fa-heart')) {
            icon.removeClass('fa-heart').addClass('fas fa-heart');
            btn.addClass('favorited');
            showNotification('Adicionado aos favoritos!', 'success');
        } else {
            icon.removeClass('fas fa-heart').addClass('fa-heart');
            btn.removeClass('favorited');
            showNotification('Removido dos favoritos!', 'info');
        }
        
        // Add heart animation
        btn.addClass('heart-beat');
        setTimeout(() => btn.removeClass('heart-beat'), 600);
    });

    // Download functionality
    $(document).on('click', '.download-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const item = $(this).closest('.gallery-item, .list-item, .featured-item');
        const title = item.find('h3').text();
        
        // Simulate download
        $(this).addClass('downloading');
        showNotification(`Iniciando download: ${title}`, 'info');
        
        setTimeout(() => {
            $(this).removeClass('downloading');
            showNotification('Download concluído!', 'success');
        }, 2000);
    });

    // Share functionality
    $(document).on('click', '.share-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const item = $(this).closest('.gallery-item, .list-item, .featured-item');
        const title = item.find('h3').text();
        
        // Simulate sharing
        if (navigator.share) {
            navigator.share({
                title: title,
                url: window.location.href
            });
        } else {
            // Fallback - copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                showNotification('Link copiado para a área de transferência!', 'success');
            });
        }
    });

    // Upload functionality
    $('.upload-btn').click(function() {
        showNotification('Funcionalidade de upload será implementada em breve!', 'info');
    });

    $('.upload-area').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    $('.upload-area').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    $('.upload-area').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        showNotification('Funcionalidade de upload será implementada em breve!', 'info');
    });

    // Slideshow functionality
    $('.slideshow-btn').click(function() {
        startSlideshow();
    });

    function startSlideshow() {
        const items = $('#galleryGrid .gallery-item:visible');
        if (items.length === 0) return;
        
        let currentIndex = 0;
        openLightbox(items.eq(currentIndex));
        
        const interval = setInterval(() => {
            currentIndex = (currentIndex + 1) % items.length;
            openLightbox(items.eq(currentIndex));
        }, 3000);
        
        // Stop slideshow when lightbox is closed
        $('#lightboxOverlay').one('transitionend', function() {
            if (!$(this).hasClass('show')) {
                clearInterval(interval);
            }
        });
        
        showNotification('Slideshow iniciado! Pressione ESC para parar.', 'info');
    }

    // Load more functionality
    $('#loadMoreBtn').click(function() {
        if (isLoading) return;
        
        isLoading = true;
        const btn = $(this);
        const originalText = btn.html();
        
        btn.html('<i class="fa fa-spinner fa-spin"></i> Carregando...')
           .prop('disabled', true);
        
        // Simulate loading
        setTimeout(() => {
            // Add more items (in a real app, this would load from server)
            addMoreGalleryItems();
            
            btn.html(originalText)
               .prop('disabled', false);
            isLoading = false;
            
            showNotification('Mais itens carregados!', 'success');
        }, 1500);
    });

    function addMoreGalleryItems() {
        // Simulate adding more items
        const currentCount = parseInt($('#currentCount').text());
        $('#currentCount').text(Math.min(currentCount + 12, 2847));
        
        // In a real app, you would append new gallery items here
    }

    // Contributor clicks
    $('.contributor-item').click(function() {
        const name = $(this).find('.contributor-name').text();
        $('#gallerySearch').val(name).trigger('input');
        showNotification(`Filtrando por uploads de ${name}`, 'info');
    });

    // Activity item clicks
    $('.activity-item').click(function() {
        const text = $(this).find('.activity-text').text();
        showNotification(`Navegando para: ${text}`, 'info');
    });

    // Close dropdowns when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.tag-dropdown').length) {
            $('.tag-dropdown').removeClass('active');
        }
    });

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="notification ${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--color-success)' : 
                            type === 'error' ? 'var(--color-danger)' : 
                            'var(--color-primary)'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 10000;
                font-size: 13px;
                font-weight: 500;
                max-width: 350px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            ">
                <i class="fa ${type === 'success' ? 'fa-check-circle' : 
                             type === 'error' ? 'fa-exclamation-circle' : 
                             'fa-info-circle'}"></i>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.css('transform', 'translateX(0)');
        }, 100);
        
        setTimeout(() => {
            notification.css('transform', 'translateX(100%)');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Initialize gallery
    filterGalleryItems();
    updateResultsCount();

    // Add loading animations
    $('.gallery-item, .list-item').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 50).animate({
            'opacity': '1'
        }, 400).css('transform', 'translateY(0)');
    });

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Smooth scroll for anchor links
    $('a[href^="#"]').click(function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 500);
        }
    });

    // Search suggestions (mock functionality)
    $('#gallerySearch').on('input', function() {
        const query = $(this).val().toLowerCase();
        if (query.length >= 2) {
            // In a real app, you would show search suggestions here
        }
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('#gallerySearch').focus();
        }
        
        // Numbers 1-5 for category switching
        if (e.key >= '1' && e.key <= '5') {
            const categories = ['all', 'screenshots', 'videos', 'fanart', 'wallpapers'];
            const categoryIndex = parseInt(e.key) - 1;
            if (categories[categoryIndex]) {
                filterByCategory(categories[categoryIndex]);
            }
        }
        
        // G for grid view, L for list view
        if (e.key.toLowerCase() === 'g') {
            switchView('grid');
        } else if (e.key.toLowerCase() === 'l') {
            switchView('list');
        }
    });

    // Auto-save favorites to localStorage (if available)
    if (typeof(Storage) !== "undefined") {
        // Load saved favorites
        const savedFavorites = JSON.parse(localStorage.getItem('galleryFavorites') || '[]');
        savedFavorites.forEach(title => {
            $(`.gallery-item h3:contains("${title}"), .list-item h3:contains("${title}")`)
                .closest('.gallery-item, .list-item')
                .find('.favorite-btn')
                .addClass('favorited')
                .find('i')
                .removeClass('fa-heart')
                .addClass('fas fa-heart');
        });

        // Save favorites when changed
        $(document).on('click', '.favorite-btn', function() {
            setTimeout(() => {
                const favorites = [];
                $('.favorite-btn.favorited').each(function() {
                    const title = $(this).closest('.gallery-item, .list-item, .featured-item').find('h3').text();
                    favorites.push(title);
                });
                localStorage.setItem('galleryFavorites', JSON.stringify(favorites));
            }, 100);
        });
    }
});

// CSS for additional animations and effects
const additionalStyles = `
    <style>
    .heart-beat {
        animation: heartBeat 0.6s ease;
    }
    
    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        25% { transform: scale(1.1); }
        50% { transform: scale(1.2); }
        75% { transform: scale(1.1); }
    }
    
    .downloading {
        position: relative;
        pointer-events: none;
    }
    
    .downloading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 16px;
        height: 16px;
        margin: -8px 0 0 -8px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top: 2px solid rgba(255,255,255,0.8);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .upload-area.dragover {
        background: rgba(255, 0, 127, 0.1);
        border-color: var(--color-primary);
        transform: scale(1.02);
    }
    
    .gallery-item:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
    }
    
    .popular-tag.active {
        transform: scale(0.95);
        transition: transform 0.1s ease;
    }
    
    .contributor-item:hover {
        background: var(--bg-light);
        transform: translateX(4px);
    }
    
    .activity-item:hover {
        background: var(--bg-light);
        transform: translateX(2px);
    }
    
    .favorite-btn.favorited {
        color: var(--color-danger) !important;
    }
    
    .lazy {
        filter: blur(4px);
        transition: filter 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .notification {
            left: 10px !important;
            right: 10px !important;
            max-width: none !important;
            transform: translateY(-100%) !important;
            top: 10px !important;
        }
        
        .notification.show {
            transform: translateY(0) !important;
        }
    }
    </style>
`;

$('head').append(additionalStyles);