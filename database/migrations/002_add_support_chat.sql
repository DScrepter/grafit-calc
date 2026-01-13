-- Миграция: Добавление системы чата техподдержки
-- Дата создания: 2026-01-13

-- Добавляем роль 'support' в ENUM таблицы users
ALTER TABLE users 
MODIFY COLUMN role ENUM('super_admin', 'admin', 'support', 'user', 'guest') NOT NULL DEFAULT 'guest';

-- Таблица чатов техподдержки
CREATE TABLE IF NOT EXISTS support_chats (
	id INT NOT NULL AUTO_INCREMENT,
	user_id INT NOT NULL COMMENT 'ID пользователя, который создал чат',
	support_user_id INT NULL COMMENT 'ID сотрудника поддержки, назначенного на чат',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	last_message_at TIMESTAMP NULL COMMENT 'Время последнего сообщения',
	PRIMARY KEY (id),
	FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
	FOREIGN KEY (support_user_id) REFERENCES users (id) ON DELETE SET NULL,
	UNIQUE KEY unique_user_chat (user_id),
	INDEX idx_user_id (user_id),
	INDEX idx_support_user_id (support_user_id),
	INDEX idx_last_message_at (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сообщений в чатах
CREATE TABLE IF NOT EXISTS support_messages (
	id INT NOT NULL AUTO_INCREMENT,
	chat_id INT NOT NULL,
	sender_id INT NOT NULL COMMENT 'ID отправителя сообщения',
	message TEXT NOT NULL,
	is_read BOOLEAN NOT NULL DEFAULT FALSE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (chat_id) REFERENCES support_chats (id) ON DELETE CASCADE,
	FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
	INDEX idx_chat_id (chat_id),
	INDEX idx_sender_id (sender_id),
	INDEX idx_is_read (is_read),
	INDEX idx_created_at (created_at),
	INDEX idx_chat_read (chat_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица вложений к сообщениям
CREATE TABLE IF NOT EXISTS support_attachments (
	id INT NOT NULL AUTO_INCREMENT,
	message_id INT NOT NULL,
	filename VARCHAR(255) NOT NULL,
	file_path VARCHAR(500) NOT NULL,
	file_size INT NOT NULL COMMENT 'Размер файла в байтах',
	mime_type VARCHAR(100) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (message_id) REFERENCES support_messages (id) ON DELETE CASCADE,
	INDEX idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
