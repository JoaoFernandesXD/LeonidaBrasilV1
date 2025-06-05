<?php
// controllers/ForumController.php
// Controller para o sistema de fórum

class ForumController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Página principal do fórum (lista de categorias)
     */
    public function index() {
        $data = [
            'categories' => $this->getCategories(),
            'recent_topics' => $this->getRecentTopics(10),
            'forum_stats' => $this->getForumStats(),
            'online_users' => $this->getOnlineUsers(),
            'meta_title' => 'Fórum - Leonida Brasil',
            'meta_description' => 'Participe das discussões sobre GTA VI no fórum da comunidade Leonida Brasil.',
        ];
        
        $this->loadView('forum', $data);
    }
    
    /**
     * Exibir tópico específico
     */
    public function topic($slug) {
        if (!$slug) {
            $this->show404();
            return;
        }
        
        // Buscar tópico pelo slug
        $topic = $this->getTopicBySlug($slug);
        
        if (!$topic) {
            $this->show404();
            return;
        }
        
        // Incrementar visualizações
        $this->incrementTopicViews($topic['id']);
        
        // Buscar respostas
        $replies = $this->getTopicReplies($topic['id']);
        
        // Buscar informações da categoria
        $category = $this->getCategoryById($topic['category_id']);
        
        $data = [
            'topic' => $topic,
            'replies' => $replies,
            'category' => $category,
            'reply_count' => count($replies),
            'current_user' => current_user(),
            'is_logged_in' => is_logged_in(),
            'can_reply' => $this->canReply($topic),
            'breadcrumbs' => $this->getBreadcrumbs($category, $topic),
            'meta_title' => $topic['title'] . ' - Fórum Leonida Brasil',
            'meta_description' => truncate_text(strip_tags($topic['content']), 160),
        ];
        
        $this->loadView('forum-topic', $data);
    }
    
    /**
     * Página de categoria
     */
    public function category($category_slug) {
        // Buscar categoria
        $category = $this->getCategoryBySlug($category_slug);
        
        if (!$category) {
            $this->show404();
            return;
        }
        
        // Parâmetros de paginação
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = 15;
        $offset = ($page - 1) * $per_page;
        
        // Buscar tópicos da categoria
        $topics = $this->getCategoryTopics($category['id'], $per_page, $offset);
        $total_topics = $this->countCategoryTopics($category['id']);
        
        $data = [
            'category' => $category,
            'topics' => $topics,
            'pagination' => $this->getPaginationData($page, $per_page, $total_topics),
            'meta_title' => $category['name'] . ' - Fórum Leonida Brasil',
            'meta_description' => $category['description'],
        ];
        
        $this->loadView('forum-category', $data);
    }
    
    /**
     * Criar novo tópico
     */
    public function createTopic() {
        if (!is_logged_in()) {
            redirect(site_url('login'));
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreateTopic();
            return;
        }
        
        $data = [
            'categories' => $this->getCategories(),
            'meta_title' => 'Criar Tópico - Fórum Leonida Brasil',
        ];
        
        $this->loadView('forum-create-topic', $data);
    }
    
    /**
     * Buscar tópico pelo slug
     */
    private function getTopicBySlug($slug) {
        try {
            $sql = "SELECT ft.*, fc.name as category_name, fc.slug as category_slug,
                           u.username, u.display_name, u.avatar, u.level
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON ft.author_id = u.id
                    WHERE ft.slug = :slug 
                    AND ft.status IN ('open', 'pinned', 'closed')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($topic) {
                // Formatar dados
                $topic['time_ago'] = time_ago($topic['created_at']);
                $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
                $topic['formatted_views'] = number_format($topic['views']);
                $topic['is_pinned'] = ($topic['status'] === 'pinned');
                $topic['is_closed'] = ($topic['status'] === 'closed');
                $topic['is_locked'] = ($topic['status'] === 'locked');
                
                // Avatar padrão se não tiver
                if (empty($topic['avatar'])) {
                    $topic['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                }
                
                // Determinar título do usuário baseado no level
                $topic['user_title'] = $this->getUserTitle($topic['level']);
                
                // Verificar se é verificado (level >= 2)
                $topic['is_verified'] = ($topic['level'] >= 2);
            }
            
            return $topic;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar tópico: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Buscar respostas do tópico
     */
    private function getTopicReplies($topic_id) {
        try {
            $sql = "SELECT fr.*, u.username, u.display_name, u.avatar, u.level,
                           (SELECT COUNT(*) FROM forum_replies WHERE author_id = u.id) as message_count
                    FROM forum_replies fr
                    JOIN users u ON fr.author_id = u.id
                    WHERE fr.topic_id = :topic_id 
                    AND fr.status = 'active'
                    ORDER BY fr.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
            $stmt->execute();
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatar dados das respostas
            foreach ($replies as &$reply) {
                $reply['time_ago'] = time_ago($reply['created_at']);
                $reply['author_name'] = $reply['display_name'] ?: $reply['username'];
                $reply['formatted_likes'] = number_format($reply['likes']);
                
                if (empty($reply['avatar'])) {
                    $reply['avatar'] = 'https://www.gtavice.net/content/images/gta-vi-mud-girl-artwork-by-lisamixart.jpeg';
                }
                
                // Status do usuário (online/offline)
                $reply['user_status'] = $this->getUserStatus($reply['author_id']);
                $reply['user_title'] = $this->getUserTitle($reply['level']);
                $reply['is_verified'] = ($reply['level'] >= 2);
                
                // Badges do usuário
                $reply['user_badges'] = $this->getUserBadges($reply['level'], $reply['message_count']);
            }
            
            return $replies;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar respostas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Incrementar visualizações do tópico
     */
    private function incrementTopicViews($topic_id) {
        try {
            $sql = "UPDATE forum_topics SET views = views + 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao incrementar visualizações: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar se pode responder ao tópico
     */
    private function canReply($topic) {
        if (!is_logged_in()) {
            return false;
        }
        
        if ($topic['status'] === 'closed' || $topic['status'] === 'locked') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Gerar breadcrumbs
     */
    private function getBreadcrumbs($category, $topic) {
        return [
            [
                'name' => 'Início',
                'url' => site_url(''),
                'icon' => 'fa fa-home'
            ],
            [
                'name' => 'Fórum',
                'url' => site_url('forum'),
                'icon' => null
            ],
            [
                'name' => $category['name'],
                'url' => site_url('forum/categoria/' . $category['slug']),
                'icon' => null
            ],
            [
                'name' => $topic['title'],
                'url' => null,
                'icon' => null,
                'current' => true
            ]
        ];
    }
    
    /**
     * Buscar categorias do fórum
     */
    private function getCategories() {
        try {
            $sql = "SELECT fc.*, COUNT(ft.id) as topics_count
                    FROM forum_categories fc
                    LEFT JOIN forum_topics ft ON fc.id = ft.category_id
                    WHERE fc.status = 'active'
                    GROUP BY fc.id
                    ORDER BY fc.order_position ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar categoria por ID
     */
    private function getCategoryById($id) {
        try {
            $sql = "SELECT * FROM forum_categories WHERE id = :id AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Buscar categoria por slug
     */
    private function getCategoryBySlug($slug) {
        try {
            $sql = "SELECT * FROM forum_categories WHERE slug = :slug AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Buscar tópicos recentes
     */
    private function getRecentTopics($limit = 10) {
        try {
            $sql = "SELECT ft.*, fc.name as category_name, u.username, u.display_name
                    FROM forum_topics ft
                    JOIN forum_categories fc ON ft.category_id = fc.id
                    JOIN users u ON ft.author_id = u.id
                    WHERE ft.status IN ('open', 'pinned')
                    ORDER BY ft.last_reply_at DESC, ft.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($topics as &$topic) {
                $topic['time_ago'] = time_ago($topic['last_reply_at'] ?: $topic['created_at']);
                $topic['author_name'] = $topic['display_name'] ?: $topic['username'];
                $topic['url'] = site_url('forum/topico/' . $topic['slug']);
            }
            
            return $topics;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar tópicos recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Estatísticas do fórum
     */
    private function getForumStats() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM forum_topics) as total_topics,
                        (SELECT COUNT(*) FROM forum_replies) as total_replies,
                        (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [
                'total_topics' => 0,
                'total_replies' => 0,
                'total_users' => 0
            ];
        }
    }
    
    /**
     * Usuários online (simulado)
     */
    private function getOnlineUsers() {
        return rand(15, 50);
    }
    
    /**
     * Determinar título do usuário baseado no level
     */
    private function getUserTitle($level) {
        switch ($level) {
            case 5: return 'Diretor Geral';
            case 4: return 'Administrador';
            case 3: return 'Moderador';
            case 2: return 'Membro Verificado';
            case 1: return 'Membro';
            default: return 'Visitante';
        }
    }
    
    /**
     * Status do usuário (online/offline)
     */
    private function getUserStatus($user_id) {
        // Simulação - em produção verificaria last_activity
        return rand(0, 1) ? 'online' : 'offline';
    }
    
    /**
     * Badges do usuário
     */
    private function getUserBadges($level, $message_count) {
        $badges = [];
        
        // Badge de nível
        if ($level >= 5) {
            $badges[] = [
                'name' => 'Diretor Geral',
                'icon' => 'fa fa-crown',
                'class' => 'director-badge',
                'level' => null
            ];
        } elseif ($level >= 3) {
            $badges[] = [
                'name' => 'Moderador',
                'icon' => 'fa fa-shield',
                'class' => 'moderator-badge',
                'level' => null
            ];
        }
        
        // Badge por quantidade de mensagens
        if ($message_count >= 500) {
            $badges[] = [
                'name' => 'Diferente',
                'icon' => 'fa fa-star',
                'class' => 'differente-badge',
                'level' => 'Nv.6'
            ];
        } elseif ($message_count >= 100) {
            $badges[] = [
                'name' => 'Simpático',
                'icon' => 'fa fa-thumbs-up',
                'class' => 'simpatico-badge',
                'level' => 'Nv.4'
            ];
        } elseif ($message_count >= 10) {
            $badges[] = [
                'name' => 'Ativo',
                'icon' => 'fa fa-comment',
                'class' => 'active-badge',
                'level' => 'Nv.2'
            ];
        }
        
        return $badges;
    }
    
    /**
     * Dados de paginação
     */
    private function getPaginationData($current_page, $per_page, $total) {
        $total_pages = ceil($total / $per_page);
        
        return [
            'current_page' => $current_page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_prev' => $current_page > 1,
            'has_next' => $current_page < $total_pages,
            'prev_page' => $current_page > 1 ? $current_page - 1 : null,
            'next_page' => $current_page < $total_pages ? $current_page + 1 : null
        ];
    }
    
    /**
     * Processar criação de tópico
     */
    private function handleCreateTopic() {
        if (!is_logged_in()) {
            set_flash_message('error', 'Você precisa estar logado para criar um tópico.');
            redirect(site_url('login'));
            return;
        }
        
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        
        // Validações
        if (empty($title) || strlen($title) < 5) {
            set_flash_message('error', 'O título deve ter pelo menos 5 caracteres.');
            redirect(site_url('forum/criar-topico'));
            return;
        }
        
        if (empty($content) || strlen($content) < 10) {
            set_flash_message('error', 'O conteúdo deve ter pelo menos 10 caracteres.');
            redirect(site_url('forum/criar-topico'));
            return;
        }
        
        if (!$this->getCategoryById($category_id)) {
            set_flash_message('error', 'Categoria inválida.');
            redirect(site_url('forum/criar-topico'));
            return;
        }
        
        try {
            // Gerar slug
            $slug = generate_slug($title);
            $original_slug = $slug;
            $counter = 1;
            
            // Verificar se slug já existe
            while ($this->slugExists($slug)) {
                $slug = $original_slug . '-' . $counter;
                $counter++;
            }
            
            // Inserir tópico
            $sql = "INSERT INTO forum_topics (category_id, author_id, title, content, slug, created_at) 
                    VALUES (:category_id, :author_id, :title, :content, :slug, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindValue(':author_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':content', $content);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            
            // Dar XP ao usuário
            $this->grantUserExperience($_SESSION['user_id'], 10);
            
            set_flash_message('success', 'Tópico criado com sucesso!');
            redirect(site_url('forum/topico/' . $slug));
            
        } catch (PDOException $e) {
            error_log("Erro ao criar tópico: " . $e->getMessage());
            set_flash_message('error', 'Erro ao criar tópico. Tente novamente.');
            redirect(site_url('forum/criar-topico'));
        }
    }
    
    /**
     * Verificar se slug já existe
     */
    private function slugExists($slug) {
        try {
            $sql = "SELECT id FROM forum_topics WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Dar experiência ao usuário
     */
    private function grantUserExperience($user_id, $points) {
        try {
            $sql = "UPDATE users SET experience_points = experience_points + :points WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':points', $points, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao dar experiência: " . $e->getMessage());
        }
    }
    
    /**
     * Página 404
     */
    private function show404() {
        http_response_code(404);
        $data = [
            'meta_title' => 'Página não encontrada - Leonida Brasil',
        ];
        $this->loadView('404', $data);
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