/**
 * Leonida Brasil - Principal.js
 * Sistema de interatividade integrado
 * Desenvolvido por Equipe Leonida
 */

$(document).ready(function() {
    'use strict';
    
    // ========================================
    // CONFIGURAÇÕES GLOBAIS
    // ========================================
    
    const CONFIG = {
        carousel: {
            autoPlay: true,
            interval: 5000,
            animationSpeed: 600
        },
        animations: {
            enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            duration: 300
        },
        notifications: {
            duration: 3000,
            position: 'top-right'
        },
        api: {
            baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, ''),
            endpoints: {
                news: '/api/news.php',
                forum: '/api/forum.php',
                login: '/api/auth.php',
                search: '/api/search.php'
            }
        }
    };
    
    const SITE_DATA = window.leonidaData || {};
    
    // ========================================
    // SISTEMA DE API
    // ========================================
    
    class ApiClient {
        static async request(endpoint, options = {}) {
            const url = CONFIG.api.baseUrl + endpoint;
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            try {
                const response = await fetch(url, { ...defaultOptions, ...options });
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('API Error:', error);
                NotificationSystem.show('Erro de conexão', 'error');
                return { success: false, error: error.message };
            }
        }
        
        static async get(endpoint, params = {}) {
            const url = new URL(CONFIG.api.baseUrl + endpoint);
            Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
            return this.request(url.pathname + url.search);
        }
        
        static async post(endpoint, data = {}) {
            return this.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }
    }
    
    // ========================================
    // SISTEMA DE PAGINAÇÃO 
    // ========================================
    
    class PaginationSystem {
        constructor() {
            this.currentPage = {
                news: 1,
                forum: 1
            };
            this.isLoading = false;
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            // Paginação de notícias
            $(document).on('click', '.news-section .pagination-btn', (e) => {
                e.preventDefault();
                this.handleNewsPage(e.target);
            });
            
            // Paginação do fórum
            $(document).on('click', '.forum-section .pagination-btn', (e) => {
                e.preventDefault();
                this.handleForumPage(e.target);
            });
        }
        
        async handleNewsPage(element) {
            if (this.isLoading) return;
            
            const $btn = $(element);
            if ($btn.hasClass('disabled') || $btn.hasClass('active')) return;
            
            let page = parseInt($btn.text());
            
            // Botões de navegação
            if ($btn.find('.fa-angle-left').length) {
                page = Math.max(1, this.currentPage.news - 1);
            } else if ($btn.find('.fa-angle-right').length) {
                page = this.currentPage.news + 1;
            }
            
            await this.loadNewsPage(page);
        }
        
        async handleForumPage(element) {
            if (this.isLoading) return;
            
            const $btn = $(element);
            if ($btn.hasClass('disabled') || $btn.hasClass('active')) return;
            
            let page = parseInt($btn.text());
            
            // Botões de navegação
            if ($btn.find('.fa-angle-left').length) {
                page = Math.max(1, this.currentPage.forum - 1);
            } else if ($btn.find('.fa-angle-right').length) {
                page = this.currentPage.forum + 1;
            }
            
            await this.loadForumPage(page);
        }
        
        async loadNewsPage(page) {
            this.isLoading = true;
            this.showNewsLoading(true);
            
            try {
                const response = await ApiClient.get(CONFIG.api.endpoints.news, { 
                    page: page,
                    per_page: 6 
                });
                
                if (response.success) {
                    this.updateNewsContent(response.data);
                    this.updateNewsPagination(page, response.meta);
                    this.currentPage.news = page;
                    NotificationSystem.show(`Página ${page} carregada!`, 'success');
                } else {
                    NotificationSystem.show('Erro ao carregar notícias', 'error');
                }
            } catch (error) {
                console.error('Error loading news:', error);
                NotificationSystem.show('Erro ao carregar página', 'error');
            }
            
            this.showNewsLoading(false);
            this.isLoading = false;
        }
        
        async loadForumPage(page) {
            this.isLoading = true;
            this.showForumLoading(true);
            
            try {
                const response = await ApiClient.get(CONFIG.api.endpoints.forum, { 
                    page: page,
                    per_page: 8 
                });
                
                if (response.success) {
                    this.updateForumContent(response.data);
                    this.updateForumPagination(page, response.meta);
                    this.currentPage.forum = page;
                    NotificationSystem.show(`Página ${page} carregada!`, 'success');
                } else {
                    NotificationSystem.show('Erro ao carregar tópicos', 'error');
                }
            } catch (error) {
                console.error('Error loading forum:', error);
                NotificationSystem.show('Erro ao carregar página', 'error');
            }
            
            this.showForumLoading(false);
            this.isLoading = false;
        }
        
        showNewsLoading(show) {
            const $newsGrid = $('.news-grid');
            if (show) {
                $newsGrid.addClass('loading').css('opacity', '0.6');
            } else {
                $newsGrid.removeClass('loading').css('opacity', '1');
            }
        }
        
        showForumLoading(show) {
            const $forumList = $('.forum-list');
            if (show) {
                $forumList.addClass('loading').css('opacity', '0.6');
            } else {
                $forumList.removeClass('loading').css('opacity', '1');
            }
        }
        
        updateNewsContent(newsData) {
            const $newsGrid = $('.news-grid');
            let html = '';
            
            newsData.forEach((news, index) => {
                const categoryClass = news.category ? news.category.toLowerCase() : 'general';
                const isFirstFeatured = index === 0;
                
                html += `
                    <a href="/noticia/${news.slug}"><article class="news-item ${isFirstFeatured ? 'featured' : ''}" data-id="${news.id}">
                        <div class="news-thumb" style="background-image: url(${news.featured_image || 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg'}); object-fit: cover; background-size: cover; background-position: center;">
                            <div class="news-badge ${isFirstFeatured ? 'featured' : categoryClass}">
                                ${isFirstFeatured ? 'Destaque' : this.formatCategory(news.category)}
                            </div>
                            <div class="news-stats">
                                <span><i class="fa fa-comments"></i>${news.comments_count || 0}</span>
                                <span><i class="fa fa-heart"></i>${news.likes || 0}</span>
                            </div>
                        </div>
                        <div class="news-content">
                            <h3>${news.title}</h3>
                            <div class="news-meta">
                                <span class="author">
                                    <i class="fa fa-user"></i>
                                    ${news.author_name || news.username}
                                </span>
                                <span class="date">${this.formatDate(news.created_at)}</span>
                            </div>
                            <div class="news-footer">
                                <span><i class="fa fa-eye"></i>${this.formatNumber(news.views)}</span>
                                <span><i class="fa fa-comments"></i>${news.comments_count || 0}</span>
                            </div>
                        </div>
                    </article></a>
                `;
            });
            
            $newsGrid.html(html);
            this.reinitNewsInteractions();
        }
        
        updateForumContent(forumData) {
            const $forumList = $('.forum-list');
            let html = '';
            
            forumData.forEach(topic => {
                const isPinned = topic.status === 'pinned';
                const avatarUrl = topic.avatar || 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                
                html += `
                    <a href="${topic.url}"><div class="forum-item ${isPinned ? 'pinned' : ''}" data-id="${topic.id}">
                        <div class="forum-avatar" style="background-image: url(${avatarUrl}); object-fit: cover; background-size: cover; background-position: center;">
                            ${isPinned ? '<i class="fa fa-thumbtack"></i>' : ''}
                        </div>
                        <div class="forum-content">
                            <h4>${topic.title}</h4>
                            <div class="forum-meta">
                                <span class="author">
                                    ${isPinned ? '<i class="fa fa-star"></i>' : ''}
                                    ${topic.author_name || topic.username}
                                </span>
                                <span class="time">${this.formatDate(topic.created_at)}</span>
                                <div class="forum-stats">
                                    <span><i class="fa fa-comments"></i>${topic.replies_count || 0}</span>
                                    <span><i class="fa fa-eye"></i>${this.formatNumber(topic.views)}</span>
                                </div>
                            </div>
                        </div>
                    </div></a>
                `;
            });
            
            $forumList.html(html);
            this.reinitForumInteractions();
        }
        
        updateNewsPagination(currentPage, meta) {
            this.updatePaginationButtons('.news-section .pagination', currentPage, meta);
        }
        
        updateForumPagination(currentPage, meta) {
            this.updatePaginationButtons('.forum-section .pagination', currentPage, meta);
        }
        
        updatePaginationButtons(selector, currentPage, meta) {
            const $pagination = $(selector);
            const totalPages = meta.total_pages || 8;
            let html = '';
            
            // Botão anterior
            html += `<a href="#" class="pagination-btn ${currentPage <= 1 ? 'disabled' : ''}">
                        <i class="fa fa-angle-left"></i>
                     </a>`;
            
            // Páginas numeradas
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<a href="#" class="pagination-btn ${i === currentPage ? 'active' : ''}">${i}</a>`;
            }
            
            // Reticências e última página se necessário
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="pagination-info">...</span>';
                }
                html += `<a href="#" class="pagination-btn">${totalPages}</a>`;
            }
            
            // Botão próximo
            html += `<a href="#" class="pagination-btn ${currentPage >= totalPages ? 'disabled' : ''}">
                        <i class="fa fa-angle-right"></i>
                     </a>`;
            
            $pagination.html(html);
        }
        
        formatCategory(category) {
            const categories = {
                'trailers': 'Trailers',
                'analysis': 'Análises',
                'theories': 'Teorias',
                'maps': 'Mapas',
                'characters': 'Personagens'
            };
            return categories[category] || category;
        }
        
        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (minutes < 60) return `há ${minutes} min`;
            if (hours < 24) return `há ${hours}h`;
            if (days < 30) return `há ${days}d`;
            
            return date.toLocaleDateString('pt-BR');
        }
        
        formatNumber(number) {
            if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'k';
            }
            return number;
        }
        
        reinitNewsInteractions() {
            // Reinicializar eventos após atualização do conteúdo
            $('.news-item').off('click').on('click', function() {
            });
            
            $('.news-item').hover(
                function() { $(this).find('.news-stats').fadeIn(200); },
                function() { $(this).find('.news-stats').fadeOut(150); }
            );
        }
        
        reinitForumInteractions() {
            $('.forum-item').off('click').on('click', function() {
               
            });
        }
    }
    
    // ========================================
    // SISTEMA DE LOGIN INTEGRADO
    // ========================================
    
    class LoginSystem {
        constructor() {
            this.$loginForm = $('.login-form');
            this.$inputs = this.$loginForm.find('input');
            this.$submitBtn = this.$loginForm.find('.btn-login');
            this.$registerBtn = $('.btn-register');
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initValidation();
            this.checkExistingSession();
        }
        
        checkExistingSession() {
            // Verificar se usuário já está logado
            if (SITE_DATA.isLoggedIn && SITE_DATA.currentUser) {
                this.displayLoggedInUser(SITE_DATA.currentUser);
            }
        }
        
        bindEvents() {
            // Submit do formulário
            this.$loginForm.on('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
            
            // Botão de registro
            this.$registerBtn.on('click', (e) => {
                e.preventDefault();
                this.showRegisterModal();
            });
            
            // Validação em tempo real
            this.$inputs.on('input', () => {
                this.validateForm();
            });
        }
        
        initValidation() {
            this.validators = {
                username: (value) => value.length >= 3,
                password: (value) => value.length >= 6
            };
        }
        
        validateForm() {
            const formData = this.getFormData();
            const isValid = Object.keys(this.validators).every(field => 
                this.validators[field](formData[field] || '')
            );
            
            this.$submitBtn.prop('disabled', !isValid);
            return isValid;
        }
        
        getFormData() {
            return {
                username: this.$loginForm.find('input[type="text"]').val(),
                password: this.$loginForm.find('input[type="password"]').val(),
                remember: this.$loginForm.find('input[type="checkbox"]').is(':checked')
            };
        }
        
        async handleLogin() {
            if (!this.validateForm()) {
                NotificationSystem.show('Preencha todos os campos corretamente!', 'error');
                return;
            }
            
            const formData = this.getFormData();
            
            // Desabilitar formulário durante login
            this.$inputs.prop('disabled', true);
            this.$submitBtn.prop('disabled', true).text('Logando...');
            
            try {
                const response = await ApiClient.post(CONFIG.api.endpoints.login, formData);
                if (response.success) {
                    NotificationSystem.show(`Bem-vindo, ${response.data.display_name}!`, 'success');
                    this.loginSuccess(response.data);
                } else {
                    NotificationSystem.show(response.message || 'Usuário ou senha incorretos!', 'error');
                    this.loginError();
                }
            } catch (error) {
                console.error('Login error:', error);
                NotificationSystem.show('Erro de conexão', 'error');
                this.loginError();
            }
        }
        
        loginSuccess(user) {
            this.displayLoggedInUser(user);
        }
        
        displayLoggedInUser(user) {
            $('.login-widget').slideUp(400, function() {
                $(this).html(`
                    <div class="widget-header">
                        <i class="fa fa-user"></i>
                        Olá, ${user.display_name || user.username}!
                        <a href="#" class="btn btn-register btn-logout">SAIR</a>
                    </div>
                    <div class="widget-content">
                        <div class="user-info">
                            <div class="user-stats">
                                <div class="stat">
                                    <div class="stat-number">${SITE_DATA.siteStats?.total_users || 127}</div>
                                    <div class="stat-label">Membros</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">${user.likes || 45}</div>
                                    <div class="stat-label">Curtidas</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">${user.topics || 12}</div>
                                    <div class="stat-label">Tópicos</div>
                                </div>
                            </div>
                            <a href="${CONFIG.api.baseUrl}/perfil" class="btn btn-profile">Meu Perfil</a>
                            <a href="${CONFIG.api.baseUrl}/forum/criar-topico" class="btn btn-profile">Postar Tópico</a>
                            <a href="${CONFIG.api.baseUrl}/configuracoes" class="btn btn-profile">Configurações</a>
                        </div>
                    </div>
                `).slideDown(400);
            });
            
            // Reinicializar evento de logout
            setTimeout(() => {
                $('.btn-logout').on('click', (e) => {
                    e.preventDefault();
                    this.handleLogout();
                });
            }, 500);
        }
        
        loginError() {
            // Reabilitar formulário
            this.$inputs.prop('disabled', false);
            this.$submitBtn.prop('disabled', false).text('ENTRAR');
            
            // Shake animation no formulário
            this.$loginForm.addClass('shake');
            setTimeout(() => {
                this.$loginForm.removeClass('shake');
            }, 600);
        }
        
        async handleLogout() {
            try {
                await ApiClient.post(CONFIG.api.endpoints.login, { action: 'logout' });
                NotificationSystem.show('Você foi desconectado!', 'info');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } catch (error) {
                console.error('Logout error:', error);
                location.reload(); // Fallback
            }
        }
        
        showRegisterModal() {
            window.location.href = `${CONFIG.api.baseUrl}/registro`;
        }
    }
    
    // ========================================
    // SISTEMA DE BUSCA INTEGRADO 
    // ========================================
    
    class SearchSystem {
        constructor() {
            this.$searchInput = $('.search-input');
            this.$searchBtn = $('.search-btn');
            this.searchHistory = this.getSearchHistory();
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initAutoComplete();
        }
        
        bindEvents() {
            // Busca ao pressionar Enter
            this.$searchInput.on('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch();
                }
            });
            
            // Busca ao clicar no botão
            this.$searchBtn.on('click', () => {
                this.performSearch();
            });
            
            // Limpar busca com Escape
            this.$searchInput.on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.clearSearch();
                }
            });
        }
        
        initAutoComplete() {
            // Sugestões baseadas nos dados do site
            this.suggestions = [
                'GTA 6 trailer',
                'Jason e Lucia',
                'Mapa Vice City',
                'Data de lançamento',
                'Teorias GTA 6',
                'Leonida mapas',
                'Personagens',
                'Veículos',
                'Easter eggs'
            ];
        }
        
        async performSearch() {
            const query = this.$searchInput.val().trim();
            
            if (!query) {
                NotificationSystem.show('Digite algo para buscar!', 'warning');
                return;
            }
            
            this.addToHistory(query);
            
            // Redirecionar para página de busca
            window.location.href = `${CONFIG.api.baseUrl}/busca/${encodeURIComponent(query)}`;
        }
        
        clearSearch() {
            this.$searchInput.val('').focus();
        }
        
        addToHistory(query) {
            if (!this.searchHistory.includes(query)) {
                this.searchHistory.unshift(query);
                if (this.searchHistory.length > 10) {
                    this.searchHistory.pop();
                }
                this.saveSearchHistory();
            }
        }
        
        getSearchHistory() {
            try {
                return JSON.parse(localStorage.getItem('leonida_search_history') || '[]');
            } catch {
                return [];
            }
        }
        
        saveSearchHistory() {
            try {
                localStorage.setItem('leonida_search_history', JSON.stringify(this.searchHistory));
            } catch {
                // Storage não disponível
            }
        }
    }
    
    // ========================================
    // CAROUSEL DE NOTÍCIAS
    // ========================================
    
    class NewsCarousel {
        constructor() {
            this.$carousel = $('.news-carousel');
            this.$slides = $('.slide');
            this.$indicators = $('.indicator');
            this.currentSlide = 0;
            this.totalSlides = this.$slides.length;
            this.autoPlayTimer = null;
            
            this.init();
        }
        
        init() {
            if (this.totalSlides <= 1) return;
            
            this.bindEvents();
            this.startAutoPlay();
            
            // Pausar autoplay no hover
            this.$carousel.hover(
                () => this.stopAutoPlay(),
                () => this.startAutoPlay()
            );
        }
        
        bindEvents() {
            // Clique nos indicadores
            this.$indicators.on('click', (e) => {
                const index = $(e.currentTarget).index();
                this.goToSlide(index);
            });
            
            // Controles por teclado
            $(document).on('keydown', (e) => {
                if (!this.$carousel.is(':hover')) return;
                
                if (e.key === 'ArrowLeft') {
                    this.previousSlide();
                    e.preventDefault();
                } else if (e.key === 'ArrowRight') {
                    this.nextSlide();
                    e.preventDefault();
                }
            });
            
            // Touch/Swipe para mobile
            this.initTouchEvents();
        }
        
        initTouchEvents() {
            let startX = 0;
            let endX = 0;
            
            this.$carousel[0].addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            }, { passive: true });
            
            this.$carousel[0].addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                this.handleSwipe(startX, endX);
            }, { passive: true });
        }
        
        handleSwipe(startX, endX) {
            const diff = startX - endX;
            const threshold = 50;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.previousSlide();
                }
            }
        }
        
        goToSlide(index) {
            if (index === this.currentSlide) return;
            
            this.$slides.removeClass('active').eq(index).addClass('active');
            this.$indicators.removeClass('active').eq(index).addClass('active');
            
            this.currentSlide = index;
            this.restartAutoPlay();
        }
        
        nextSlide() {
            const nextIndex = (this.currentSlide + 1) % this.totalSlides;
            this.goToSlide(nextIndex);
        }
        
        previousSlide() {
            const prevIndex = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            this.goToSlide(prevIndex);
        }
        
        startAutoPlay() {
            if (!CONFIG.carousel.autoPlay) return;
            
            this.autoPlayTimer = setInterval(() => {
                this.nextSlide();
            }, CONFIG.carousel.interval);
        }
        
        stopAutoPlay() {
            if (this.autoPlayTimer) {
                clearInterval(this.autoPlayTimer);
                this.autoPlayTimer = null;
            }
        }
        
        restartAutoPlay() {
            this.stopAutoPlay();
            this.startAutoPlay();
        }
    }
    
    // ========================================
    // INTERAÇÕES DE CONTEÚDO 
    // ========================================
    
    class ContentInteractions {
        constructor() {
            this.init();
        }
        
        init() {
            this.initWidgetInteractions();
            this.initPlayerWidget();
        }
        
        
        initWidgetInteractions() {
            // Ranking interactions
            $('.rank-item').on('click', function() {
                const username = $(this).find('.rank-user').text();
                window.location.href = `${CONFIG.api.baseUrl}/perfil/${username}`;
            });
            
            // Featured user profile
            $('.btn-profile').on('click', function(e) {
                if ($(this).attr('href') === '#') {
                    e.preventDefault();
                    NotificationSystem.show('Carregando perfil do usuário...', 'info');
                }
            });
        }
        
        initPlayerWidget() {
            // Player buttons - navegação real
            $('.btn-player').on('click', function(e) {
                e.preventDefault();
                const section = $(this).find('span').text();
                
                $(this).addClass('active');
                setTimeout(() => {
                    $(this).removeClass('active');
                }, 200);
                
                // Navegação baseada no tipo de botão
                if ($(this).hasClass('radio')) {
                    window.location.href = `${CONFIG.api.baseUrl}/radio`;
                } else if ($(this).hasClass('forum')) {
                    window.location.href = `${CONFIG.api.baseUrl}/forum`;
                } else if ($(this).hasClass('hub')) {
                    window.location.href = `${CONFIG.api.baseUrl}/hub`;
                } else if ($(this).hasClass('gallery')) {
                    window.location.href = `${CONFIG.api.baseUrl}/galeria`;
                } else {
                    NotificationSystem.show(`Navegando para: ${section}`, 'info');
                }
            });
            
            // Like button
            $('.like-btn').on('click', function() {
                $(this).toggleClass('liked');
                const isLiked = $(this).hasClass('liked');
                
                if (isLiked) {
                    $(this).html('💖');
                    NotificationSystem.show('Curtiu a música!', 'success');
                } else {
                    $(this).html('♥');
                }
            });
            
            // Volume control simulation
            $('.volume-control').on('click', function() {
                NotificationSystem.show('Controle de volume - Em breve!', 'info');
            });
        }
    }
    
    // ========================================
    // SISTEMA DE NAVEGAÇÃO
    // ========================================
    
    class Navigation {
        constructor() {
            this.$navItems = $('.nav-item');
            this.$dropdowns = $('.dropdown');
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initMobileMenu();
        }
        
        bindEvents() {
            // Hover nos itens de navegação (desktop)
            this.$navItems.on('mouseenter', function() {
                const $dropdown = $(this).find('.dropdown');
                if ($dropdown.length) {
                    $dropdown.stop().fadeIn(200);
                }
            }).on('mouseleave', function() {
                const $dropdown = $(this).find('.dropdown');
                if ($dropdown.length) {
                    $dropdown.stop().fadeOut(150);
                }
            });
            
           
            // Fechar dropdowns ao clicar fora
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.nav-item').length) {
                    this.$dropdowns.hide();
                }
            });
        }
        
        initMobileMenu() {
            // Para implementação futura do menu mobile
        }
        
        static showComingSoon(section) {
            NotificationSystem.show(`${section} - Em breve!`, 'info');
        }
    }
    
    // ========================================
    // SISTEMA DE NOTIFICAÇÕES 
    // ========================================
    
    class NotificationSystem {
        static init() {
            // Criar container se não existir
            if (!$('#notification-container').length) {
                $('body').append('<div id="notification-container"></div>');
            }
        }
        
        static show(message, type = 'info', duration = CONFIG.notifications.duration) {
            const $container = $('#notification-container');
            const id = 'notification-' + Date.now();
            
            const $notification = $(`
                <div id="${id}" class="notification notification-${type}">
                    <div class="notification-content">
                        <i class="fa ${this.getIcon(type)}"></i>
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            `);
            
            $container.append($notification);
            
            // Animação de entrada
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            // Auto-hide
            if (duration > 0) {
                setTimeout(() => {
                    this.hide(id);
                }, duration);
            }
            
            // Botão de fechar
            $notification.find('.notification-close').on('click', () => {
                this.hide(id);
            });
            
            return id;
        }
        
        static hide(id) {
            const $notification = $(`#${id}`);
            $notification.removeClass('show');
            
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }
        
        static getIcon(type) {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            return icons[type] || icons.info;
        }
    }
    
    // ========================================
    // EFEITOS VISUAIS E ANIMAÇÕES
    // ========================================
    
    class VisualEffects {
        constructor() {
            this.init();
        }
        
        init() {
            this.initScrollAnimations();
            this.initHoverEffects();
            this.initLoadingStates();
            this.initCounters();
        }
        
        initScrollAnimations() {
            // Fade in elements on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-visible');
                    }
                });
            });
            
            $('.widget, .news-item, .forum-item').each(function() {
                this.classList.add('fade-in');
                observer.observe(this);
            });
        }
        
        initHoverEffects() {
            // Parallax effect nos cards
            $('.news-item, .widget').on('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                $(this).css('transform', `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`);
            }).on('mouseleave', function() {
                $(this).css('transform', '');
            });
        }
        
        initLoadingStates() {
            // Loading animation para elementos
            $(document).on('click', '[data-loading]', function() {
                const $this = $(this);
                const originalText = $this.text();
                
                $this.addClass('loading').prop('disabled', true);
                
                setTimeout(() => {
                    $this.removeClass('loading').prop('disabled', false);
                }, 2000);
            });
        }
        
        initCounters() {
            // Animar números baseados nos dados reais do site
            const onlineCount = SITE_DATA.onlineCount || 1247;
            this.animateCounter('.listener-count .count', onlineCount);
            this.animateCounter('.rank-points', null); // Auto-detect value
            this.animateCounter('.stat-number', null); // Stats dos usuários
        }
        
        animateCounter(selector, targetValue = null) {
            $(selector).each(function() {
                const $this = $(this);
                const target = targetValue || parseInt($this.text().replace(/\D/g, '')) || 0;
                const duration = 2000;
                const steps = 60;
                const stepValue = target / steps;
                let current = 0;
                
                const timer = setInterval(() => {
                    current += stepValue;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    $this.text(Math.floor(current).toLocaleString());
                }, duration / steps);
            });
        }
    }
    
    // ========================================
    // SISTEMA DE FILTROS
    // ========================================
    
    class FilterSystem {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            // Filtro de categoria nas notícias
            $('.filter-select').on('change', async (e) => {
                const category = $(e.target).val();
                if (category && category !== 'Categoria') {
                    await this.filterNews(category.toLowerCase());
                } else {
                    // Recarregar todas as notícias
                    window.paginationSystem.loadNewsPage(1);
                }
            });
            
            // Filtro de assuntos quentes
            $('.topic-tag').on('click', function(e) {
                e.preventDefault();
                const tag = $(this).text().replace('#', '');
                window.location.href = `${CONFIG.api.baseUrl}/busca/${encodeURIComponent(tag)}`;
            });
        }
        
        async filterNews(category) {
            try {
                const response = await ApiClient.get(CONFIG.api.endpoints.news, { 
                    category: category,
                    page: 1,
                    per_page: 6 
                });
                
                if (response.success) {
                    window.paginationSystem.updateNewsContent(response.data);
                    window.paginationSystem.updateNewsPagination(1, response.meta);
                    NotificationSystem.show(`Filtrado por: ${category}`, 'info');
                } else {
                    NotificationSystem.show('Erro ao filtrar notícias', 'error');
                }
            } catch (error) {
                console.error('Filter error:', error);
                NotificationSystem.show('Erro ao aplicar filtro', 'error');
            }
        }
    }
    
    // ========================================
    // SISTEMA DE ESTATÍSTICAS DINÂMICAS
    // ========================================
    
    class StatsSystem {
        constructor() {
            this.updateInterval = null;
            this.init();
        }
        
        init() {
            this.updateOnlineCount();
            this.startPeriodicUpdates();
        }
        
        updateOnlineCount() {
            // Simular flutuação do contador online
            const baseCount = SITE_DATA.onlineCount || 1247;
            const variation = Math.floor(Math.random() * 20) - 10; // -10 a +10
            const newCount = Math.max(1, baseCount + variation);
            
            $('.listener-count .count').text(newCount.toLocaleString());
            $('.online-count .count-number').text(newCount.toLocaleString());
        }
        
        startPeriodicUpdates() {
            // Atualizar contador a cada 30 segundos
            this.updateInterval = setInterval(() => {
                this.updateOnlineCount();
            }, 30000);
        }
        
        destroy() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
        }
    }
    
    // ========================================
    // INICIALIZAÇÃO PRINCIPAL
    // ========================================
    
    // Aguardar carregamento completo
    $(window).on('load', function() {
        // Remover loading screen se existir
        $('.loading-screen').fadeOut(500);
    });
    
    // Inicializar todos os sistemas
    NotificationSystem.init();
    
    const newsCarousel = new NewsCarousel();
    const navigation = new Navigation();
    const searchSystem = new SearchSystem();
    const loginSystem = new LoginSystem();
    const contentInteractions = new ContentInteractions();
    const visualEffects = new VisualEffects();
    const paginationSystem = new PaginationSystem(); // NOVO
    const filterSystem = new FilterSystem(); // NOVO
    const statsSystem = new StatsSystem(); // NOVO
    
    // Expor sistemas globalmente para acesso
    window.paginationSystem = paginationSystem;
    window.filterSystem = filterSystem;
    
    // Mensagem de boas-vindas personalizada
    setTimeout(() => {
        const userName = SITE_DATA.currentUser?.display_name || SITE_DATA.currentUser?.username;
        if (userName) {
            NotificationSystem.show(`Bem-vindo de volta, ${userName}! 🌴`, 'success');
        } 
    }, 1000);
    

    window.LeonidaBrasil = {
        config: CONFIG,
        data: SITE_DATA,
        systems: {
            carousel: newsCarousel,
            navigation: navigation,
            search: searchSystem,
            login: loginSystem,
            pagination: paginationSystem,
            filters: filterSystem,
            stats: statsSystem
        },
        api: ApiClient,
        notify: NotificationSystem.show.bind(NotificationSystem)
    };
    
    
});
