<?php
/**
 * Менеджер для работы с чатом техподдержки
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Logger.php';

class SupportChatManager {
	private $db;
	private $logger;
	private $uploadsPath;

	public function __construct() {
		$this->db = Database::getInstance();
		$this->logger = Logger::getInstance();
		$this->uploadsPath = __DIR__ . '/../../uploads/support';
		
		// Создаем директорию для загрузок, если её нет
		if (!is_dir($this->uploadsPath)) {
			mkdir($this->uploadsPath, 0755, true);
		}
	}

	/**
	 * Получает или создает чат для пользователя
	 */
	public function getOrCreateChat($userId) {
		$chat = $this->db->fetchOne(
			"SELECT * FROM support_chats WHERE user_id = ?",
			[$userId]
		);

		if (!$chat) {
			$this->db->execute(
				"INSERT INTO support_chats (user_id) VALUES (?)",
				[$userId]
			);
			$chatId = $this->db->lastInsertId();
			$chat = $this->db->fetchOne(
				"SELECT * FROM support_chats WHERE id = ?",
				[$chatId]
			);
		}

		return $chat;
	}

	/**
	 * Получает чат по ID
	 */
	public function getChatById($chatId) {
		return $this->db->fetchOne(
			"SELECT * FROM support_chats WHERE id = ?",
			[$chatId]
		);
	}

	/**
	 * Получает чат пользователя
	 */
	public function getUserChat($userId) {
		return $this->db->fetchOne(
			"SELECT * FROM support_chats WHERE user_id = ?",
			[$userId]
		);
	}

	/**
	 * Получает список всех чатов (для поддержки)
	 */
	public function getAllChats($supportUserId = null) {
		$sql = "
			SELECT 
				sc.*,
				u.id as user_id,
				u.username,
				u.email,
				u.first_name,
				u.last_name,
				(
					SELECT COUNT(*) 
					FROM support_messages sm 
					INNER JOIN users su ON su.id = sm.sender_id
					WHERE sm.chat_id = sc.id 
					AND sm.is_read = FALSE 
					AND sm.sender_id != ?
					AND (su.role != 'support' AND su.role != 'super_admin' AND su.role != 'admin')
				) as unread_count
			FROM support_chats sc
			INNER JOIN users u ON u.id = sc.user_id
		";
		
		$params = [$supportUserId ?? 0];
		
		if ($supportUserId) {
			$sql .= " WHERE sc.support_user_id = ? OR sc.support_user_id IS NULL";
			$params[] = $supportUserId;
		}
		
		$sql .= " ORDER BY sc.last_message_at DESC, sc.created_at DESC";
		
		return $this->db->fetchAll($sql, $params);
	}

	/**
	 * Назначает сотрудника поддержки на чат
	 */
	public function assignSupport($chatId, $supportUserId) {
		return $this->db->execute(
			"UPDATE support_chats SET support_user_id = ? WHERE id = ?",
			[$supportUserId, $chatId]
		);
	}

	/**
	 * Отправляет сообщение
	 */
	public function sendMessage($chatId, $senderId, $message, $attachments = []) {
		$this->db->beginTransaction();
		try {
			// Вставляем сообщение
			$this->db->execute(
				"INSERT INTO support_messages (chat_id, sender_id, message) VALUES (?, ?, ?)",
				[$chatId, $senderId, $message]
			);
			$messageId = $this->db->lastInsertId();

			// Обрабатываем вложения
			$savedAttachments = [];
			foreach ($attachments as $attachment) {
				$attachmentId = $this->saveAttachment($messageId, $attachment);
				if ($attachmentId) {
					$savedAttachments[] = $attachmentId;
				}
			}

			// Обновляем время последнего сообщения в чате
			$this->db->execute(
				"UPDATE support_chats SET last_message_at = NOW() WHERE id = ?",
				[$chatId]
			);

			// Если сообщение от пользователя, помечаем как непрочитанное
			// Если от поддержки - помечаем все сообщения пользователя как прочитанные
			$chat = $this->getChatById($chatId);
			$sender = $this->db->fetchOne("SELECT role FROM users WHERE id = ?", [$senderId]);
			
			if ($sender && ($sender['role'] === 'support' || $sender['role'] === 'super_admin' || $sender['role'] === 'admin')) {
				// Сообщение от поддержки - помечаем все непрочитанные сообщения пользователя как прочитанные
				$this->db->execute(
					"UPDATE support_messages SET is_read = TRUE WHERE chat_id = ? AND sender_id != ? AND is_read = FALSE",
					[$chatId, $senderId]
				);
			}

			$this->db->commit();
			
			return [
				'message_id' => $messageId,
				'attachments' => $savedAttachments
			];
		} catch (Exception $e) {
			$this->db->rollBack();
			$this->logger->exception($e, ['action' => 'send_message', 'chat_id' => $chatId]);
			throw $e;
		}
	}

	/**
	 * Сохраняет вложение
	 */
	private function saveAttachment($messageId, $fileData) {
		$filename = $fileData['filename'] ?? 'file';
		$fileSize = $fileData['size'] ?? 0;
		$mimeType = $fileData['mime_type'] ?? 'application/octet-stream';
		$tempPath = $fileData['temp_path'] ?? null;

		if (!$tempPath || !file_exists($tempPath)) {
			return null;
		}

		// Генерируем уникальное имя файла
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		$uniqueName = uniqid('att_', true) . '_' . time() . '.' . $extension;
		$filePath = $this->uploadsPath . '/' . $uniqueName;

		// Перемещаем файл
		if (!move_uploaded_file($tempPath, $filePath)) {
			// Если move_uploaded_file не сработал (файл уже перемещен), копируем
			if (!copy($tempPath, $filePath)) {
				return null;
			}
		}

		// Сохраняем информацию о файле в БД
		$this->db->execute(
			"INSERT INTO support_attachments (message_id, filename, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?)",
			[$messageId, $filename, 'uploads/support/' . $uniqueName, $fileSize, $mimeType]
		);

		return $this->db->lastInsertId();
	}

	/**
	 * Получает сообщения чата
	 */
	public function getMessages($chatId, $limit = 100, $offset = 0) {
		$messages = $this->db->fetchAll(
			"SELECT 
				sm.*,
				u.username,
				u.first_name,
				u.last_name,
				u.role as sender_role
			FROM support_messages sm
			INNER JOIN users u ON u.id = sm.sender_id
			WHERE sm.chat_id = ?
			ORDER BY sm.created_at ASC
			LIMIT ? OFFSET ?",
			[$chatId, $limit, $offset]
		);

		// Загружаем вложения для каждого сообщения
		foreach ($messages as &$message) {
			$message['attachments'] = $this->getMessageAttachments($message['id']);
		}

		return $messages;
	}

	/**
	 * Получает новые сообщения после указанного ID (для Long Polling)
	 */
	public function getNewMessages($chatId, $lastMessageId = 0) {
		$messages = $this->db->fetchAll(
			"SELECT 
				sm.*,
				u.username,
				u.first_name,
				u.last_name,
				u.role as sender_role
			FROM support_messages sm
			INNER JOIN users u ON u.id = sm.sender_id
			WHERE sm.chat_id = ? AND sm.id > ?
			ORDER BY sm.created_at ASC",
			[$chatId, $lastMessageId]
		);

		// Загружаем вложения для каждого сообщения
		foreach ($messages as &$message) {
			$message['attachments'] = $this->getMessageAttachments($message['id']);
		}

		return $messages;
	}

	/**
	 * Получает вложения сообщения
	 */
	public function getMessageAttachments($messageId) {
		return $this->db->fetchAll(
			"SELECT id, filename, file_path, file_size, mime_type, created_at 
			FROM support_attachments 
			WHERE message_id = ?",
			[$messageId]
		);
	}

	/**
	 * Получает количество непрочитанных сообщений для пользователя
	 */
	public function getUnreadCount($userId, $isSupport = false) {
		try {
			if ($isSupport) {
				// Для поддержки - считаем непрочитанные сообщения от пользователей во всех чатах
				$result = $this->db->fetchOne(
					"SELECT COUNT(*) as count 
					FROM support_messages sm
					INNER JOIN support_chats sc ON sc.id = sm.chat_id
					INNER JOIN users u ON u.id = sm.sender_id
					WHERE sm.is_read = FALSE 
					AND (u.role != 'support' AND u.role != 'super_admin' AND u.role != 'admin')
					AND (sc.support_user_id = ? OR sc.support_user_id IS NULL)",
					[$userId]
				);
				return isset($result['count']) ? (int)$result['count'] : 0;
			} else {
				// Для обычного пользователя - считаем непрочитанные сообщения в его чате
				$chat = $this->getUserChat($userId);
				if (!$chat) {
					return 0;
				}
				
				$result = $this->db->fetchOne(
					"SELECT COUNT(*) as count 
					FROM support_messages 
					WHERE chat_id = ? 
					AND sender_id != ? 
					AND is_read = FALSE",
					[$chat['id'], $userId]
				);
				return isset($result['count']) ? (int)$result['count'] : 0;
			}
		} catch (Exception $e) {
			$this->logger->error('Ошибка получения количества непрочитанных сообщений', [
				'user_id' => $userId,
				'is_support' => $isSupport,
				'error' => $e->getMessage()
			]);
			return 0;
		}
	}

	/**
	 * Получает количество непрочитанных сообщений от конкретного пользователя (для поддержки)
	 */
	public function getUnreadCountFromUser($userId) {
		try {
			$chat = $this->getUserChat($userId);
			if (!$chat) {
				return 0;
			}

			$result = $this->db->fetchOne(
				"SELECT COUNT(*) as count 
				FROM support_messages 
				WHERE chat_id = ? 
				AND sender_id = ? 
				AND is_read = FALSE",
				[$chat['id'], $userId]
			);
			return isset($result['count']) ? (int)$result['count'] : 0;
		} catch (Exception $e) {
			$this->logger->error('Ошибка получения количества непрочитанных сообщений от пользователя', [
				'user_id' => $userId,
				'error' => $e->getMessage()
			]);
			return 0;
		}
	}

	/**
	 * Помечает сообщения как прочитанные
	 */
	public function markAsRead($chatId, $userId) {
		return $this->db->execute(
			"UPDATE support_messages 
			SET is_read = TRUE 
			WHERE chat_id = ? 
			AND sender_id != ? 
			AND is_read = FALSE",
			[$chatId, $userId]
		);
	}

	/**
	 * Получает путь к файлу вложения
	 */
	public function getAttachmentPath($attachmentId) {
		$attachment = $this->db->fetchOne(
			"SELECT file_path FROM support_attachments WHERE id = ?",
			[$attachmentId]
		);

		if (!$attachment) {
			return null;
		}

		$fullPath = __DIR__ . '/../../' . $attachment['file_path'];
		if (!file_exists($fullPath)) {
			return null;
		}

		return $fullPath;
	}

	/**
	 * Получает информацию о вложении
	 */
	public function getAttachment($attachmentId) {
		return $this->db->fetchOne(
			"SELECT * FROM support_attachments WHERE id = ?",
			[$attachmentId]
		);
	}
}
