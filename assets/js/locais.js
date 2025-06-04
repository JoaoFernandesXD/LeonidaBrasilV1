/**
 * LOCATION PAGE - JavaScript
 * Sistema de interatividade para páginas de localização
 */

// Namespace para evitar conflitos
window.LeonidaLocation = {
    // Configurações
    config: {
        currentLocation: 'vice-city-beach',
        favorites: JSON.parse(localStorage.getItem('location-favorites') || '[]'),
        mapFullscreen: false,
        activePOI: null
    },

    // Elementos DOM (cache para performance)
    elements: {
        container: null,
        mapContainer: null,
        mapPOIs: null,
        galleryItems: null,
        favoriteBtn: null,
        shareBtn: null,
        editBtn: null,
        navLinks: null,
        mapControls: null
    },

    // Inicialização
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.setupMap();
        this.setupGallery();
        this.setupNavigation();
        this.loadLocationData();
        
        console.log('🗺️ Página de Localização inicializada!');
    },

    // Cache de elementos DOM
    cacheElements: function() {
        const location = this;
        location.elements = {
            container: $('.location-container'),
            mapContainer: $('.map-container'),
            mapPOIs: $('.map-poi'),
            galleryItems: $('.gallery-item'),
            favoriteBtn: $('.favorite-btn'),
            shareBtn: $('.share-btn'),
            editBtn: $('.edit-btn'),
            navLinks: $('.nav-link'),
            mapControls: $('.map-btn'),
            fullscreenBtn: $('.fullscreen-map')
        };
    },

    // Eventos
    bindEvents: function() {
        const location = this;

        // Botões de ação
        location.elements.favoriteBtn.on('click', function() {
            location.toggleFavorite();
        });

        location.elements.shareBtn.on('click', function() {
            location.shareLocation();
        });

        location.elements.editBtn.on('click', function() {
            location.openEditModal();
        });

        // Navegação interna
        location.elements.navLinks.on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            location.scrollToSection(target);
            location.updateActiveNav($(this));
        });

        // Controles do mapa
        location.elements.mapControls.on('click', function() {
            const action = $(this).hasClass('zoom-in') ? 'zoom-in' : 
                          $(this).hasClass('zoom-out') ? 'zoom-out' :
                          $(this).hasClass('toggle-layer') ? 'toggle-layer' : null;
            
            if (action) {
                location.handleMapControl(action);
            }
        });

        // Fullscreen do mapa
        location.elements.fullscreenBtn.on('click', function() {
            location.toggleMapFullscreen();
        });

        // POIs do mapa
        location.elements.mapPOIs.on('click', function() {
            location.selectPOI($(this));
        });

        // Galeria
        location.elements.galleryItems.on('click', function() {
            location.openLightbox($(this));
        });

        // ESC para fechar fullscreen
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                if (location.config.mapFullscreen) {
                    location.toggleMapFullscreen();
                }
                // Also close lightbox if open
                location.closeLightbox();
            }
        });

        // Prevent scroll when in fullscreen
        $(window).on('scroll', function() {
            if (!location.config.mapFullscreen) {
                location.updateScrollSpy();
            }
        });

        // Click outside map to exit fullscreen
        $(document).on('click', function(e) {
            if (location.config.mapFullscreen && 
                !$(e.target).closest('.map-container').length && 
                !$(e.target).closest('.map-controls').length) {
                // Don't exit on click, only on ESC or button
                // location.toggleMapFullscreen();
            }
        });
    },

    // Sistema de navegação
    setupNavigation: function() {
        const location = this;
        
        // Smooth scroll para âncoras
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $(this.getAttribute('href'));
            
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });
    },

    scrollToSection: function(target) {
        if (target && $(target).length) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 80
            }, 800);
        }
    },

    updateActiveNav: function(activeLink) {
        this.elements.navLinks.removeClass('active');
        activeLink.addClass('active');
    },

    updateScrollSpy: function() {
        const location = this;
        const scrollPos = $(window).scrollTop() + 100;
        
        $('.location-section').each(function() {
            const section = $(this);
            const sectionTop = section.offset().top;
            const sectionBottom = sectionTop + section.outerHeight();
            const sectionId = section.attr('id');
            
            if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                location.elements.navLinks.removeClass('active');
                $(`.nav-link[href="#${sectionId}"]`).addClass('active');
            }
        });
    },

    // Sistema de mapa
    setupMap: function() {
        const location = this;
        
        // Adicionar IDs às seções para scroll spy
        $('.overview-section').attr('id', 'overview');
        $('.features-section').attr('id', 'features');
        $('.map-section').attr('id', 'map');
        $('.missions-section').attr('id', 'missions');
        $('.related-section').attr('id', 'related');
        $('.gallery-section').attr('id', 'gallery');
        
        // Tooltip nos POIs
        location.elements.mapPOIs.each(function() {
            const poi = $(this);
            const poiData = poi.data('poi');
            
            // Adicionar dados se necessário
            if (!poi.find('.poi-info').length) {
                // POI info já existe no HTML
            }
        });
    },

    handleMapControl: function(action) {
        const location = this;
        
        switch (action) {
            case 'zoom-in':
                location.zoomMap(1.2);
                break;
            case 'zoom-out':
                location.zoomMap(0.8);
                break;
            case 'toggle-layer':
                location.toggleMapLayer();
                break;
        }
        
        location.showNotification(`Mapa: ${action}`, 'info');
    },

    zoomMap: function(factor) {
        const mapImage = $('.map-image');
        const currentScale = parseFloat(mapImage.data('scale') || 1);
        const newScale = Math.max(0.5, Math.min(3, currentScale * factor));
        
        mapImage.css('transform', `scale(${newScale})`);
        mapImage.data('scale', newScale);
    },

    toggleMapLayer: function() {
        const mapContainer = this.elements.mapContainer;
        mapContainer.toggleClass('satellite-view');
        
        // Aqui você poderia trocar a imagem do mapa
        const currentSrc = $('.map-image').attr('src');
        // Implementar troca de camadas se necessário
    },

    toggleMapFullscreen: function() {
        const location = this;
        const mapContainer = location.elements.mapContainer;
        
        location.config.mapFullscreen = !location.config.mapFullscreen;
        
        if (location.config.mapFullscreen) {
            // Enter fullscreen
            mapContainer.addClass('fullscreen');
            $('body').addClass('map-fullscreen');
            
            // Add exit button
            if (!mapContainer.find('.fullscreen-exit-btn').length) {
                const exitBtn = $(`
                    <button class="fullscreen-exit-btn">
                        <i class="fa fa-times"></i>
                        Sair da Tela Cheia (ESC)
                    </button>
                `);
                
                exitBtn.on('click', function() {
                    location.toggleMapFullscreen();
                });
                
                mapContainer.append(exitBtn);
            }
            
            // Update fullscreen button text
            location.elements.fullscreenBtn.html('<i class="fa fa-compress"></i> Sair da Tela Cheia');
            
            // Disable scrolling
            $('html, body').css('overflow', 'hidden');
            
            // Show notification
            location.showNotification('Pressione ESC para sair da tela cheia', 'info');
            
        } else {
            // Exit fullscreen
            mapContainer.removeClass('fullscreen');
            $('body').removeClass('map-fullscreen');
            
            // Remove exit button
            mapContainer.find('.fullscreen-exit-btn').remove();
            
            // Restore fullscreen button text
            location.elements.fullscreenBtn.html('<i class="fa fa-expand"></i> Tela Cheia');
            
            // Re-enable scrolling
            $('html, body').css('overflow', 'auto');
        }
    },

    selectPOI: function(poi) {
        const location = this;
        
        // Remove active de outros POIs
        location.elements.mapPOIs.removeClass('active');
        
        // Adiciona active ao POI clicado
        poi.addClass('active');
        
        location.config.activePOI = poi.data('poi');
        
        // Mostrar informações do POI
        const poiName = poi.find('.poi-info h5').text();
        location.showNotification(`Ponto selecionado: ${poiName}`, 'success');
        
        // Scroll para o POI se necessário
        poi[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    },

    // Sistema de galeria
    setupGallery: function() {
        const location = this;
        
        // Criar lightbox se não existir
        if (!$('#lightbox').length) {
            const lightbox = $(`
                <div id="lightbox" class="lightbox-overlay">
                    <div class="lightbox-container">
                        <button class="lightbox-close">
                            <i class="fa fa-times"></i>
                        </button>
                        <img class="lightbox-image" src="" alt="">
                        <div class="lightbox-caption"></div>
                    </div>
                </div>
            `);
            
            $('body').append(lightbox);
            
            // Eventos do lightbox
            $('#lightbox').on('click', function(e) {
                if (e.target === this) {
                    location.closeLightbox();
                }
            });
            
            $('.lightbox-close').on('click', function() {
                location.closeLightbox();
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    location.closeLightbox();
                }
            });
        }
    },

    openLightbox: function(item) {
        const img = item.find('img');
        const src = img.attr('src');
        const alt = img.attr('alt');
        const type = item.find('.gallery-type').text();
        
        $('#lightbox .lightbox-image').attr('src', src).attr('alt', alt);
        $('#lightbox .lightbox-caption').text(`${alt} - ${type}`);
        $('#lightbox').addClass('show');
        
        $('body').css('overflow', 'hidden');
    },

    closeLightbox: function() {
        $('#lightbox').removeClass('show');
        $('body').css('overflow', 'auto');
    },

    // Sistema de favoritos
    toggleFavorite: function() {
        const location = this;
        const locationId = location.config.currentLocation;
        const index = location.config.favorites.indexOf(locationId);
        
        if (index > -1) {
            location.config.favorites.splice(index, 1);
            location.elements.favoriteBtn.removeClass('favorited');
            location.elements.favoriteBtn.html('<i class="fa fa-heart"></i> Favoritar');
            location.showNotification('Removido dos favoritos', 'info');
        } else {
            location.config.favorites.push(locationId);
            location.elements.favoriteBtn.addClass('favorited');
            location.elements.favoriteBtn.html('<i class="fa fa-heart"></i> Favoritado');
            location.showNotification('Adicionado aos favoritos!', 'success');
        }
        
        // Salvar no localStorage
        localStorage.setItem('location-favorites', JSON.stringify(location.config.favorites));
    },

    // Compartilhamento
    shareLocation: function() {
        const location = this;
        const url = window.location.href;
        const title = $('h1').text();
        const text = `Confira "${title}" no HUB Leonida!`;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                text: text,
                url: url
            });
        } else {
            // Fallback: copiar para clipboard
            navigator.clipboard.writeText(url).then(() => {
                location.showNotification('Link copiado para a área de transferência!', 'success');
            }).catch(() => {
                location.showNotification('Erro ao copiar link', 'error');
            });
        }
    },

    // Modal de edição
    openEditModal: function() {
        const location = this;
        
        // Criar modal se não existir
        if (!$('#edit-modal').length) {
            const modal = $(`
                <div id="edit-modal" class="edit-modal-overlay">
                    <div class="edit-modal">
                        <div class="modal-header">
                            <h3>
                                <i class="fa fa-edit"></i>
                                Sugerir Edição
                            </h3>
                            <button class="modal-close">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form class="edit-form">
                                <div class="form-group">
                                    <label>Tipo de Edição</label>
                                    <select name="edit-type" required>
                                        <option value="">Selecione...</option>
                                        <option value="info">Correção de Informação</option>
                                        <option value="image">Nova Imagem</option>
                                        <option value="poi">Novo Ponto de Interesse</option>
                                        <option value="other">Outro</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Descrição da Edição</label>
                                    <textarea name="description" placeholder="Descreva sua sugestão..." required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Fonte (opcional)</label>
                                    <input type="url" name="source" placeholder="Link para fonte da informação">
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Enviar Sugestão</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            // Eventos do modal
            $('#edit-modal').on('click', function(e) {
                if (e.target === this) {
                    location.closeEditModal();
                }
            });
            
            $('.modal-close').on('click', function() {
                location.closeEditModal();
            });
            
            $('.edit-form').on('submit', function(e) {
                e.preventDefault();
                location.submitEdit($(this));
            });
        }
        
        $('#edit-modal').addClass('show');
    },

    closeEditModal: function() {
        $('#edit-modal').removeClass('show');
    },

    submitEdit: function(form) {
        const location = this;
        const formData = {
            type: form.find('[name="edit-type"]').val(),
            description: form.find('[name="description"]').val(),
            source: form.find('[name="source"]').val(),
            location: location.config.currentLocation,
            timestamp: new Date().toISOString()
        };
        
        // Simular envio
        console.log('Sugestão de edição:', formData);
        
        location.closeEditModal();
        location.showNotification('Sugestão enviada com sucesso! Obrigado pela contribuição.', 'success');
        
        // Reset form
        form[0].reset();
    },

    // Carregar dados da localização
    loadLocationData: function() {
        const location = this;
        const locationId = location.config.currentLocation;
        
        // Verificar se está nos favoritos
        if (location.config.favorites.includes(locationId)) {
            location.elements.favoriteBtn.addClass('favorited');
            location.elements.favoriteBtn.html('<i class="fa fa-heart"></i> Favoritado');
        }
        
        // Animar contadores
        location.animateCounters();
        
        // Animar barras de features
        setTimeout(() => {
            $('.feature-fill').each(function() {
                const fill = $(this);
                const width = fill.css('width');
                fill.css('width', '0').animate({ width: width }, 1000);
            });
        }, 500);
    },

    animateCounters: function() {
        $('.stat-number').each(function() {
            const counter = $(this);
            const target = counter.text().replace(/[^\d.,]/g, '');
            const isDecimal = target.includes('.');
            const numericTarget = parseFloat(target.replace(',', '.'));
            
            if (!isNaN(numericTarget)) {
                counter.text('0');
                
                $({ count: 0 }).animate({ count: numericTarget }, {
                    duration: 2000,
                    step: function() {
                        const current = isDecimal ? this.count.toFixed(1) : Math.floor(this.count);
                        counter.text(current + (target.includes('k') ? 'k' : ''));
                    },
                    complete: function() {
                        counter.text(target);
                    }
                });
            }
        });
    },

    // Sistema de notificações
    showNotification: function(message, type = 'info') {
        const notification = $(`
            <div class="notification notification-${type}">
                <div class="notification-content">
                    <i class="fa ${this.getNotificationIcon(type)}"></i>
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

    getNotificationIcon: function(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || 'fa-info-circle';
    }
};

// Inicialização quando DOM estiver pronto
$(document).ready(function() {
    // Inicializar apenas se estivermos na página de localização
    if ($('.location-container').length > 0) {
        LeonidaLocation.init();
    }
});

// Prevent conflicts with other pages
if (typeof window.LeonidaBrasil === 'undefined') {
    window.LeonidaBrasil = {};
}
window.LeonidaBrasil.Location = LeonidaLocation;