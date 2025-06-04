/**
 * HUB LEONIDA - JavaScript
 * Sistema de base de dados GTA VI
 */

// Namespace para evitar conflitos
window.LeonidaHub = {
    // Configurações
    config: {
        itemsPerPage: 12,
        currentPage: 1,
        totalItems: 315,
        currentView: 'grid',
        currentCategory: 'all',
        currentFilters: {
            sort: 'name',
            type: 'all',
            region: 'all'
        },
        searchQuery: '',
        favorites: JSON.parse(localStorage.getItem('hub-favorites') || '[]')
    },

    // Dados dos itens (simulado - normalmente viria da API)
    data: {
        items: [
            {
                id: 'jason',
                category: 'characters',
                type: 'confirmed',
                region: 'vice-city',
                title: 'Jason',
                description: 'Protagonista masculino de GTA VI. Criminoso experiente em parceria com Lucia.',
                image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSJ1cmwoI2dyYWRpZW50MSIvPgo8ZGVmcz4KPGxpbmVhckdyYWRpZW50IGlkPSJncmFkaWVudDEiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPgo8c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjRkYwMDdGIi8+CjxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iIzAwQkZGRiIvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+Cjx0ZXh0IHg9IjEwMCIgeT0iMTEwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjAiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5KYXNvbjwvdGV4dD4KPC9zdmc+',
                views: 2400,
                lastUpdate: '2 dias',
                tags: ['protagonista', 'principal']
            },
            {
                id: 'lucia',
                category: 'characters',
                type: 'confirmed',
                region: 'vice-city',
                title: 'Lucia',
                description: 'Protagonista feminina de GTA VI. Primeira mulher protagonista da série principal.',
                image: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSJ1cmwoI2dyYWRpZW50MikiLz4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZGllbnQyIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI0ZGNEQ2RCIvPgo8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNGRkNBNTciLz4KPC9saW5lYXJHcmFkaWVudD4KPC9kZWZzPgo8dGV4dCB4PSIxMDAiIHk9IjExMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjIwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+THVjaWE8L3RleHQ+Cjwvc3ZnPg==',
                views: 1800,
                lastUpdate: '2 dias',
                tags: ['protagonista', 'feminina']
            }
            // Adicione mais itens conforme necessário
        ],

        suggestions: [
            { id: 'jason', title: 'Jason', category: 'Personagem', icon: 'fa-user' },
            { id: 'lucia', title: 'Lucia', category: 'Personagem', icon: 'fa-user' },
            { id: 'vice-city-beach', title: 'Vice City Beach', category: 'Localização', icon: 'fa-map-marker-alt' },
            { id: 'everglades', title: 'Everglades', category: 'Localização', icon: 'fa-tree' }
        ]
    },

    // Elementos DOM (cache para performance)
    elements: {
        container: null,
        searchInput: null,
        searchSuggestions: null,
        categoryTabs: null,
        contentGrid: null,
        resultsCount: null,
        viewButtons: null,
        filterSelects: null,
        loadMoreBtn: null,
        modals: null,
        sidebar: null
    },

    // Inicialização
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.setupSearch();
        this.loadInitialData();
        this.initializeFilters();
        this.setupModals();
        this.initializeFavorites();
        
        console.log('🎮 HUB Leonida inicializado com sucesso!');
    },

    // Cache de elementos DOM
    cacheElements: function() {
        const hub = this;
        hub.elements = {
            container: $('.hub-container'),
            searchInput: $('.hub-container .search-input'),
            searchSuggestions: $('.search-suggestions'),
            categoryTabs: $('.category-tabs .tab-btn'),
            contentGrid: $('#content-grid'),
            resultsCount: $('.results-count'),
            viewButtons: $('.view-btn'),
            filterSelects: $('.filter-select'),
            loadMoreBtn: $('.load-more-btn'),
            modals: {
                search: $('#search-modal')
            },
            sidebar: $('.quick-access-sidebar'),
            favoritesList: $('#favorites-list')
        };
    },

    // Eventos
    bindEvents: function() {
        const hub = this;

        // Busca
        hub.elements.searchInput.on('input', function() {
            hub.handleSearch($(this).val());
        });

        hub.elements.searchInput.on('focus', function() {
            hub.showSuggestions();
        });

        // Clique fora fecha sugestões
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-container').length) {
                hub.hideSuggestions();
            }
        });

        // Tabs de categoria
        hub.elements.categoryTabs.on('click', function() {
            const category = $(this).data('category');
            hub.filterByCategory(category);
        });

        // Botões de visualização
        hub.elements.viewButtons.on('click', function() {
            const view = $(this).data('view');
            hub.changeView(view);
        });

        // Filtros
        hub.elements.filterSelects.on('change', function() {
            hub.applyFilters();
        });

        // Clear filters
        $('.filter-clear-btn').on('click', function() {
            hub.clearFilters();
        });

        // Load More
        hub.elements.loadMoreBtn.on('click', function() {
            hub.loadMoreItems();
        });

        // Favoritos
        $(document).on('click', '.favorite-btn', function(e) {
            e.stopPropagation();
            const itemId = $(this).data('id');
            hub.toggleFavorite(itemId);
        });

        // Share
        $(document).on('click', '.share-btn', function(e) {
            e.stopPropagation();
            const itemId = $(this).data('id');
            hub.shareItem(itemId);
        });

        // Modais
        $('.search-toggle').on('click', function() {
            hub.openAdvancedSearch();
        });

        $('.modal-close').on('click', function() {
            hub.closeModals();
        });

        // FAB Actions
        $('.fab[data-action]').on('click', function() {
            const action = $(this).data('action');
            hub.handleFabAction(action);
        });

        // Sidebar toggle
        $('.sidebar-toggle').on('click', function() {
            hub.toggleSidebar();
        });

        // Breadcrumb actions
        $('.page-actions .action-btn').on('click', function() {
            const action = $(this).attr('class').split(' ').find(cls => cls.endsWith('-btn'));
            hub.handlePageAction(action);
        });
    },

    // Sistema de busca
    setupSearch: function() {
        const hub = this;
        
        // Debounce para performance
        hub.searchDebounce = null;
    },

    handleSearch: function(query) {
        const hub = this;
        
        clearTimeout(hub.searchDebounce);
        hub.searchDebounce = setTimeout(function() {
            hub.config.searchQuery = query.toLowerCase();
            
            if (query.length >= 2) {
                hub.filterSuggestions(query);
                hub.showSuggestions();
            } else {
                hub.hideSuggestions();
            }
            
            hub.renderItems();
        }, 300);
    },

    filterSuggestions: function(query) {
        const hub = this;
        const filtered = hub.data.suggestions.filter(item => 
            item.title.toLowerCase().includes(query) ||
            item.category.toLowerCase().includes(query)
        );
        
        hub.renderSuggestions(filtered);
    },

    renderSuggestions: function(suggestions) {
        const hub = this;
        const container = hub.elements.searchSuggestions;
        
        container.empty();
        
        suggestions.slice(0, 5).forEach(item => {
            const suggestion = $(`
                <div class="suggestion-item" data-id="${item.id}">
                    <div class="suggestion-icon ${item.category.toLowerCase()}">
                        <i class="fa ${item.icon}"></i>
                    </div>
                    <div class="suggestion-text">
                        <div class="suggestion-title">${item.title}</div>
                        <div class="suggestion-category">${item.category}</div>
                    </div>
                </div>
            `);
            
            suggestion.on('click', function() {
                hub.selectSuggestion(item.id);
            });
            
            container.append(suggestion);
        });
    },

    showSuggestions: function() {
        this.elements.searchSuggestions.show();
    },

    hideSuggestions: function() {
        this.elements.searchSuggestions.hide();
    },

    selectSuggestion: function(itemId) {
        const hub = this;
        const item = hub.data.items.find(i => i.id === itemId);
        
        if (item) {
            hub.elements.searchInput.val(item.title);
            hub.hideSuggestions();
            hub.showItemDetail(itemId);
        }
    },

    // Sistema de filtros
    filterByCategory: function(category) {
        const hub = this;
        
        hub.config.currentCategory = category;
        
        // Update tab visual
        hub.elements.categoryTabs.removeClass('active');
        $(`.tab-btn[data-category="${category}"]`).addClass('active');
        
        hub.config.currentPage = 1;
        hub.renderItems();
        hub.updateStats();
    },

    changeView: function(view) {
        const hub = this;
        
        hub.config.currentView = view;
        
        // Update button visual
        hub.elements.viewButtons.removeClass('active');
        $(`.view-btn[data-view="${view}"]`).addClass('active');
        
        // Update grid class
        hub.elements.contentGrid.removeClass('list-view cards-view').addClass(view + '-view');
    },

    applyFilters: function() {
        const hub = this;
        
        hub.config.currentFilters = {
            sort: $('#sort-filter').val(),
            type: $('#type-filter').val(),
            region: $('#region-filter').val()
        };
        
        hub.config.currentPage = 1;
        hub.renderItems();
    },

    clearFilters: function() {
        const hub = this;
        
        // Reset filter selects
        hub.elements.filterSelects.val('');
        $('#sort-filter').val('name');
        $('#type-filter').val('all');
        $('#region-filter').val('all');
        
        // Reset search
        hub.elements.searchInput.val('');
        hub.config.searchQuery = '';
        
        // Reset category
        hub.config.currentCategory = 'all';
        hub.elements.categoryTabs.removeClass('active');
        $('.tab-btn[data-category="all"]').addClass('active');
        
        // Reset config
        hub.config.currentPage = 1;
        hub.config.currentFilters = {
            sort: 'name',
            type: 'all',
            region: 'all'
        };
        
        hub.renderItems();
        hub.updateStats();
        
        // Notification
        hub.showNotification('Filtros limpos com sucesso!', 'success');
    },

    // Renderização
    loadInitialData: function() {
        const hub = this;
        hub.renderItems();
        hub.updateStats();
        hub.loadRecentItems();
        hub.loadPopularItems();
    },

    renderItems: function() {
        const hub = this;
        const filtered = hub.getFilteredItems();
        const paginated = hub.paginateItems(filtered);
        
        hub.elements.contentGrid.empty();
        
        if (paginated.length === 0) {
            hub.showEmptyState();
            return;
        }
        
        paginated.forEach((item, index) => {
            const itemElement = hub.createItemElement(item, index);
            hub.elements.contentGrid.append(itemElement);
        });
        
        hub.updateResultsCount(filtered.length);
        hub.updateLoadMoreButton(filtered.length);
        
        // Animate items
        setTimeout(() => {
            $('.hub-item').addClass('fade-in-visible');
        }, 100);
    },

    getFilteredItems: function() {
        const hub = this;
        let items = [...hub.data.items];
        
        // Filter by category
        if (hub.config.currentCategory !== 'all') {
            items = items.filter(item => item.category === hub.config.currentCategory);
        }
        
        // Filter by search
        if (hub.config.searchQuery) {
            items = items.filter(item => 
                item.title.toLowerCase().includes(hub.config.searchQuery) ||
                item.description.toLowerCase().includes(hub.config.searchQuery) ||
                (item.tags && item.tags.some(tag => tag.toLowerCase().includes(hub.config.searchQuery)))
            );
        }
        
        // Filter by type
        if (hub.config.currentFilters.type !== 'all') {
            items = items.filter(item => item.type === hub.config.currentFilters.type);
        }
        
        // Filter by region
        if (hub.config.currentFilters.region !== 'all') {
            items = items.filter(item => item.region === hub.config.currentFilters.region);
        }
        
        // Sort items
        items = hub.sortItems(items);
        
        return items;
    },

    sortItems: function(items) {
        const hub = this;
        const sortBy = hub.config.currentFilters.sort;
        
        return items.sort((a, b) => {
            switch (sortBy) {
                case 'name':
                    return a.title.localeCompare(b.title);
                case 'name-desc':
                    return b.title.localeCompare(a.title);
                case 'popular':
                    return (b.views || 0) - (a.views || 0);
                case 'recent':
                case 'updated':
                default:
                    return 0; // Manter ordem original para recent/updated
            }
        });
    },

    paginateItems: function(items) {
        const hub = this;
        const start = 0;
        const end = hub.config.currentPage * hub.config.itemsPerPage;
        return items.slice(start, end);
    },

    createItemElement: function(item, index) {
        const hub = this;
        const isFavorited = hub.config.favorites.includes(item.id);
        
        return $(`
            <div class="hub-item fade-in" 
                 data-category="${item.category}" 
                 data-type="${item.type}" 
                 data-region="${item.region}"
                 data-item-id="${item.id}"
                 style="animation-delay: ${index * 0.1}s">
                <div class="item-image">
                    <img src="${item.image}" alt="${item.title}">
                    <div class="item-overlay">
                        <button class="favorite-btn ${isFavorited ? 'favorited' : ''}" data-id="${item.id}">
                            <i class="fa fa-heart"></i>
                        </button>
                        <button class="share-btn" data-id="${item.id}">
                            <i class="fa fa-share"></i>
                        </button>
                    </div>
                    <div class="item-badge ${item.type}">${hub.getBadgeText(item.type)}</div>
                </div>
                <div class="item-content">
                    <div class="item-category">
                        <i class="fa ${hub.getCategoryIcon(item.category)}"></i>
                        ${hub.getCategoryName(item.category)}
                    </div>
                    <h3 class="item-title">${item.title}</h3>
                    <p class="item-description">${item.description}</p>
                    <div class="item-meta">
                        <span class="meta-tag">
                            <i class="fa fa-map-marker-alt"></i>
                            ${hub.getRegionName(item.region)}
                        </span>
                        <span class="meta-tag">
                            <i class="fa fa-clock"></i>
                            Atualizado há ${item.lastUpdate}
                        </span>
                    </div>
                </div>
            </div>
        `);
    },

    // Utilitários
    getBadgeText: function(type) {
        const types = {
            'confirmed': 'Confirmado',
            'rumor': 'Rumor',
            'theory': 'Teoria',
            'leaked': 'Vazamento'
        };
        return types[type] || type;
    },

    getCategoryIcon: function(category) {
        const icons = {
            'characters': 'fa-users',
            'locations': 'fa-map-marker-alt',
            'vehicles': 'fa-car',
            'missions': 'fa-tasks'
        };
        return icons[category] || 'fa-question';
    },

    getCategoryName: function(category) {
        const names = {
            'characters': 'Personagem',
            'locations': 'Localização',
            'vehicles': 'Veículo',
            'missions': 'Missão'
        };
        return names[category] || category;
    },

    getRegionName: function(region) {
        const names = {
            'vice-city': 'Vice City',
            'keys': 'Florida Keys',
            'everglades': 'Everglades',
            'miami': 'Miami Beach',
            'unknown': 'Desconhecida'
        };
        return names[region] || region;
    },

    showEmptyState: function() {
        const hub = this;
        hub.elements.contentGrid.html(`
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa fa-search"></i>
                </div>
                <h3>Nenhum item encontrado</h3>
                <p>Tente ajustar os filtros ou buscar por outros termos.</p>
                <button class="btn btn-primary" onclick="LeonidaHub.clearFilters()">
                    <i class="fa fa-refresh"></i>
                    Limpar Filtros
                </button>
            </div>
        `);
    },

    updateResultsCount: function(count) {
        const hub = this;
        hub.elements.resultsCount.text(`${count} itens encontrados`);
    },

    updateLoadMoreButton: function(totalFiltered) {
        const hub = this;
        const shown = hub.config.currentPage * hub.config.itemsPerPage;
        const remaining = totalFiltered - shown;
        
        if (remaining > 0) {
            hub.elements.loadMoreBtn.show();
            hub.elements.loadMoreBtn.find('.load-count').text(`(${remaining} restantes)`);
        } else {
            hub.elements.loadMoreBtn.hide();
        }
    },

    loadMoreItems: function() {
        const hub = this;
        hub.config.currentPage++;
        hub.renderItems();
        
        // Scroll to new items
        setTimeout(() => {
            const newItems = $('.hub-item').slice(-hub.config.itemsPerPage);
            if (newItems.length > 0) {
                newItems[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    },

    updateStats: function() {
        const hub = this;
        const filtered = hub.getFilteredItems();
        
        // Update category counts
        const counts = {
            all: hub.data.items.length,
            characters: hub.data.items.filter(i => i.category === 'characters').length,
            locations: hub.data.items.filter(i => i.category === 'locations').length,
            vehicles: hub.data.items.filter(i => i.category === 'vehicles').length,
            missions: hub.data.items.filter(i => i.category === 'missions').length
        };
        
        Object.keys(counts).forEach(category => {
            $(`.tab-btn[data-category="${category}"] .tab-count`).text(counts[category]);
        });
        
        // Update header stats
        $('.stat-card .stat-number').each(function() {
            const card = $(this).closest('.stat-card');
            const category = card.find('.stat-icon').attr('class').split(' ').find(c => c.includes('characters') || c.includes('locations') || c.includes('vehicles') || c.includes('missions'));
            
            if (category) {
                const type = category.split(' ').pop();
                $(this).text(counts[type] || 0);
            }
        });
    },

    // Sistema de favoritos
    initializeFavorites: function() {
        const hub = this;
        hub.updateFavoritesList();
    },

    toggleFavorite: function(itemId) {
        const hub = this;
        const index = hub.config.favorites.indexOf(itemId);
        
        if (index > -1) {
            hub.config.favorites.splice(index, 1);
            hub.showNotification('Removido dos favoritos', 'info');
        } else {
            hub.config.favorites.push(itemId);
            hub.showNotification('Adicionado aos favoritos!', 'success');
        }
        
        // Save to localStorage
        localStorage.setItem('hub-favorites', JSON.stringify(hub.config.favorites));
        
        // Update UI
        hub.updateFavoriteButton(itemId);
        hub.updateFavoritesList();
    },

    updateFavoriteButton: function(itemId) {
        const hub = this;
        const isFavorited = hub.config.favorites.includes(itemId);
        const button = $(`.favorite-btn[data-id="${itemId}"]`);
        
        button.toggleClass('favorited', isFavorited);
        
        if (isFavorited) {
            button.addClass('liked');
            setTimeout(() => button.removeClass('liked'), 600);
        }
    },

    updateFavoritesList: function() {
        const hub = this;
        const container = hub.elements.favoritesList;
        
        container.empty();
        
        if (hub.config.favorites.length === 0) {
            container.html(`
                <div class="empty-favorites">
                    <i class="fa fa-heart"></i>
                    <p>Nenhum favorito ainda</p>
                    <small>Clique no ❤️ para favoritar itens</small>
                </div>
            `);
            return;
        }
        
        hub.config.favorites.forEach(itemId => {
            const item = hub.data.items.find(i => i.id === itemId);
            if (item) {
                const favoriteItem = $(`
                    <a href="#" class="quick-item" data-id="${item.id}">
                        <i class="fa ${hub.getCategoryIcon(item.category)}"></i>
                        <span>${item.title}</span>
                        <small>${item.views || 0} views</small>
                    </a>
                `);
                
                favoriteItem.on('click', function(e) {
                    e.preventDefault();
                    hub.showItemDetail(item.id);
                });
                
                container.append(favoriteItem);
            }
        });
    },

    shareItem: function(itemId) {
        const hub = this;
        const item = hub.data.items.find(i => i.id === itemId);
        
        if (!item) return;
        
        const url = `${window.location.origin}/hub.html#${itemId}`;
        const text = `Confira "${item.title}" no HUB Leonida!`;
        
        if (navigator.share) {
            navigator.share({
                title: item.title,
                text: text,
                url: url
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                hub.showNotification('Link copiado para a área de transferência!', 'success');
            }).catch(() => {
                hub.showNotification('Erro ao copiar link', 'error');
            });
        }
    },

    // Modais
    setupModals: function() {
        const hub = this;
        
        // Close modal on overlay click
        $('.modal-overlay').on('click', function(e) {
            if (e.target === this) {
                hub.closeModals();
            }
        });
        
        // ESC key closes modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                hub.closeModals();
            }
        });
    },

    openAdvancedSearch: function() {
        const hub = this;
        hub.elements.modals.search.addClass('show');
        
        // Focus first input
        setTimeout(() => {
            hub.elements.modals.search.find('.search-term').focus();
        }, 300);
    },

    showItemDetail: function(itemId) {
        const hub = this;
        const item = hub.data.items.find(i => i.id === itemId);
        
        if (!item) return;
        
        const modal = hub.elements.modals.item;
        const content = modal.find('.item-detail-content');
        
        content.html(`
            <div class="item-detail-header">
                <div class="item-detail-image">
                    <img src="${item.image}" alt="${item.title}">
                    <div class="item-detail-badge ${item.type}">${hub.getBadgeText(item.type)}</div>
                </div>
                <div class="item-detail-info">
                    <div class="item-detail-category">
                        <i class="fa ${hub.getCategoryIcon(item.category)}"></i>
                        ${hub.getCategoryName(item.category)}
                    </div>
                    <h2 class="item-detail-title">${item.title}</h2>
                    <p class="item-detail-description">${item.description}</p>
                    <div class="item-detail-meta">
                        <span class="meta-item">
                            <i class="fa fa-map-marker-alt"></i>
                            <strong>Região:</strong> ${hub.getRegionName(item.region)}
                        </span>
                        <span class="meta-item">
                            <i class="fa fa-eye"></i>
                            <strong>Visualizações:</strong> ${item.views || 0}
                        </span>
                        <span class="meta-item">
                            <i class="fa fa-clock"></i>
                            <strong>Última atualização:</strong> há ${item.lastUpdate}
                        </span>
                    </div>
                </div>
            </div>
            <div class="item-detail-actions">
                <button class="btn btn-primary favorite-action ${hub.config.favorites.includes(itemId) ? 'favorited' : ''}" data-id="${itemId}">
                    <i class="fa fa-heart"></i>
                    ${hub.config.favorites.includes(itemId) ? 'Remover dos Favoritos' : 'Adicionar aos Favoritos'}
                </button>
                <button class="btn btn-secondary share-action" data-id="${itemId}">
                    <i class="fa fa-share"></i>
                    Compartilhar
                </button>
                <button class="btn btn-secondary report-action" data-id="${itemId}">
                    <i class="fa fa-flag"></i>
                    Reportar Erro
                </button>
            </div>
            <div class="item-detail-content-sections">
                <div class="detail-section">
                    <h3><i class="fa fa-info-circle"></i> Informações Detalhadas</h3>
                    <p>Aqui viriam informações mais detalhadas sobre ${item.title}, incluindo histórico, aparições em trailers, teorias relacionadas, etc.</p>
                </div>
                <div class="detail-section">
                    <h3><i class="fa fa-link"></i> Itens Relacionados</h3>
                    <p>Links para outros itens relacionados seriam mostrados aqui.</p>
                </div>
            </div>
        `);
        
        // Bind actions
        content.find('.favorite-action').on('click', function() {
            hub.toggleFavorite(itemId);
            $(this).toggleClass('favorited');
            $(this).html(hub.config.favorites.includes(itemId) ? 
                '<i class="fa fa-heart"></i> Remover dos Favoritos' : 
                '<i class="fa fa-heart"></i> Adicionar aos Favoritos'
            );
        });
        
        content.find('.share-action').on('click', function() {
            hub.shareItem(itemId);
        });
        
        content.find('.report-action').on('click', function() {
            hub.reportItem(itemId);
        });
        
        modal.addClass('show');
        
        // Update URL hash
        window.location.hash = itemId;
    },

    closeModals: function() {
        const hub = this;
        $('.modal').removeClass('show');
        
        // Clear URL hash if present
        if (window.location.hash) {
            history.replaceState(null, null, window.location.pathname);
        }
    },

    // Ações da página
    handlePageAction: function(action) {
        const hub = this;
        
        switch (action) {
            case 'search-toggle':
                hub.openAdvancedSearch();
                break;
            case 'view-toggle':
                const currentView = hub.config.currentView;
                const views = ['grid', 'list', 'cards'];
                const nextIndex = (views.indexOf(currentView) + 1) % views.length;
                hub.changeView(views[nextIndex]);
                break;
            case 'favorites-btn':
                hub.toggleSidebar();
                break;
        }
    },

    handleFabAction: function(action) {
        const hub = this;
        
        switch (action) {
            case 'suggest':
                hub.openSuggestionForm();
                break;
            case 'report':
                hub.openReportForm();
                break;
            case 'share':
                hub.shareHub();
                break;
            case 'top':
                $('html, body').animate({ scrollTop: 0 }, 800);
                break;
        }
    },

    toggleSidebar: function() {
        const hub = this;
        hub.elements.sidebar.toggleClass('open');
    },

    // Sidebar content
    loadRecentItems: function() {
        const hub = this;
        const recentContainer = $('.quick-section').first().find('.quick-list');
        
        // Simulate recent items
        const recentItems = hub.data.items.slice(0, 3);
        
        recentItems.forEach(item => {
            const quickItem = $(`
                <a href="#" class="quick-item" data-id="${item.id}">
                    <i class="fa ${hub.getCategoryIcon(item.category)}"></i>
                    <span>${item.title} - Detalhes atualizados</span>
                    <small>há ${item.lastUpdate}</small>
                </a>
            `);
            
            quickItem.on('click', function(e) {
                e.preventDefault();
                hub.showItemDetail(item.id);
            });
            
            recentContainer.append(quickItem);
        });
    },

    loadPopularItems: function() {
        const hub = this;
        const popularContainer = $('.quick-section').eq(1).find('.quick-list');
        
        // Sort by views and get top 3
        const popularItems = [...hub.data.items]
            .sort((a, b) => (b.views || 0) - (a.views || 0))
            .slice(0, 3);
        
        popularItems.forEach(item => {
            const quickItem = $(`
                <a href="#" class="quick-item popular" data-id="${item.id}">
                    <i class="fa ${hub.getCategoryIcon(item.category)}"></i>
                    <span>${item.title}</span>
                    <small>${item.views || 0} visualizações</small>
                </a>
            `);
            
            quickItem.on('click', function(e) {
                e.preventDefault();
                hub.showItemDetail(item.id);
            });
            
            popularContainer.append(quickItem);
        });
    },

    // Advanced Search Form
    initializeFilters: function() {
        const hub = this;
        
        $('.advanced-search-form').on('submit', function(e) {
            e.preventDefault();
            hub.performAdvancedSearch();
        });
        
        $('#clear-search').on('click', function() {
            $('.advanced-search-form')[0].reset();
        });
    },

    performAdvancedSearch: function() {
        const hub = this;
        const form = $('.advanced-search-form');
        
        const searchData = {
            term: form.find('.search-term').val(),
            category: form.find('.search-category').val(),
            type: form.find('.search-type').val(),
            region: form.find('.search-region').val(),
            date: form.find('.search-date').val(),
            sort: form.find('.search-sort').val()
        };
        
        // Apply search
        if (searchData.term) {
            hub.elements.searchInput.val(searchData.term);
            hub.config.searchQuery = searchData.term.toLowerCase();
        }
        
        if (searchData.category) {
            hub.filterByCategory(searchData.category);
        }
        
        // Apply filters
        if (searchData.type) $('#type-filter').val(searchData.type);
        if (searchData.region) $('#region-filter').val(searchData.region);
        if (searchData.sort) $('#sort-filter').val(searchData.sort);
        
        hub.applyFilters();
        hub.closeModals();
        
        hub.showNotification('Busca avançada aplicada!', 'success');
    },

    // Notifications
    showNotification: function(message, type = 'info') {
        const notification = $(`
            <div class="notification notification-${type}">
                <div class="notification-content">
                    <div class="notification-message">${message}</div>
                    <button class="notification-close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        `);
        
        let container = $('#notification-container');
        if (container.length === 0) {
            container = $('<div id="notification-container"></div>');
            $('body').append(container);
        }
        
        container.append(notification);
        
        // Show notification
        setTimeout(() => notification.addClass('show'), 100);
        
        // Auto remove
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 4000);
        
        // Manual close
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        });
    },

    // Additional Features
    openSuggestionForm: function() {
        this.showNotification('Formulário de sugestão em desenvolvimento!', 'info');
    },

    openReportForm: function() {
        this.showNotification('Formulário de report em desenvolvimento!', 'info');
    },

    reportItem: function(itemId) {
        this.showNotification('Report enviado! Obrigado pelo feedback.', 'success');
    },

    shareHub: function() {
        const url = window.location.href;
        const text = 'Confira o HUB Leonida - Base completa de dados do GTA VI!';
        
        if (navigator.share) {
            navigator.share({
                title: 'HUB Leonida',
                text: text,
                url: url
            });
        } else {
            navigator.clipboard.writeText(url).then(() => {
                this.showNotification('Link do HUB copiado!', 'success');
            });
        }
    },

    // URL Hash handling
    handleHashChange: function() {
        const hub = this;
        const hash = window.location.hash.substring(1);
        
        if (hash && hub.data.items.find(item => item.id === hash)) {
            hub.showItemDetail(hash);
        }
    }
};

// Event Listeners para evitar conflitos
$(document).ready(function() {
    // Inicializar apenas se estivermos na página do HUB
    if ($('.hub-container').length > 0) {
        LeonidaHub.init();
        
        // Handle initial hash
        if (window.location.hash) {
            LeonidaHub.handleHashChange();
        }
        
        // Handle hash changes
        $(window).on('hashchange', function() {
            LeonidaHub.handleHashChange();
        });
    }
});

// Prevent conflicts with other pages
if (typeof window.LeonidaBrasil === 'undefined') {
    window.LeonidaBrasil = {};
}
window.LeonidaBrasil.Hub = LeonidaHub;