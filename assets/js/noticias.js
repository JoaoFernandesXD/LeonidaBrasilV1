/**
 * Leonida Brasil - Noticias.js
 * JavaScript específico para funcionalidades da página de notícias
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // NEWS PAGE SYSTEM
    // ========================================
    
    class NewsPageSystem {
        constructor() {
            this.currentCategory = 'all';
            this.currentSort = 'recent';
            this.currentView = 'grid';
            this.searchQuery = '';
            this.newsItems = [];
            this.filteredItems = [];
            this.itemsPerPage = 9;
            this.currentPage = 1;
            this.totalItems = 247;
            this.isLoading = false;
            
            this.init();
        }
        
        init() {
            this.cacheElements();
            this.bindEvents();
            this.initNewsItems();
            this.initStatsCounter();
            this.initTrendingUpdates();
            this.setupIntersectionObserver();
            this.loadNewsData();
        }
        
        cacheElements() {
            this.$filterBtns = $('.filter-btn');
            this.$searchInput = $('.news-search');
            this.$clearSearch = $('.clear-search');
            this.$sortSelect = $('.sort-select');
            this.$viewBtns = $('.view-btn');
            this.$newsGrid = $('#newsGrid');
            this.$loadMoreBtn = $('.load-more-btn');
            this.$resultsCount = $('.results-count');
            this.$remainingCount = $('.remaining-count');
            this.$newsletterForm = $('.newsletter-form');
        }
        
        bindEvents() {
            // Category filters
            this.$filterBtns.on('click', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const category = $btn.data('category');
                this.filterByCategory(category);
            });
            
            // Search functionality
            this.$searchInput.on('input', (e) => {
                this.searchQuery = $(e.target).val().toLowerCase().trim();
                this.toggleClearButton();
                this.debounceSearch();
            });
            
            this.$clearSearch.on('click', () => {
                this.clearSearch();
            });
            
            // Sort functionality
            this.$sortSelect.on('change', (e) => {
                this.currentSort = $(e.target).val();
                this.sortNews();
            });
            
            // View toggle
            this.$viewBtns.on('click', (e) => {
                const $btn = $(e.currentTarget);
                const view = $btn.data('view');
                this.changeView(view);
            });
            
            // Load more
            this.$loadMoreBtn.on('click', () => {
                this.loadMoreNews();
            });
            
            // Newsletter
            this.$newsletterForm.on('submit', (e) => {
                e.preventDefault();
                this.handleNewsletterSignup();
            });
            
            // News item clicks
            $(document).on('click', '.news-item, .featured-main, .featured-item', function() {
                const title = $(this).find('h3, h4').text();
                NewsPageSystem.prototype.trackClick('news_click', title);
                NotificationSystem.show(`Abrindo: ${title}`, 'info');
                
                // Simulate navigation
                $(this).addClass('loading');
                setTimeout(() => {
                    $(this).removeClass('loading');
                    // In a real app, you would navigate to the article
                    window.location.href = 'noticia.html';
                }, 1000);
            });
            
            // Trending clicks
            $(document).on('click', '.trending-item', function() {
                const tag = $(this).find('.trend-tag').text();
                NewsPageSystem.prototype.handleTrendingClick(tag);
            });
        }
        
        // ========================================
        // FILTERING SYSTEM
        // ========================================
        
        filterByCategory(category) {
            this.currentCategory = category;
            this.currentPage = 1;
            
            // Update active filter button
            this.$filterBtns.removeClass('active');
            this.$filterBtns.filter(`[data-category="${category}"]`).addClass('active');
            
            // Apply filters
            this.applyFilters();
            this.trackClick('category_filter', category);
            
            NotificationSystem.show(`Filtrando por: ${this.getCategoryName(category)}`, 'info');
        }
        
        applyFilters() {
            this.filteredItems = this.newsItems.filter(item => {
                const matchesCategory = this.currentCategory === 'all' || 
                                      item.category === this.currentCategory;
                const matchesSearch = this.searchQuery === '' || 
                                    item.title.toLowerCase().includes(this.searchQuery) ||
                                    item.content.toLowerCase().includes(this.searchQuery) ||
                                    item.author.toLowerCase().includes(this.searchQuery);
                
                return matchesCategory && matchesSearch;
            });
            
            this.sortNews();
            this.updateDisplay();
        }
        
        getCategoryName(category) {
            const names = {
                'all': 'Todas',
                'trailers': 'Trailers',
                'analises': 'Análises',
                'teorias': 'Teorias',
                'mapas': 'Mapas',
                'personagens': 'Personagens'
            };
            return names[category] || category;
        }
        
        // ========================================
        // SEARCH SYSTEM
        // ========================================
        
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.applyFilters();
                if (this.searchQuery) {
                    this.trackClick('search', this.searchQuery);
                }
            }, 300);
        }
        
        toggleClearButton() {
            if (this.searchQuery) {
                this.$clearSearch.show();
            } else {
                this.$clearSearch.hide();
            }
        }
        
        clearSearch() {
            this.$searchInput.val('');
            this.searchQuery = '';
            this.toggleClearButton();
            this.applyFilters();
            NotificationSystem.show('Busca limpa', 'info');
        }
        
        // ========================================
        // SORTING SYSTEM
        // ========================================
        
        sortNews() {
            switch (this.currentSort) {
                case 'recent':
                    this.filteredItems.sort((a, b) => new Date(b.date) - new Date(a.date));
                    break;
                case 'popular':
                    this.filteredItems.sort((a, b) => b.views - a.views);
                    break;
                case 'comments':
                    this.filteredItems.sort((a, b) => b.comments - a.comments);
                    break;
                case 'views':
                    this.filteredItems.sort((a, b) => b.views - a.views);
                    break;
            }
            
            this.updateDisplay();
        }
        
        // ========================================
        // VIEW SYSTEM
        // ========================================
        
        changeView(view) {
            this.currentView = view;
            
            // Update active view button
            this.$viewBtns.removeClass('active');
            this.$viewBtns.filter(`[data-view="${view}"]`).addClass('active');
            
            // Update grid class
            if (view === 'list') {
                this.$newsGrid.addClass('list-view');
            } else {
                this.$newsGrid.removeClass('list-view');
            }
            
            this.trackClick('view_change', view);
            NotificationSystem.show(`Visualização alterada para: ${view === 'grid' ? 'Grid' : 'Lista'}`, 'info');
        }
        
        // ========================================
        // DATA MANAGEMENT
        // ========================================
        
        initNewsItems() {
            // Get news items from DOM
            this.newsItems = [];
            this.$newsGrid.find('.news-item').each((index, element) => {
                const $item = $(element);
                this.newsItems.push({
                    element: $item,
                    category: $item.data('category') || 'analises',
                    title: $item.find('h3').text().trim(),
                    content: $item.find('p').text().trim(),
                    author: $item.find('.author span').text().trim(),
                    date: this.parseDate($item.find('.date').text().trim()),
                    views: this.parseViews($item.find('.fa-eye').parent().text()),
                    comments: this.parseComments($item.find('.fa-comments').parent().text()),
                    likes: this.parseLikes($item.find('.fa-heart').parent().text())
                });
            });
            
            this.filteredItems = [...this.newsItems];
        }
        
        parseDate(dateStr) {
            // Convert relative dates to actual dates
            const now = new Date();
            if (dateStr.includes('há')) {
                if (dateStr.includes('dia')) {
                    const days = parseInt(dateStr);
                    return new Date(now.getTime() - (days * 24 * 60 * 60 * 1000));
                } else if (dateStr.includes('semana')) {
                    const weeks = parseInt(dateStr);
                    return new Date(now.getTime() - (weeks * 7 * 24 * 60 * 60 * 1000));
                }
            }
            return now;
        }
        
        parseViews(text) {
            const match = text.match(/(\d+\.?\d*)(k?)/);
            if (match) {
                const num = parseFloat(match[1]);
                return match[2] === 'k' ? num * 1000 : num;
            }
            return 0;
        }
        
        parseComments(text) {
            const match = text.match(/(\d+)/);
            return match ? parseInt(match[1]) : 0;
        }
        
        parseLikes(text) {
            const match = text.match(/(\d+\.?\d*)(k?)/);
            if (match) {
                const num = parseFloat(match[1]);
                return match[2] === 'k' ? num * 1000 : num;
            }
            return 0;
        }
        
        // ========================================
        // DISPLAY UPDATES
        // ========================================
        
        updateDisplay() {
            const visibleItems = this.filteredItems.slice(0, this.currentPage * this.itemsPerPage);
            
            // Hide all items first
            this.$newsGrid.find('.news-item').addClass('hidden');
            
            // Show filtered items
            visibleItems.forEach(item => {
                item.element.removeClass('hidden');
            });
            
            // Update results count
            this.updateResultsCount();
            
            // Update load more button
            this.updateLoadMoreButton();
            
            // Show no results message if needed
            this.showNoResultsMessage();
        }
        
        updateResultsCount() {
            const total = this.filteredItems.length;
            const visible = Math.min(this.currentPage * this.itemsPerPage, total);
            
            this.$resultsCount.html(`Mostrando <strong>${visible}</strong> de <strong>${total}</strong> notícias`);
        }
        
        updateLoadMoreButton() {
            const total = this.filteredItems.length;
            const visible = this.currentPage * this.itemsPerPage;
            const remaining = Math.max(0, total - visible);
            
            if (remaining > 0) {
                this.$loadMoreBtn.show();
                this.$remainingCount.text(`(${remaining} restantes)`);
            } else {
                this.$loadMoreBtn.hide();
            }
        }
        
        showNoResultsMessage() {
            if (this.filteredItems.length === 0) {
                if (!$('.no-results').length) {
                    const noResultsHtml = `
                        <div class="no-results">
                            <h3>Nenhuma notícia encontrada</h3>
                            <p>Tente ajustar os filtros ou termo de busca</p>
                            <button class="clear-filters-btn">Limpar Filtros</button>
                        </div>
                    `;
                    this.$newsGrid.append(noResultsHtml);
                    
                    $('.clear-filters-btn').on('click', () => {
                        this.clearAllFilters();
                    });
                }
            } else {
                $('.no-results').remove();
            }
        }
        
        clearAllFilters() {
            this.currentCategory = 'all';
            this.searchQuery = '';
            this.currentPage = 1;
            
            this.$filterBtns.removeClass('active');
            this.$filterBtns.filter('[data-category="all"]').addClass('active');
            this.$searchInput.val('');
            this.toggleClearButton();
            
            this.applyFilters();
            NotificationSystem.show('Todos os filtros foram limpos', 'success');
        }
        
        // ========================================
        // LOAD MORE SYSTEM
        // ========================================
        
        loadMoreNews() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.$loadMoreBtn.addClass('loading');
            
            // Simulate loading delay
            setTimeout(() => {
                this.currentPage++;
                this.updateDisplay();
                this.isLoading = false;
                this.$loadMoreBtn.removeClass('loading');
                
                NotificationSystem.show('Mais notícias carregadas!', 'success');
                this.trackClick('load_more', this.currentPage);
                
                // Scroll to new content
                const firstNewItem = this.$newsGrid.find('.news-item:visible').eq((this.currentPage - 1) * this.itemsPerPage);
                if (firstNewItem.length) {
                    $('html, body').animate({
                        scrollTop: firstNewItem.offset().top - 100
                    }, 500);
                }
            }, 1500);
        }
        
        loadNewsData() {
            // Simulate loading additional news data
            // In a real application, this would be an API call
            const additionalNews = this.generateAdditionalNews();
            
            // Add to existing news items
            additionalNews.forEach(news => {
                this.newsItems.push(news);
            });
            
            this.totalItems = this.newsItems.length;
        }
        
        generateAdditionalNews() {
            const categories = ['analises', 'teorias', 'trailers', 'mapas', 'personagens'];
            const authors = ['NewsWriter', 'AnalystPro', 'TheoryExpert', 'MapGuru', 'CharacterFan'];
            const titles = [
                'Nova Teoria sobre o Final de GTA VI',
                'Análise Técnica: Engine de GTA VI',
                'Personagens Secundários Revelados',
                'Mapa de Vice City: Áreas Secretas',
                'Sistema de Clima Dinâmico Confirmado',
                'Easter Eggs Encontrados no Código',
                'Comparação: GTA VI vs Red Dead 2',
                'Trilha Sonora: Artistas Confirmados',
                'Sistema de Relacionamentos Expandido',
                'Mundo Online: Novos Detalhes'
            ];
            
            const news = [];
            for (let i = 0; i < 20; i++) {
                const category = categories[Math.floor(Math.random() * categories.length)];
                const author = authors[Math.floor(Math.random() * authors.length)];
                const title = titles[Math.floor(Math.random() * titles.length)];
                const daysAgo = Math.floor(Math.random() * 30) + 1;
                
                news.push({
                    element: null, // Will be created dynamically
                    category: category,
                    title: title,
                    content: `Conteúdo detalhado sobre ${title.toLowerCase()}...`,
                    author: author,
                    date: new Date(Date.now() - (daysAgo * 24 * 60 * 60 * 1000)),
                    views: Math.floor(Math.random() * 10000) + 500,
                    comments: Math.floor(Math.random() * 200) + 10,
                    likes: Math.floor(Math.random() * 1000) + 50
                });
            }
            
            return news;
        }
        
        // ========================================
        // NEWSLETTER SYSTEM
        // ========================================
        
        handleNewsletterSignup() {
            const $form = this.$newsletterForm;
            const $input = $form.find('input[type="email"]');
            const $button = $form.find('button');
            const email = $input.val().trim();
            
            if (!email) {
                NotificationSystem.show('Digite um e-mail válido!', 'warning');
                $input.addClass('shake');
                setTimeout(() => $input.removeClass('shake'), 600);
                return;
            }
            
            if (!this.isValidEmail(email)) {
                NotificationSystem.show('E-mail inválido!', 'error');
                $input.addClass('shake');
                setTimeout(() => $input.removeClass('shake'), 600);
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
                const $count = $('.newsletter-info span');
                const currentCount = parseFloat($count.text().replace(/[^\d.]/g, ''));
                $count.text(`${(currentCount + 0.1).toFixed(1)}k+ inscritos`);
                
                this.trackClick('newsletter_signup', email);
            }, 2000);
        }
        
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // ========================================
        // TRENDING SYSTEM
        // ========================================
        
        handleTrendingClick(tag) {
            this.searchQuery = tag.replace('#', '');
            this.$searchInput.val(this.searchQuery);
            this.toggleClearButton();
            this.applyFilters();
            
            NotificationSystem.show(`Buscando por: ${tag}`, 'info');
            this.trackClick('trending_click', tag);
            
            // Scroll to news grid
            $('html, body').animate({
                scrollTop: this.$newsGrid.offset().top - 100
            }, 500);
        }
        
        initTrendingUpdates() {
            // Simulate real-time trending updates
            setInterval(() => {
                this.updateTrendingCounts();
            }, 30000); // Update every 30 seconds
        }
        
        updateTrendingCounts() {
            $('.trend-count').each(function() {
                const $count = $(this);
                const currentText = $count.text();
                const match = currentText.match(/(\d+\.?\d*)(k?)/);
                
                if (match) {
                    let num = parseFloat(match[1]);
                    const suffix = match[2];
                    
                    // Random increment
                    if (Math.random() > 0.7) { // 30% chance to update
                        num += Math.random() * 0.5;
                        if (suffix === 'k') {
                            $count.text(`${num.toFixed(1)}k posts`);
                        } else {
                            $count.text(`${Math.floor(num)} posts`);
                        }
                    }
                }
            });
        }
        
        // ========================================
        // STATS COUNTER
        // ========================================
        
        initStatsCounter() {
            // Animate counters on page load
            this.animateCounters();
            
            // Update stats periodically
            setInterval(() => {
                this.updateStats();
            }, 60000); // Update every minute
        }
        
        animateCounters() {
            $('.stat-number').each(function() {
                const $counter = $(this);
                const target = $counter.text();
                const isK = target.includes('k');
                const isM = target.includes('M');
                
                let numTarget = parseFloat(target.replace(/[^\d.]/g, ''));
                if (isK) numTarget *= 1000;
                if (isM) numTarget *= 1000000;
                
                let current = 0;
                const increment = numTarget / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= numTarget) {
                        current = numTarget;
                        clearInterval(timer);
                    }
                    
                    let display = Math.floor(current);
                    if (isM) {
                        display = (current / 1000000).toFixed(1) + 'M';
                    } else if (isK) {
                        display = (current / 1000).toFixed(1) + 'k';
                    }
                    
                    $counter.text(display);
                }, 20);
            });
        }
        
        updateStats() {
            // Simulate real-time stats updates
            $('.stat-number').each(function() {
                const $stat = $(this);
                const current = $stat.text();
                
                if (Math.random() > 0.8) { // 20% chance to update
                    const isK = current.includes('k');
                    const isM = current.includes('M');
                    let num = parseFloat(current.replace(/[^\d.]/g, ''));
                    
                    if (isK) {
                        num += Math.random() * 0.1;
                        $stat.text(num.toFixed(1) + 'k');
                    } else if (isM) {
                        num += Math.random() * 0.01;
                        $stat.text(num.toFixed(1) + 'M');
                    } else {
                        num += Math.floor(Math.random() * 5) + 1;
                        $stat.text(num);
                    }
                }
            });
        }
        
        // ========================================
        // INTERSECTION OBSERVER
        // ========================================
        
        setupIntersectionObserver() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('fade-in-visible');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });
                
                // Observe news items
                this.$newsGrid.find('.news-item').each(function() {
                    $(this).addClass('fade-in');
                    observer.observe(this);
                });
            }
        }
        
        // ========================================
        // ANALYTICS & TRACKING
        // ========================================
        
        trackClick(action, value = null) {
            // Analytics tracking (simulate)
            console.log(`📊 News Analytics: ${action}`, value || '');
            
            // Track popular searches
            if (action === 'search' && value) {
                this.updatePopularSearches(value);
            }
            
            // Track category preferences
            if (action === 'category_filter' && value !== 'all') {
                this.updateCategoryStats(value);
            }
        }
        
        updatePopularSearches(query) {
            try {
                const searches = JSON.parse(localStorage.getItem('leonida_popular_searches') || '{}');
                searches[query] = (searches[query] || 0) + 1;
                
                // Keep only top 10
                const sorted = Object.entries(searches)
                    .sort(([,a], [,b]) => b - a)
                    .slice(0, 10);
                
                localStorage.setItem('leonida_popular_searches', JSON.stringify(Object.fromEntries(sorted)));
            } catch (e) {
                console.warn('Could not update popular searches:', e);
            }
        }
        
        updateCategoryStats(category) {
            try {
                const stats = JSON.parse(localStorage.getItem('leonida_category_stats') || '{}');
                stats[category] = (stats[category] || 0) + 1;
                localStorage.setItem('leonida_category_stats', JSON.stringify(stats));
            } catch (e) {
                console.warn('Could not update category stats:', e);
            }
        }
        
        // ========================================
        // KEYBOARD SHORTCUTS
        // ========================================
        
        initKeyboardShortcuts() {
            $(document).on('keydown', (e) => {
                // Only if not typing in an input
                if ($(e.target).is('input, textarea, select')) return;
                
                switch(e.key) {
                    case '/': // Focus search
                        e.preventDefault();
                        this.$searchInput.focus();
                        break;
                    case 'Escape': // Clear search
                        if (this.searchQuery) {
                            this.clearSearch();
                        }
                        break;
                    case '1':
                    case '2':
                    case '3':
                    case '4':
                    case '5':
                    case '6':
                        e.preventDefault();
                        const categories = ['all', 'trailers', 'analises', 'teorias', 'mapas', 'personagens'];
                        const categoryIndex = parseInt(e.key) - 1;
                        if (categories[categoryIndex]) {
                            this.filterByCategory(categories[categoryIndex]);
                        }
                        break;
                    case 'g': // Toggle grid view
                        e.preventDefault();
                        this.changeView('grid');
                        break;
                    case 'l': // Toggle list view
                        e.preventDefault();
                        this.changeView('list');
                        break;
                }
            });
        }
    }
    
    // ========================================
    // FEATURED NEWS INTERACTIONS
    // ========================================
    
    class FeaturedNews {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initAutoRotation();
        }
        
        bindEvents() {
            $('.featured-main, .featured-item').hover(
                function() {
                    $(this).find('.news-thumb img').css('transform', 'scale(1.05)');
                },
                function() {
                    $(this).find('.news-thumb img').css('transform', 'scale(1)');
                }
            );
            
            // Play button interactions
            $('.play-overlay, .video-icon').on('click', function(e) {
                e.stopPropagation();
                const $item = $(this).closest('.featured-main, .featured-item, .news-item');
                const title = $item.find('h3, h4').text();
                
                NotificationSystem.show(`Reproduzindo vídeo: ${title}`, 'info');
                
                // Simulate video loading
                $(this).html('<i class="fa fa-spinner fa-spin"></i>');
                setTimeout(() => {
                    $(this).html('<i class="fa fa-play"></i>');
                }, 2000);
            });
        }
        
        initAutoRotation() {
            // Auto-highlight different featured items
            let currentHighlight = 0;
            const $featuredItems = $('.featured-item');
            
            setInterval(() => {
                $featuredItems.removeClass('highlight');
                const $current = $featuredItems.eq(currentHighlight);
                $current.addClass('highlight');
                
                currentHighlight = (currentHighlight + 1) % $featuredItems.length;
            }, 10000); // Rotate every 10 seconds
        }
    }
    
    // ========================================
    // RESPONSIVE MENU SYSTEM
    // ========================================
    
    class ResponsiveNewsMenu {
        constructor() {
            this.init();
        }
        
        init() {
            this.createMobileFilters();
            this.bindEvents();
        }
        
        createMobileFilters() {
            if ($(window).width() <= 768) {
                const $controls = $('.news-controls');
                if (!$controls.find('.mobile-filter-toggle').length) {
                    $controls.prepend(`
                        <button class="mobile-filter-toggle">
                            <i class="fa fa-filter"></i>
                            Filtros
                        </button>
                    `);
                }
            }
        }
        
        bindEvents() {
            $(document).on('click', '.mobile-filter-toggle', function() {
                $('.category-filters').toggleClass('mobile-open');
                $(this).toggleClass('active');
            });
            
            $(window).on('resize', () => {
                if ($(window).width() > 768) {
                    $('.category-filters').removeClass('mobile-open');
                    $('.mobile-filter-toggle').removeClass('active');
                }
            });
        }
    }
    
    // ========================================
    // PERFORMANCE OPTIMIZATION
    // ========================================
    
    class PerformanceOptimizer {
        constructor() {
            this.init();
        }
        
        init() {
            this.lazyLoadImages();
            this.throttleScrollEvents();
            this.optimizeAnimations();
        }
        
        lazyLoadImages() {
            if ('IntersectionObserver' in window) {
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
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }
        
        throttleScrollEvents() {
            let ticking = false;
            
            $(window).on('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(() => {
                        this.handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }
        
        handleScroll() {
            const scrollTop = $(window).scrollTop();
            
            // Add scroll-based effects here
            if (scrollTop > 100) {
                $('.news-controls').addClass('sticky-controls');
            } else {
                $('.news-controls').removeClass('sticky-controls');
            }
        }
        
        optimizeAnimations() {
            // Reduce animations for users who prefer reduced motion
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                $('*').css({
                    'animation-duration': '0.01ms',
                    'transition-duration': '0.01ms'
                });
            }
        }
    }
    
    // ========================================
    // INITIALIZATION
    // ========================================
    
    // Initialize all systems
    const newsPageSystem = new NewsPageSystem();
    const featuredNews = new FeaturedNews();
    const responsiveMenu = new ResponsiveNewsMenu();
    const performanceOptimizer = new PerformanceOptimizer();
    
    // Initialize keyboard shortcuts
    newsPageSystem.initKeyboardShortcuts();
    
    // Show welcome message
    setTimeout(() => {
        NotificationSystem.show('🗞️ Use / para buscar, 1-6 para filtros, G/L para visualização', 'info', 4000);
    }, 1500);
    
    // Log successful initialization
    console.log('📰 News page system loaded successfully!');
});