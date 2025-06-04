-- =================================================
-- LEONIDA BRASIL - SCHEMA COMPLETO DO BANCO DE DADOS
-- Portal GTA VI - Sistema Backend PHP
-- =================================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =================================================
-- 1. TABELAS DE USUÁRIOS
-- =================================================

-- Usuários principais
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `experience_points` int(11) DEFAULT 0,
  `registration_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','suspended','banned') DEFAULT 'active',
  `email_verified` boolean DEFAULT FALSE,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perfis dos usuários
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `favorite_gta` varchar(50) DEFAULT NULL,
  `gaming_platforms` json DEFAULT NULL,
  `favorite_games` json DEFAULT NULL,
  `social_links` json DEFAULT NULL,
  `privacy_settings` json DEFAULT NULL,
  `notification_settings` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de rankings
CREATE TABLE `user_rankings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` enum('general','forum','news','gallery') NOT NULL,
  `points` int(11) DEFAULT 0,
  `position` int(11) DEFAULT NULL,
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category` (`category`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- 2. TABELAS DO HUB (CONTEÚDO GTA VI)
-- =================================================

-- Personagens
CREATE TABLE `characters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `biography` longtext DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `image_gallery` json DEFAULT NULL,
  `type` enum('protagonist','main','secondary','npc') DEFAULT 'secondary',
  `status` enum('confirmed','rumor','theory') DEFAULT 'rumor',
  `abilities` json DEFAULT NULL,
  `relationships` json DEFAULT NULL,
  `timeline_events` json DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Localizações
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `detailed_info` longtext DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `image_gallery` json DEFAULT NULL,
  `map_coordinates` json DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `type` enum('city','district','landmark','business') DEFAULT 'landmark',
  `features` json DEFAULT NULL,
  `poi_data` json DEFAULT NULL,
  `missions_related` json DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Veículos
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `manufacturer` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specifications` longtext DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `image_gallery` json DEFAULT NULL,
  `performance_stats` json DEFAULT NULL,
  `customization_options` json DEFAULT NULL,
  `locations_found` json DEFAULT NULL,
  `price_range` json DEFAULT NULL,
  `status` enum('confirmed','rumor','leaked') DEFAULT 'rumor',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Missões
CREATE TABLE `missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `detailed_walkthrough` longtext DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `image_gallery` json DEFAULT NULL,
  `type` enum('main','side','random') DEFAULT 'side',
  `difficulty` enum('easy','medium','hard','expert') DEFAULT 'medium',
  `objectives` json DEFAULT NULL,
  `strategies` json DEFAULT NULL,
  `rewards` json DEFAULT NULL,
  `characters_involved` json DEFAULT NULL,
  `locations_involved` json DEFAULT NULL,
  `timeline_position` int(11) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- 3. TABELAS DE CONTEÚDO
-- =================================================

-- Notícias
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `image_gallery` json DEFAULT NULL,
  `category` enum('trailers','analysis','theories','maps','characters') DEFAULT 'analysis',
  `tags` json DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `featured` boolean DEFAULT FALSE,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galeria
CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `dimensions` json DEFAULT NULL,
  `category` enum('screenshots','videos','fanart','wallpapers') DEFAULT 'screenshots',
  `tags` json DEFAULT NULL,
  `uploader_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploader_id` (`uploader_id`),
  FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- 4. TABELAS DO FÓRUM
-- =================================================

-- Categorias do fórum
CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `order_position` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tópicos do fórum
CREATE TABLE `forum_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `status` enum('open','closed','pinned','locked') DEFAULT 'open',
  `views` int(11) DEFAULT 0,
  `replies_count` int(11) DEFAULT 0,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `last_reply_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `author_id` (`author_id`),
  KEY `last_reply_by` (`last_reply_by`),
  FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`last_reply_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respostas do fórum
CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `parent_reply_id` int(11) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `status` enum('active','hidden','deleted') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `author_id` (`author_id`),
  KEY `parent_reply_id` (`parent_reply_id`),
  FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_reply_id`) REFERENCES `forum_replies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- 5. TABELAS DE SISTEMA
-- =================================================

-- Favoritos dos usuários
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_type` enum('character','location','vehicle','mission','news') NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_lookup` (`user_id`, `item_type`, `item_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários (notícias, galeria, etc.)
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('news','gallery','character','location') NOT NULL,
  `content_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `status` enum('active','hidden','deleted') DEFAULT 'active',
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `content_lookup` (`content_type`, `content_id`),
  KEY `author_id` (`author_id`),
  KEY `parent_comment_id` (`parent_comment_id`),
  FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de tags
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#007cba',
  `usage_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rádio (Vice City FM)
CREATE TABLE `radio_tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `album` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `play_count` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações do sistema
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- 6. ÍNDICES PARA PERFORMANCE
-- =================================================

-- Índices principais para otimização
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);

CREATE INDEX idx_characters_slug ON characters(slug);
CREATE INDEX idx_characters_type ON characters(type);
CREATE INDEX idx_characters_status ON characters(status);

CREATE INDEX idx_locations_slug ON locations(slug);
CREATE INDEX idx_locations_region ON locations(region);

CREATE INDEX idx_vehicles_slug ON vehicles(slug);
CREATE INDEX idx_vehicles_category ON vehicles(category);

CREATE INDEX idx_missions_slug ON missions(slug);
CREATE INDEX idx_missions_type ON missions(type);

CREATE INDEX idx_news_slug ON news(slug);
CREATE INDEX idx_news_category ON news(category);
CREATE INDEX idx_news_status ON news(status);
CREATE INDEX idx_news_published_at ON news(published_at);

CREATE INDEX idx_gallery_category ON gallery_items(category);
CREATE INDEX idx_gallery_status ON gallery_items(status);

CREATE INDEX idx_forum_topics_category ON forum_topics(category_id);
CREATE INDEX idx_forum_replies_topic ON forum_replies(topic_id);

-- Índices compostos para consultas específicas
CREATE INDEX idx_user_favorites_lookup ON user_favorites(user_id, item_type, item_id);
CREATE INDEX idx_comments_content ON comments(content_type, content_id);

-- =================================================
-- 7. DADOS INICIAIS DO SISTEMA
-- =================================================

-- Inserir configurações padrão do sistema
INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'Leonida Brasil', 'string'),
('site_description', 'Portal dedicado ao universo de GTA VI', 'string'),
('user_registration_enabled', '1', 'boolean'),
('forum_enabled', '1', 'boolean'),
('gallery_upload_enabled', '1', 'boolean'),
('max_file_size', '10485760', 'integer'),
('radio_enabled', '1', 'boolean');

-- Inserir categorias padrão do fórum
INSERT INTO forum_categories (name, description, icon, order_position, status) VALUES
('Geral', 'Discussões gerais sobre GTA VI', 'fas fa-comments', 1, 'active'),
('Teorias', 'Compartilhe suas teorias sobre o jogo', 'fas fa-lightbulb', 2, 'active'),
('Missões', 'Discussões sobre missões e walkthroughs', 'fas fa-tasks', 3, 'active'),
('Personagens', 'Tudo sobre os personagens de GTA VI', 'fas fa-users', 4, 'active'),
('Veículos', 'Carros, motos e outros veículos', 'fas fa-car', 5, 'active'),
('Mapas', 'Discussões sobre localizações e mapas', 'fas fa-map', 6, 'active'),
('Off-Topic', 'Assuntos diversos não relacionados ao jogo', 'fas fa-coffee', 7, 'active');

-- Inserir tags padrão
INSERT INTO tags (name, slug, color, usage_count) VALUES
('GTA VI', 'gta-vi', '#ff6b6b', 0),
('Jason', 'jason', '#4ecdc4', 0),
('Lucia', 'lucia', '#45b7d1', 0),
('Vice City', 'vice-city', '#96ceb4', 0),
('Leonida', 'leonida', '#ffeaa7', 0),
('Trailer', 'trailer', '#fd79a8', 0),
('Gameplay', 'gameplay', '#a29bfe', 0),
('Rumores', 'rumores', '#6c5ce7', 0);

COMMIT;