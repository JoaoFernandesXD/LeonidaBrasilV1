<?php
// controllers/HomeController.php
// Controller completo para a página inicial com dados dinâmicos

class HomeController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Buscar todos os dados para a página inicial
        $data = [
            // Seções principais da home
            'carousel_slides' => $this->getCarouselSlides(),
            'latest_news' => $this->getLatestNews(6),
            'recent_topics' => $this->getRecentTopics(8),
            'hot_topics' => $this->getHotTopics(5),
            
            // Widgets da sidebar
            'featured_user' => $this->getFeaturedUser(),
            'community_ranking' => $this->getCommunityRanking(5),
            'recent_badges' => $this->getRecentBadges(8),
            
            // Estatísticas
            'site_stats' => $this->getSiteStats(),
            'online_count' => $this->getOnlineCount(),
            
            // Dados do usuário atual
            'current_user' => current_user(),
            
            // Meta dados para SEO
            'meta_title' => 'Leonida Brasil - Portal GTA VI',
            'meta_description' => 'Portal dedicado ao universo de GTA VI com notícias exclusivas, fórum ativo e hub completo de informações sobre Leonida.',
            'meta_keywords' => 'GTA VI, GTA 6, Leonida, Vice City, Jason, Lucia, portal, notícias, fórum'
        ];
        
        // Carregar a view
        $this->loadView('home', $data);
    }
    
    /**
     * Slides do carousel principal
     */
    private function getCarouselSlides() {
        try {
            $sql = "SELECT id, slug, title, subtitle, excerpt, featured_image, category
                    FROM news 
                    WHERE status = 'published' AND featured = 1
                    ORDER BY published_at DESC 
                    LIMIT 3";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $slides = $stmt->fetchAll();
            
            // Se não tiver notícias em destaque, pegar as mais recentes
            if (empty($slides)) {
                $sql = "SELECT id, slug, title, subtitle, excerpt, featured_image, category
                        FROM news 
                        WHERE status = 'published'
                        ORDER BY published_at DESC 
                        LIMIT 3";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $slides = $stmt->fetchAll();
            }
            
            // Fallback com dados estáticos se não houver notícias
            if (empty($slides)) {
                return [
                    [
                        'id' => 1,
                        'title' => 'Segundo Trailer de GTA VI Revelado!',
                        'subtitle' => 'Novas cenas épicas de Jason e Lucia em ação no estado de Leonida',
                        'featured_image' => 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg',
                        'slug' => 'segundo-trailer-gta-vi'
                    ],
                    [
                        'id' => 2,
                        'title' => 'Mapa de Vice City Reconstituído',
                        'subtitle' => 'Comunidade mapeia cada detalhe revelado nos trailers oficiais',
                        'featured_image' => 'https://www.gtavice.net/content/images/vice-city-04.jpg',
                        'slug' => 'mapa-vice-city'
                    ],
                    [
                        'id' => 3,
                        'title' => 'HUB Leonida Atualizado',
                        'subtitle' => 'Nova base de dados com personagens, locais e veículos de GTA VI',
                        'featured_image' => 'https://www.gtavice.net/content/images/port-gellhorn-03.jpg',
                        'slug' => 'hub-leonida-atualizado'
                    ]
                ];
            }
            
            return $slides;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar slides do carousel: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Últimas notícias para a grid
     */
    private function getLatestNews($limit = 6) {
        try {
            $sql = "SELECT n.id, n.slug, n.title, n.excerpt, n.featured_image, 
                           n.category, n.views, n.created_at, n.published_at,
                           u.username, u.display_name,
                           (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id) as comments_count
                    FROM news n
                    JOIN users u ON n.author_id = u.id
                    WHERE n.status = 'published' 
                    ORDER BY n.published_at DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $news = $stmt->fetchAll();
            
            // Adicionar likes simulados e formatar dados
            foreach ($news as &$item) {
                $item['likes'] = rand(10, 500);
                $item['time_ago'] = time_ago($item['published_at']);
                $item['category_class'] = strtolower($item['category']);
                $item['is_featured'] = ($item['id'] == 1); // Primeira notícia como destaque
            }
            
            return $news;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias: " . $e->getMessage());
            return $this->getFallbackNews();
        }
    }
    
    /**
     * Tópicos recentes do fórum
     */
    private function getRecentTopics($limit = 8) {
        try {
            $sql = "SELECT ft.id, ft.title, ft.views, ft.replies_count, ft.created_at,
                           ft.status, ft.last_reply_at,
                           fc.name as category_name, fc.icon as category_icon,
                           u.username, u.display_name, u.avatar
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON ft.author_id = u.id
                    WHERE ft.status IN ('open', 'pinned')
                    ORDER BY 
                        CASE WHEN ft.status = 'pinned' THEN 0 ELSE 1 END,
                        ft.last_reply_at DESC, 
                        ft.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll();
            
            // Formatar dados
            foreach ($topics as &$topic) {
                $topic['time_ago'] = time_ago($topic['last_reply_at'] ?: $topic['created_at']);
                $topic['is_pinned'] = ($topic['status'] === 'pinned');
                $topic['avatar_url'] = $topic['avatar'] ?: 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
            }
            
            return $topics;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar tópicos do fórum: " . $e->getMessage());
            return $this->getFallbackTopics();
        }
    }
    
    /**
     * Assuntos quentes (tags populares)
     */
    private function getHotTopics($limit = 5) {
        try {
            $sql = "SELECT name, slug, usage_count
                    FROM tags 
                    WHERE usage_count > 0
                    ORDER BY usage_count DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll();
            
            // Se não houver tags, retornar fallback
            if (empty($topics)) {
                return [
                    ['name' => 'TeoriasGTA6', 'slug' => 'teorias-gta6', 'usage_count' => 234],
                    ['name' => 'MapaLeonida', 'slug' => 'mapa-leonida', 'usage_count' => 189],
                    ['name' => 'JasonLucia', 'slug' => 'jason-lucia', 'usage_count' => 156],
                    ['name' => 'ViceCity2026', 'slug' => 'vice-city-2026', 'usage_count' => 134],
                    ['name' => 'EasterEggs', 'slug' => 'easter-eggs', 'usage_count' => 98]
                ];
            }
            
            return $topics;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar assuntos quentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Usuário em destaque
     */
    private function getFeaturedUser() {
        try {
            $sql = "SELECT u.id, u.username, u.display_name, u.avatar, u.level, 
                           u.experience_points,
                           (SELECT COUNT(*) FROM forum_topics WHERE author_id = u.id) as topics_count,
                           (SELECT COUNT(*) FROM news WHERE author_id = u.id AND status = 'published') as news_count
                    FROM users u
                    WHERE u.status = 'active'
                    ORDER BY u.experience_points DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'username' => 'Lisamixart',
                    'display_name' => 'Lisamixart',
                    'title' => 'GTA VI Mud Girl Artwork',
                    'avatar' => 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg'
                ];
            }
            
            $user['title'] = $this->generateUserTitle($user);
            $user['avatar'] = $user['avatar'] ?: 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
            
            return $user;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário em destaque: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ranking da comunidade
     */
    private function getCommunityRanking($limit = 5) {
        try {
            $sql = "SELECT u.id, u.username, u.display_name, u.avatar, u.level, 
                           u.experience_points, ur.points as ranking_points,
                           ROW_NUMBER() OVER (ORDER BY u.experience_points DESC) as position
                    FROM users u
                    LEFT JOIN user_rankings ur ON u.id = ur.user_id AND ur.category = 'general'
                    WHERE u.status = 'active'
                    ORDER BY u.experience_points DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $ranking = $stmt->fetchAll();
            
            // Adicionar classes de medalha
            foreach ($ranking as &$user) {
                $user['medal_class'] = $this->getMedalClass($user['position']);
                $user['points'] = $user['ranking_points'] ?: $user['experience_points'];
                $user['avatar'] = $user['avatar'] ?: 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
            }
            
            return $ranking;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar ranking: " . $e->getMessage());
            return $this->getFallbackRanking();
        }
    }
    
    /**
     * Emblemas recentes (simulação por enquanto)
     */
    private function getRecentBadges($limit = 8) {
        try {
            // Por enquanto, vamos simular badges baseados em atividades dos usuários
            $sql = "SELECT u.id, u.username, u.display_name, u.avatar, 
                           u.registration_date, u.level,
                           (SELECT COUNT(*) FROM forum_topics WHERE author_id = u.id) as topics_count,
                           (SELECT COUNT(*) FROM news WHERE author_id = u.id) as news_count
                    FROM users u
                    WHERE u.status = 'active'
                    ORDER BY u.registration_date DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            $badges = [];
            foreach ($users as $user) {
                $badge = $this->generateBadgeForUser($user);
                if ($badge) {
                    $badges[] = $badge;
                }
            }
            
            return array_slice($badges, 0, $limit);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar badges: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Estatísticas do site
     */
    private function getSiteStats() {
        try {
            $stats = [];
            
            // Total de usuários ativos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stats['total_users'] = $stmt->fetch()['total'];
            
            // Total de notícias publicadas
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM news WHERE status = 'published'");
            $stats['total_news'] = $stmt->fetch()['total'];
            
            // Total de tópicos do fórum
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM forum_topics");
            $stats['total_topics'] = $stmt->fetch()['total'];
            
            // Total de itens do hub
            $stmt = $this->db->query("
                SELECT 
                    (SELECT COUNT(*) FROM characters) +
                    (SELECT COUNT(*) FROM locations) +
                    (SELECT COUNT(*) FROM vehicles) +
                    (SELECT COUNT(*) FROM missions) as total
            ");
            $stats['total_hub_items'] = $stmt->fetch()['total'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'total_users' => 1247,
                'total_news' => 156,
                'total_topics' => 892,
                'total_hub_items' => 234
            ];
        }
    }
    
    /**
     * Contagem de usuários online (simulado)
     */
    private function getOnlineCount() {
        // Por enquanto, simular usuários online
        return rand(800, 1500);
    }
    
    /**
     * Gerar título personalizado para o usuário
     */
    private function generateUserTitle($user) {
        if ($user['news_count'] > 10) {
            return 'Jornalista da Comunidade';
        } elseif ($user['topics_count'] > 20) {
            return 'Líder da Discussão';
        } elseif ($user['level'] >= 5) {
            return 'Veterano de Leonida';
        } else {
            return 'Membro Ativo';
        }
    }
    
    /**
     * Obter classe da medalha baseada na posição
     */
    private function getMedalClass($position) {
        switch ($position) {
            case 1: return 'gold';
            case 2: return 'silver';
            case 3: return 'bronze';
            default: return '';
        }
    }
    
    /**
     * Gerar badge simulado para usuário
     */
    private function generateBadgeForUser($user) {
        $badges = [
            ['name' => 'Primeiro Post', 'icon' => 'https://www.gtavice.net/content/images/official-gta-vi-logo.png'],
            ['name' => 'Veterano', 'icon' => 'https://www.gtavice.net/content/images/official-gta-vi-logo.png'],
            ['name' => 'Contribuidor', 'icon' => 'https://www.gtavice.net/content/images/official-gta-vi-logo.png'],
            ['name' => 'Teorista', 'icon' => 'https://www.gtavice.net/content/images/official-gta-vi-logo.png']
        ];
        
        $randomBadge = $badges[array_rand($badges)];
        
        return [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'badge_name' => $randomBadge['name'],
            'badge_icon' => $randomBadge['icon'],
            'earned_at' => $user['registration_date']
        ];
    }
    
    /**
     * Fallbacks para quando não há dados no banco
     */
    private function getFallbackNews() {
        return [
            [
                'id' => 1,
                'title' => 'Análise Completa do Trailer 2: Novos Detalhes de Leonida',
                'category' => 'analysis',
                'username' => 'TheoryMaster',
                'time_ago' => 'há 2 dias',
                'views' => 2100,
                'comments_count' => 67,
                'likes' => 543,
                'featured_image' => 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg',
                'is_featured' => true
            ]
            // ... mais itens de fallback
        ];
    }
    
    private function getFallbackTopics() {
        return [
            [
                'id' => 1,
                'title' => 'Regras Gerais do Fórum - Leia Antes de Postar',
                'username' => 'Moderação',
                'time_ago' => 'há 1 mês',
                'replies_count' => 0,
                'views' => 2500,
                'is_pinned' => true,
                'avatar_url' => 'https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExcDQ5Z2d1eWN5b2M3Z2R3bXBwN3p0dW9yY21jYjhkMjFtdDZyd3A4eiZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cicSTZuSpc6y9WVtvn/giphy.gif'
            ]
            // ... mais tópicos
        ];
    }
    
    private function getFallbackRanking() {
        return [
            [
                'position' => 1,
                'username' => 'VicePlayer2026',
                'points' => 1247,
                'medal_class' => 'gold',
                'avatar' => 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg'
            ]
            // ... mais usuários
        ];
    }
    
    /**
     * Carregar view com layout
     */
    private function loadView($view, $data = []) {
        // Extrair dados para variáveis
        extract($data);
        
        // Incluir header
        include 'views/includes/header.php';
        
        // Incluir a view específica
        include "views/pages/{$view}.php";
        
        // Incluir footer
        include 'views/includes/footer.php';
    }
}
?>