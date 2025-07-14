
CREATE TABLE `articles` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int UNSIGNED NOT NULL COMMENT '关联users表主键',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `like_count` int NOT NULL DEFAULT '0',
  `featured_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `views` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `article_attachments` (
  `id` int NOT NULL,
  `article_id` int NOT NULL,
  `user_id` int NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `size` int NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `downloads` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `clients` (
  `id` int NOT NULL,
  `client_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `client_secret` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `redirect_uri` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `comments` (
  `id` int NOT NULL,
  `content_id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `custom_codes` (
  `id` int NOT NULL,
  `custom_html` text COLLATE utf8mb4_general_ci,
  `custom_css` text COLLATE utf8mb4_general_ci,
  `custom_js` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `drafts` (
  `id` int NOT NULL,
  `author_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `saved_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `follows` (
  `id` int NOT NULL,
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `menu` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


CREATE TABLE `search_records` (
  `id` int NOT NULL,
  `query` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `sliders` (
  `id` int UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `sort` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `system_permissions` (
  `perm_id` int NOT NULL,
  `perm_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `perm_tag` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('beian_number', '备案号', '2025-06-29 12:56:17'),
('comment_approve', '0', '2025-06-29 12:56:17'),
('login_page_description', '', '2025-06-29 10:49:50'),
('login_page_image', 'uploads/login_page/login_bg_1751194190.png', '2025-06-29 10:49:50'),
('login_page_title', '', '2025-06-29 10:49:50'),
('reg_enabled', '1', '2025-06-29 12:56:17'),
('site_logo', 'uploads/xxx.png', '2025-06-29 12:56:17'),
('site_name', 'ZICMS', '2025-06-29 12:56:17'),
('site_url', 'https://example.com', '2025-06-29 12:56:17');



CREATE TABLE `system_sidebar` (
  `menu_id` int NOT NULL,
  `menu_title` varchar(50) NOT NULL,
  `menu_path` varchar(255) NOT NULL,
  `parent_id` int DEFAULT '0',
  `menu_icon` varchar(50) DEFAULT NULL,
  `sort_order` int DEFAULT '100',
  `is_show` tinyint(1) DEFAULT '1',
  `permission_tag` varchar(50) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;



CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default-avatar.jpg',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '普通用户',
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `post_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布文章数',
  `following` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注用户数',
  `fans` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `likes` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '累计获赞数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `user_follow` (
  `id` int NOT NULL,
  `follower_id` int NOT NULL,
  `following_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `article_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);


ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`);

ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`),
  ADD KEY `user_id` (`user_id`);


ALTER TABLE `custom_codes`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `drafts`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follower_id` (`follower_id`,`following_id`);


ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `search_records`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `system_permissions`
  ADD PRIMARY KEY (`perm_id`),
  ADD UNIQUE KEY `perm_tag` (`perm_tag`);


ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);


ALTER TABLE `system_sidebar`
  ADD PRIMARY KEY (`menu_id`);


ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `user_follow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `follow_relation` (`follower_id`,`following_id`);


ALTER TABLE `articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `article_attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `custom_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `drafts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `follows`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `menu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `search_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `sliders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `system_permissions`
  MODIFY `perm_id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `system_sidebar`
  MODIFY `menu_id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `user_follow`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;


ALTER TABLE `article_attachments`
  ADD CONSTRAINT `article_attachments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;


ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `articles` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

