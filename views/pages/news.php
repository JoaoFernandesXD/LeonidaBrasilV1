<!-- Main Content -->
<main class="news-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="page-title">
                <h1>
                    <i class="fa fa-newspaper"></i>
                    Notícias GTA VI
                </h1>
                <p>Todas as novidades, análises e descobertas sobre o universo de Leonida</p>
            </div>
            
            <div class="header-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_news ?? 247 ?></div>
                    <div class="stat-label">Notícias</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return strtotime($n['created_at']) > strtotime('-1 week'); })) : 15 ?></div>
                    <div class="stat-label">Esta Semana</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= isset($news_list) ? number_format(array_sum(array_column($news_list, 'views'))) : '1.2M' ?></div>
                    <div class="stat-label">Visualizações</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="news-controls">
        <div class="controls-left">
            <div class="category-filters">
                <button class="filter-btn <?= empty($current_category) ? 'active' : '' ?>" data-category="all">
                    <i class="fa fa-globe"></i>
                    Todas
                    <span class="count"><?= $total_news ?? 247 ?></span>
                </button>
                <button class="filter-btn <?= $current_category === 'trailers' ? 'active' : '' ?>" data-category="trailers">
                    <i class="fa fa-video"></i>
                    Trailers
                    <span class="count"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return $n['category'] === 'trailers'; })) : 23 ?></span>
                </button>
                <button class="filter-btn <?= $current_category === 'analysis' ? 'active' : '' ?>" data-category="analysis">
                    <i class="fa fa-chart-line"></i>
                    Análises
                    <span class="count"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return $n['category'] === 'analysis'; })) : 67 ?></span>
                </button>
                <button class="filter-btn <?= $current_category === 'theories' ? 'active' : '' ?>" data-category="theories">
                    <i class="fa fa-lightbulb"></i>
                    Teorias
                    <span class="count"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return $n['category'] === 'theories'; })) : 89 ?></span>
                </button>
                <button class="filter-btn <?= $current_category === 'maps' ? 'active' : '' ?>" data-category="maps">
                    <i class="fa fa-map"></i>
                    Mapas
                    <span class="count"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return $n['category'] === 'maps'; })) : 34 ?></span>
                </button>
                <button class="filter-btn <?= $current_category === 'characters' ? 'active' : '' ?>" data-category="characters">
                    <i class="fa fa-users"></i>
                    Personagens
                    <span class="count"><?= isset($news_list) ? count(array_filter($news_list, function($n) { return $n['category'] === 'characters'; })) : 45 ?></span>
                </button>
            </div>
        </div>
        
        <div class="controls-right">
            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" class="news-search" placeholder="Buscar notícias..." value="<?= htmlspecialchars($current_search ?? '') ?>">
                    <button class="clear-search" style="display: <?= !empty($current_search) ? 'block' : 'none' ?>;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="sort-container">
                <select class="sort-select">
                    <option value="recent">Mais Recentes</option>
                    <option value="popular">Mais Populares</option>
                    <option value="comments">Mais Comentados</option>
                    <option value="views">Mais Visualizados</option>
                </select>
            </div>
            
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Visualização em Grid">
                    <i class="fa fa-th"></i>
                </button>
                <button class="view-btn" data-view="list" title="Visualização em Lista">
                    <i class="fa fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Featured News Section -->
    <div class="featured-section">
        <div class="section-header">
            <h2>
                <i class="fa fa-star"></i>
                Destaques da Semana
            </h2>
        </div>
        
        <div class="featured-grid">
            <?php if (!empty($featured_news) && count($featured_news) > 0): ?>
                <?php $main_featured = $featured_news[0]; ?>
                <article class="featured-main">
                    <div class="news-thumb">
                        <img src="<?= htmlspecialchars($main_featured['featured_image'] ?? 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg') ?>" alt="<?= htmlspecialchars($main_featured['title']) ?>">
                        <div class="news-badge featured">Destaque</div>
                        <?php if (($main_featured['category'] ?? '') === 'trailers'): ?>
                            <div class="play-overlay">
                                <i class="fa fa-play"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="news-content">
                        <div class="news-category">
                            <span class="category-tag <?= strtolower($main_featured['category'] ?? 'analysis') ?>"><?= $main_featured['category_formatted'] ?? 'Análises' ?></span>
                        </div>
                        <h3><?= htmlspecialchars($main_featured['title']) ?></h3>
                        <p><?= htmlspecialchars($main_featured['excerpt'] ?? $main_featured['subtitle'] ?? '') ?></p>
                        <div class="news-meta">
                            <div class="author">
                                <img src="<?= htmlspecialchars($main_featured['avatar'] ?? 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg') ?>" alt="<?= htmlspecialchars($main_featured['author_name'] ?? 'Author') ?>">
                                <span><?= htmlspecialchars($main_featured['author_name'] ?? $main_featured['username'] ?? 'TheoryMaster') ?></span>
                            </div>
                            <div class="date"><?= $main_featured['time_ago'] ?? 'há 2 dias' ?></div>
                        </div>
                        <div class="news-stats">
                            <span><i class="fa fa-eye"></i> <?= $main_featured['formatted_views'] ?? '8.2k' ?></span>
                            <span><i class="fa fa-comments"></i> <?= $main_featured['comments_count'] ?? '234' ?></span>
                            <span><i class="fa fa-heart"></i> <?= number_format($main_featured['likes'] ?? 1100) ?></span>
                        </div>
                    </div>
                </article>

                <div class="featured-side">
                    <?php for ($i = 1; $i < min(3, count($featured_news)); $i++): ?>
                        <?php $featured = $featured_news[$i]; ?>
                        <article class="featured-item">
                            <div class="news-thumb">
                                <img src="<?= htmlspecialchars($featured['featured_image'] ?? 'https://www.gtavice.net/content/images/gta-vi-official-website-screenshot-vice-city.jpg') ?>" alt="<?= htmlspecialchars($featured['title']) ?>">
                                <div class="news-badge <?= strtolower($featured['category'] ?? 'analysis') ?>"><?= $featured['category_formatted'] ?? 'Análises' ?></div>
                            </div>
                            <div class="news-content">
                                <h4><?= htmlspecialchars($featured['title']) ?></h4>
                                <div class="news-meta">
                                    <span class="author"><?= htmlspecialchars($featured['author_name'] ?? $featured['username'] ?? 'Author') ?></span>
                                    <span class="date"><?= $featured['time_ago'] ?? 'há alguns dias' ?></span>
                                </div>
                                <div class="news-stats">
                                    <span><i class="fa fa-eye"></i> <?= $featured['formatted_views'] ?? number_format($featured['views'] ?? 6700) ?></span>
                                    <span><i class="fa fa-comments"></i> <?= $featured['comments_count'] ?? '189' ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endfor; ?>
                    
                    <?php if (count($featured_news) < 2): ?>
                        <!-- Fallback featured items -->
                        <article class="featured-item">
                            <div class="news-thumb">
                                <img src="https://www.gtavice.net/content/images/gta-vi-official-website-screenshot-vice-city.jpg" alt="Vice City Map">
                                <div class="news-badge mapas">Mapas</div>
                            </div>
                            <div class="news-content">
                                <h4>Mapa Colaborativo: Vice City Revelada</h4>
                                <div class="news-meta">
                                    <span class="author">MapExplorer</span>
                                    <span class="date">há 4 dias</span>
                                </div>
                                <div class="news-stats">
                                    <span><i class="fa fa-eye"></i> 6.7k</span>
                                    <span><i class="fa fa-comments"></i> 189</span>
                                </div>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Fallback featured section -->
                <article class="featured-main">
                    <div class="news-thumb">
                        <img src="https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg" alt="Trailer 2 Analysis">
                        <div class="news-badge featured">Destaque</div>
                        <div class="play-overlay">
                            <i class="fa fa-play"></i>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-category">
                            <span class="category-tag analises">Análises</span>
                        </div>
                        <h3>Análise Completa do Trailer 2: Novos Detalhes de Leonida</h3>
                        <p>Descubra todos os segredos revelados no segundo trailer oficial de GTA VI, incluindo novos personagens, localizações inéditas e easter eggs escondidos.</p>
                        <div class="news-meta">
                            <div class="author">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiNGRjAwN0YiLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSIzIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNiAxOEM2IDE1IDggMTMgMTIgMTNTMTggMTUgMTggMThINloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==" alt="TheoryMaster">
                                <span>TheoryMaster</span>
                            </div>
                            <div class="date">há 2 dias</div>
                        </div>
                        <div class="news-stats">
                            <span><i class="fa fa-eye"></i> 8.2k</span>
                            <span><i class="fa fa-comments"></i> 234</span>
                            <span><i class="fa fa-heart"></i> 1.1k</span>
                        </div>
                    </div>
                </article>

                <div class="featured-side">
                    <article class="featured-item">
                        <div class="news-thumb">
                            <img src="https://www.gtavice.net/content/images/gta-vi-official-website-screenshot-vice-city.jpg" alt="Vice City Map">
                            <div class="news-badge mapas">Mapas</div>
                        </div>
                        <div class="news-content">
                            <h4>Mapa Colaborativo: Vice City Revelada</h4>
                            <div class="news-meta">
                                <span class="author">MapExplorer</span>
                                <span class="date">há 4 dias</span>
                            </div>
                            <div class="news-stats">
                                <span><i class="fa fa-eye"></i> 6.7k</span>
                                <span><i class="fa fa-comments"></i> 189</span>
                            </div>
                        </div>
                    </article>

                    <article class="featured-item">
                        <div class="news-thumb">
                            <img src="https://www.gtavice.net/content/images/manni-l-perez-possible-lucia-voice-artist.jpeg" alt="Lucia Character">
                            <div class="news-badge personagens">Personagens</div>
                        </div>
                        <div class="news-content">
                            <h4>Lucia: Primeira Protagonista Feminina de GTA</h4>
                            <div class="news-meta">
                                <span class="author">CharAnalyst</span>
                                <span class="date">há 1 semana</span>
                            </div>
                            <div class="news-stats">
                                <span><i class="fa fa-eye"></i> 5.9k</span>
                                <span><i class="fa fa-comments"></i> 156</span>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main News Grid -->
    <div class="news-main-section">
        <div class="section-header">
            <h2>
                <i class="fa fa-clock"></i>
                Todas as Notícias
            </h2>
            <div class="results-info">
                <span class="results-count">Mostrando <strong><?= count($news_list ?? []) ?></strong> notícias</span>
            </div>
        </div>

        <div class="news-grid" id="newsGrid">
            <?php if (!empty($news_list)): ?>
                <?php foreach ($news_list as $news): ?>
                    <article class="news-item" data-category="<?= $news['category'] ?? 'analysis' ?>" data-news-id="<?= $news['id'] ?>">
                        <div class="news-thumb">
                            <?php if (!empty($news['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($news['featured_image']) ?>" alt="<?= htmlspecialchars($news['title']) ?>">
                            <?php else: ?>
                                <div class="gradient-bg" style="background: linear-gradient(135deg, #667eea, #764ba2);"></div>
                            <?php endif; ?>
                            <div class="news-badge <?= strtolower($news['category'] ?? 'analysis') ?>"><?= $news['category_formatted'] ?? ucfirst($news['category'] ?? 'Analysis') ?></div>
                            <div class="reading-time"><?= rand(4, 15) ?> min</div>
                            <?php if (($news['category'] ?? '') === 'trailers'): ?>
                                <div class="video-icon">
                                    <i class="fa fa-play"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($news['title']) ?></h3>
                            <p><?= htmlspecialchars($news['excerpt'] ?? truncate_text(strip_tags($news['content'] ?? ''), 120)) ?></p>
                            <div class="news-meta">
                                <div class="author">
                                    <img src="<?= htmlspecialchars($news['avatar'] ?? 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiMwMEJGRkYiLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSIzIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNiAxOEM2IDE1IDggMTMgMTIgMTNTMTggMTUgMTggMThINloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==') ?>" alt="<?= htmlspecialchars($news['author_name'] ?? $news['username'] ?? 'Author') ?>">
                                    <span><?= htmlspecialchars($news['author_name'] ?? $news['username'] ?? 'Author') ?></span>
                                </div>
                                <div class="date"><?= $news['time_ago'] ?? time_ago($news['created_at']) ?></div>
                            </div>
                            <div class="news-stats">
                                <span><i class="fa fa-eye"></i> <?= $news['formatted_views'] ?? number_format($news['views'] ?? rand(1000, 15000)) ?></span>
                                <span><i class="fa fa-comments"></i> <?= $news['comments_count'] ?? rand(10, 400) ?></span>
                                <span><i class="fa fa-heart"></i> <?= number_format($news['likes'] ?? rand(50, 2000)) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback news items quando não há dados do backend -->
                <article class="news-item" data-category="analises">
                    <div class="news-thumb">
                        <img src="https://www.gtavice.net/content/images/gta-vi-trailer-2-3184.jpg" alt="Easter Eggs Trailer">
                        <div class="news-badge analises">Análises</div>
                        <div class="reading-time">8 min</div>
                    </div>
                    <div class="news-content">
                        <h3>15 Easter Eggs Escondidos no Segundo Trailer</h3>
                        <p>Nossa equipe analisou frame por frame e encontrou referências incríveis aos jogos anteriores da série.</p>
                        <div class="news-meta">
                            <div class="author">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiMwMEJGRkYiLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSIzIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNiAxOEM2IDE1IDggMTMgMTIgMTNTMTggMTUgMTggMThINloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==" alt="EggHunter">
                                <span>EggHunter</span>
                            </div>
                            <div class="date">há 5 dias</div>
                        </div>
                        <div class="news-stats">
                            <span><i class="fa fa-eye"></i> 4.8k</span>
                            <span><i class="fa fa-comments"></i> 98</span>
                            <span><i class="fa fa-heart"></i> 542</span>
                        </div>
                    </div>
                </article>

                <article class="news-item" data-category="teorias">
                    <div class="news-thumb">
                        <img src="https://www.gtavice.net/content/images/gta-vi-satellite-map-by-randomamy.jpeg" alt="Release Date Theory">
                        <div class="news-badge teorias">Teorias</div>
                        <div class="reading-time">12 min</div>
                    </div>
                    <div class="news-content">
                        <h3>Data de Lançamento: Pistas no Código do Site</h3>
                        <p>Desenvolvedores podem ter deixado dicas sobre a data oficial escondidas no código da Rockstar.</p>
                        <div class="news-meta">
                            <div class="author">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiNGRUNBNTciLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSIzIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNiAxOEM2IDE1IDggMTMgMTIgMTNTMTggMTUgMTggMThINloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==" alt="DateSpeculator">
                                <span>DateSpeculator</span>
                            </div>
                            <div class="date">há 3 dias</div>
                        </div>
                        <div class="news-stats">
                            <span><i class="fa fa-eye"></i> 7.2k</span>
                            <span><i class="fa fa-comments"></i> 256</span>
                            <span><i class="fa fa-heart"></i> 891</span>
                        </div>
                    </div>
                </article>

                <article class="news-item" data-category="trailers">
                    <div class="news-thumb">
                        <img src="https://www.gtavice.net/content/images/vice-city-04.jpg" alt="Trailer Breakdown">
                        <div class="news-badge trailers">Trailers</div>
                        <div class="reading-time">15 min</div>
                        <div class="video-icon">
                            <i class="fa fa-play"></i>
                        </div>
                    </div>
                    <div class="news-content">
                        <h3>Breakdown Completo: Cada Segundo do Trailer 2</h3>
                        <p>Análise detalhada de todos os 150 segundos do novo trailer, com timestamps e explicações.</p>
                        <div class="news-meta">
                            <div class="author">
                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiM0OGRiZmIiLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSIzIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNiAxOEM2IDE1IDggMTMgMTIgMTNTMTggMTUgMTggMThINloiIGZpbGw9IndoaXRlIi8+Cjwvc3ZnPg==" alt="VideoAnalyst">
                                <span>VideoAnalyst</span>
                            </div>
                            <div class="date">há 1 semana</div>
                        </div>
                        <div class="news-stats">
                            <span><i class="fa fa-eye"></i> 12.4k</span>
                            <span><i class="fa fa-comments"></i> 387</span>
                            <span><i class="fa fa-heart"></i> 1.8k</span>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>

        <!-- Load More Section -->
        <div class="load-more-section">
            <button class="load-more-btn" <?= ($current_page ?? 1) >= ($total_pages ?? 1) ? 'style="display:none;"' : '' ?>>
                <i class="fa fa-plus"></i>
                Carregar Mais Notícias
                <span class="remaining-count">(<?= max(0, ($total_news ?? 238) - (($current_page ?? 1) * 6)) ?> restantes)</span>
            </button>
        </div>
    </div>

    <!-- Trending Topics Sidebar -->
    <aside class="trending-sidebar">
        <div class="trending-widget">
            <div class="widget-header">
                <h3>
                    <i class="fa fa-fire"></i>
                    Trending Topics
                </h3>
            </div>
            <div class="widget-content">
                <div class="trending-list">
                    <div class="trending-item">
                        <div class="trend-rank">1</div>
                        <div class="trend-content">
                            <span class="trend-tag">#GTA6Trailer2</span>
                            <span class="trend-count">15.2k posts</span>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trend-rank">2</div>
                        <div class="trend-content">
                            <span class="trend-tag">#JasonLucia</span>
                            <span class="trend-count">8.7k posts</span>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trend-rank">3</div>
                        <div class="trend-content">
                            <span class="trend-tag">#ViceCity2025</span>
                            <span class="trend-count">6.3k posts</span>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trend-rank">4</div>
                        <div class="trend-content">
                            <span class="trend-tag">#LeonidaMap</span>
                            <span class="trend-count">4.9k posts</span>
                        </div>
                    </div>
                    <div class="trending-item">
                        <div class="trend-rank">5</div>
                        <div class="trend-content">
                            <span class="trend-tag">#RockstarGames</span>
                            <span class="trend-count">3.1k posts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Widget -->
        <div class="stats-widget">
            <div class="widget-header">
                <h3>
                    <i class="fa fa-chart-bar"></i>
                    Estatísticas
                </h3>
            </div>
            <div class="widget-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-newspaper"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?= $total_news ?? 247 ?></div>
                            <div class="stat-label">Notícias Publicadas</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-eye"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?= isset($news_list) ? number_format(array_sum(array_column($news_list, 'views'))/1000000, 1) . 'M' : '1.2M' ?></div>
                            <div class="stat-label">Visualizações Totais</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?= isset($news_list) ? number_format(array_sum(array_column($news_list, 'comments_count'))/1000, 1) . 'k' : '8.5k' ?></div>
                            <div class="stat-label">Comentários</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">2.8k</div>
                            <div class="stat-label">Autores Ativos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter Widget -->
        <div class="newsletter-widget">
            <div class="widget-header">
                <h3>
                    <i class="fa fa-envelope"></i>
                    Newsletter
                </h3>
            </div>
            <div class="widget-content">
                <p>Receba as últimas notícias de GTA VI diretamente no seu e-mail!</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Seu e-mail" required>
                    <button type="submit" class="btn-subscribe">
                        <i class="fa fa-paper-plane"></i>
                        Inscrever
                    </button>
                </form>
                <div class="newsletter-info">
                    <i class="fa fa-users"></i>
                    <span>12.5k+ inscritos</span>
                </div>
            </div>
        </div>
    </aside>
</main>

<link rel="stylesheet" href="<?= site_url() ?>/assets/css/noticias.css">
<!-- JavaScript Data Integration -->
<script>
window.newsPageData = {
    currentPage: <?= $current_page ?? 1 ?>,
    totalPages: <?= $total_pages ?? 1 ?>,
    currentCategory: '<?= $current_category ?? '' ?>',
    currentSearch: '<?= htmlspecialchars($current_search ?? '', ENT_QUOTES) ?>',
    totalNews: <?= $total_news ?? 247 ?>,
    newsApiUrl: '<?= site_url('api/news.php') ?>',
    baseUrl: '<?= site_url('noticias') ?>',
    hasMore: <?= ($current_page ?? 1) < ($total_pages ?? 1) ? 'true' : 'false' ?>
};
</script>
<script src="<?= site_url() ?>assets/js/noticias.js"></script>