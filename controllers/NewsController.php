<?php
// controllers/NewsController.php
// Controller completo para notícias

class NewsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Página principal de notícias (lista)
     */
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $category = sanitize_input($_GET['category'] ?? '');
        $search = sanitize_input($_GET['search'] ?? '');
        
        $data = [
            'news_list' => $this->getNewsList($page, 6, $category, $search),
            'categories' => $this->getCategories(),
            'featured_news' => $this->getFeaturedNews(3),
            'popular_news' => $this->getPopularNews(5),
            'current_page' => $page,
            'current_category' => $category,
            'current_search' => $search,
            'meta_title' => $category ? "Notícias - " . ucfirst($category) : 'Notícias - Leonida Brasil',
            'meta_description' => 'Últimas notícias sobre GTA VI, trailers, teorias e análises da comunidade Leonida Brasil.'
        ];
        
        $this->loadView('news', $data);
    }
    
    /**
     * Página individual de notícia
     */
    public function single($slug) {
        try {
            // Buscar notícia pelo slug
            $news = $this->getNewsBySlug($slug);
            
            if (!$news) {
                // Redirecionar para 404 se não encontrar
                header("HTTP/1.1 404 Not Found");
                $this->loadView('404');
                return;
            }
            
            // Incrementar visualização
            $this->incrementViews($news['id']);
            
            // Buscar dados relacionados
            $related_news = $this->getRelatedNews($news['id'], $news['category'], 4);
            $comments = $this->getComments($news['id']);
            $author = $this->getAuthor($news['author_id']);
            
            $data = [
                'news' => $news,
                'author' => $author,
                'related_news' => $related_news,
                'comments' => $comments,
                'comments_count' => count($comments),
                'is_liked' => $this->isLikedByUser($news['id']),
                'meta_title' => $news['meta_title'] ?: $news['title'] . ' - Leonida Brasil',
                'meta_description' => $news['meta_description'] ?: $news['excerpt'],
                'meta_image' => $news['featured_image'],
                'canonical_url' => site_url("noticia/{$news['slug']}")
            ];
            
            $this->loadView('news-single', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao carregar notícia: " . $e->getMessage());
            header("HTTP/1.1 500 Internal Server Error");
            $this->loadView('error');
        }
    }
    
    /**
     * Buscar notícia por slug
     */
    private function getNewsBySlug($slug) {
        try {
            $sql = "SELECT n.*, u.username, u.display_name, u.avatar as author_avatar,
                           (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id AND status = 'active') as comments_count
                    FROM news n
                    JOIN users u ON n.author_id = u.id
                    WHERE n.slug = :slug AND n.status = 'published'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            
            $news = $stmt->fetch();
            
            if ($news) {
                // Formatar dados
                $news['time_ago'] = time_ago($news['published_at'] ?: $news['created_at']);
                $news['formatted_date'] = format_date($news['published_at'] ?: $news['created_at'], 'd/m/Y H:i');
                $news['reading_time'] = $this->calculateReadingTime($news['content']);
                $news['author_name'] = $news['display_name'] ?: $news['username'];
                $news['category_formatted'] = $this->formatCategoryName($news['category']);
                $news['tags'] = $news['tags'] ? json_decode($news['tags'], true) : [];
                $news['image_gallery'] = $news['image_gallery'] ? json_decode($news['image_gallery'], true) : [];
                
                // URL do autor
                $news['author_url'] = site_url("perfil/{$news['username']}");
                
                // Avatar padrão se não tiver
                if (empty($news['author_avatar'])) {
                    $news['author_avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                }
            }
            
            return $news;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícia por slug: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lista de notícias com paginação
     */
    private function getNewsList($page, $per_page, $category = '', $search = '') {
        try {
            $offset = ($page - 1) * $per_page;
            
            // Construir condições WHERE
            $where_conditions = ["n.status = 'published'"];
            $params = [];
            
            if (!empty($category)) {
                $where_conditions[] = "n.category = :category";
                $params['category'] = $category;
            }
            
            if (!empty($search)) {
                $where_conditions[] = "(n.title LIKE :search OR n.content LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $sql = "SELECT n.id, n.slug, n.title, n.subtitle, n.excerpt, n.featured_image, 
                           n.category, n.views, n.likes, n.published_at, n.created_at,
                           u.username, u.display_name,
                           (SELECT COUNT(*) FROM comments WHERE content_type = 'news' AND content_id = n.id AND status = 'active') as comments_count
                    FROM news n
                    JOIN users u ON n.author_id = u.id
                    WHERE {$where_clause}
                    ORDER BY n.featured DESC, n.published_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $news = $stmt->fetchAll();
            
            // Formatar dados
            foreach ($news as &$item) {
                $item['time_ago'] = time_ago($item['published_at'] ?: $item['created_at']);
                $item['category_formatted'] = $this->formatCategoryName($item['category']);
                $item['author_name'] = $item['display_name'] ?: $item['username'];
                $item['url'] = site_url("noticia/{$item['slug']}");
                $item['formatted_views'] = number_format($item['views']);
                
                // Imagem padrão se não tiver
                if (empty($item['featured_image'])) {
                    $item['featured_image'] = 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg';
                }
            }
            
            return $news;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar lista de notícias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Notícias relacionadas
     */
    private function getRelatedNews($news_id, $category, $limit = 4) {
        try {
            $sql = "SELECT id, slug, title, featured_image, views, published_at, created_at
                    FROM news 
                    WHERE id != :news_id 
                    AND category = :category 
                    AND status = 'published'
                    ORDER BY views DESC, published_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
            $stmt->bindValue(':category', $category);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $related = $stmt->fetchAll();
            
            // Formatar dados
            foreach ($related as &$item) {
                $item['time_ago'] = time_ago($item['published_at'] ?: $item['created_at']);
                $item['url'] = site_url("noticia/{$item['slug']}");
                $item['formatted_views'] = number_format($item['views']);
                
                if (empty($item['featured_image'])) {
                    $item['featured_image'] = 'https://www.gtavice.net/content/images/brian-hi-res-headshot-artwork.jpg';
                }
            }
            
            return $related;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias relacionadas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Comentários da notícia
     */
    private function getComments($news_id) {
        try {
            $sql = "SELECT c.*, u.username, u.display_name, u.avatar
                    FROM comments c
                    JOIN users u ON c.author_id = u.id
                    WHERE c.content_type = 'news' 
                    AND c.content_id = :news_id 
                    AND c.status = 'active'
                    ORDER BY c.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $comments = $stmt->fetchAll();
            
            // Formatar dados
            foreach ($comments as &$comment) {
                $comment['time_ago'] = time_ago($comment['created_at']);
                $comment['author_name'] = $comment['display_name'] ?: $comment['username'];
                $comment['author_url'] = site_url("perfil/{$comment['username']}");
                
                if (empty($comment['avatar'])) {
                    $comment['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                }
            }
            
            return $comments;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar comentários: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Dados do autor
     */
    private function getAuthor($author_id) {
        try {
            $sql = "SELECT u.*, 
                           (SELECT COUNT(*) FROM news WHERE author_id = u.id AND status = 'published') as news_count,
                           (SELECT COUNT(*) FROM forum_topics WHERE author_id = u.id) as topics_count
                    FROM users u
                    WHERE u.id = :author_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $author = $stmt->fetch();
            
            if ($author) {
                $author['name'] = $author['display_name'] ?: $author['username'];
                $author['url'] = site_url("perfil/{$author['username']}");
                $author['member_since'] = format_date($author['registration_date'], 'm/Y');
                
                if (empty($author['avatar'])) {
                    $author['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                }
            }
            
            return $author;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar dados do autor: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Incrementar visualizações
     */
    private function incrementViews($news_id) {
        try {
            $sql = "UPDATE news SET views = views + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $news_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao incrementar visualizações: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar se usuário curtiu a notícia
     */
    private function isLikedByUser($news_id) {
        if (!is_logged_in()) return false;
        
        try {
            $sql = "SELECT id FROM user_favorites 
                    WHERE user_id = :user_id 
                    AND item_type = 'news' 
                    AND item_id = :news_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':news_id', $news_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar like: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcular tempo de leitura
     */
    private function calculateReadingTime($content) {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // 200 palavras por minuto
        return $reading_time . ' min de leitura';
    }
    
    /**
     * Formatar nome da categoria
     */
    private function formatCategoryName($category) {
        $categories = [
            'trailers' => 'Trailers',
            'analysis' => 'Análises',
            'theories' => 'Teorias',
            'maps' => 'Mapas',
            'characters' => 'Personagens',
            'gameplay' => 'Gameplay'
        ];
        
        return $categories[$category] ?? ucfirst($category);
    }
    
    /**
     * Categorias disponíveis
     */
    private function getCategories() {
        return [
            'trailers' => 'Trailers',
            'analysis' => 'Análises',
            'theories' => 'Teorias',
            'maps' => 'Mapas',
            'characters' => 'Personagens',
            'gameplay' => 'Gameplay'
        ];
    }
    
    /**
     * Notícias em destaque
     */
    private function getFeaturedNews($limit = 3) {
        try {
            $sql = "SELECT id, slug, title, featured_image, views, published_at
                    FROM news 
                    WHERE status = 'published' AND featured = 1
                    ORDER BY published_at DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias em destaque: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Notícias populares
     */
    private function getPopularNews($limit = 5) {
        try {
            $sql = "SELECT id, slug, title, views, published_at
                    FROM news 
                    WHERE status = 'published'
                    ORDER BY views DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícias populares: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Carregar view
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