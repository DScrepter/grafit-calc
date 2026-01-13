<?php
/**
 * API для работы с чатом техподдержки
 */

// Защита от повторного подключения
if (defined('SUPPORT_API_LOADED')) {
	return;
}
define('SUPPORT_API_LOADED', true);

// Устанавливаем заголовки ПЕРВЫМ делом, до любых require
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}

// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Подключаем error_handler
require_once __DIR__ . '/error_handler.php';

// Подключаем классы
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/SupportChatManager.php';
require_once __DIR__ . '/../classes/Logger.php';

try {
	$auth = new Auth();
	$auth->requireAuth();
	
	$manager = new SupportChatManager();
	$method = $_SERVER['REQUEST_METHOD'];
	$action = $_GET['action'] ?? $_POST['action'] ?? '';

	$currentUserId = $auth->getUserId();
	$currentUserRole = $auth->getUserRole();
	$isSupport = $auth->isSupport();

	switch ($action) {
	case 'chats':
		// Получение списка чатов (только для поддержки)
		if (!$isSupport) {
			http_response_code(403);
			echo json_encode(['error' => 'Недостаточно прав доступа']);
			exit;
		}

		$chats = $manager->getAllChats($currentUserId);
		echo json_encode($chats, JSON_UNESCAPED_UNICODE);
		break;

	case 'my_chat':
		// Получение чата текущего пользователя
		$chat = $manager->getOrCreateChat($currentUserId);
		echo json_encode($chat, JSON_UNESCAPED_UNICODE);
		break;

	case 'messages':
		// Получение сообщений чата
		$chatId = $_GET['chat_id'] ?? null;
		$lastMessageId = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;
		
		if (!$chatId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан ID чата']);
			exit;
		}

		$chat = $manager->getChatById($chatId);
		if (!$chat) {
			http_response_code(404);
			echo json_encode(['error' => 'Чат не найден']);
			exit;
		}

		// Проверяем права доступа
		if (!$isSupport && $chat['user_id'] != $currentUserId) {
			http_response_code(403);
			echo json_encode(['error' => 'Нет доступа к этому чату']);
			exit;
		}

		// Если это поддержка и чат еще не назначен, назначаем
		if ($isSupport && !$chat['support_user_id']) {
			$manager->assignSupport($chatId, $currentUserId);
		}

		// Если указан last_message_id, возвращаем только новые сообщения
		if ($lastMessageId > 0) {
			$newMessages = $manager->getNewMessages($chatId, $lastMessageId);
			echo json_encode($newMessages, JSON_UNESCAPED_UNICODE);
		} else {
			// Обычный запрос - возвращаем все сообщения
			$messages = $manager->getMessages($chatId);
			echo json_encode($messages, JSON_UNESCAPED_UNICODE);
		}
		break;

	case 'send':
		// Отправка сообщения
		if ($method !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
			exit;
		}

		$data = json_decode(file_get_contents('php://input'), true);
		$chatId = $data['chat_id'] ?? null;
		$message = trim($data['message'] ?? '');
		$targetUserId = $data['user_id'] ?? null; // Для поддержки - ID пользователя, с которым чат

		if (!$isSupport && !$chatId) {
			// Для обычного пользователя создаем/получаем его чат
			$chat = $manager->getOrCreateChat($currentUserId);
			$chatId = $chat['id'];
		} elseif ($isSupport && $targetUserId) {
			// Для поддержки - получаем чат пользователя
			$chat = $manager->getOrCreateChat($targetUserId);
			$chatId = $chat['id'];
			
			// Назначаем поддержку на чат, если еще не назначена
			if (!$chat['support_user_id']) {
				$manager->assignSupport($chatId, $currentUserId);
			}
		}

		if (!$chatId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан чат']);
			exit;
		}

		$chat = $manager->getChatById($chatId);
		if (!$chat) {
			http_response_code(404);
			echo json_encode(['error' => 'Чат не найден']);
			exit;
		}

		// Проверяем права доступа
		if (!$isSupport && $chat['user_id'] != $currentUserId) {
			http_response_code(403);
			echo json_encode(['error' => 'Нет доступа к этому чату']);
			exit;
		}

		if (empty($message) && empty($data['attachments'])) {
			http_response_code(400);
			echo json_encode(['error' => 'Сообщение не может быть пустым']);
			exit;
		}

		$result = $manager->sendMessage($chatId, $currentUserId, $message, []);
		echo json_encode(['success' => true, 'message_id' => $result['message_id']], JSON_UNESCAPED_UNICODE);
		break;

	case 'upload':
		// Загрузка файла
		if ($method !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
			exit;
		}

		if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
			http_response_code(400);
			echo json_encode(['error' => 'Ошибка загрузки файла']);
			exit;
		}

		$file = $_FILES['file'];
		$maxSize = 10 * 1024 * 1024; // 10MB
		$allowedTypes = [
			'image/jpeg', 'image/png', 'image/gif', 'image/webp',
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'text/plain'
		];

		if ($file['size'] > $maxSize) {
			http_response_code(400);
			echo json_encode(['error' => 'Файл слишком большой (максимум 10MB)']);
			exit;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		if (!in_array($mimeType, $allowedTypes)) {
			http_response_code(400);
			echo json_encode(['error' => 'Недопустимый тип файла']);
			exit;
		}

		$chatId = $_POST['chat_id'] ?? null;
		$targetUserId = $_POST['user_id'] ?? null;
		$message = trim($_POST['message'] ?? '');

		if (!$isSupport && !$chatId) {
			$chat = $manager->getOrCreateChat($currentUserId);
			$chatId = $chat['id'];
		} elseif ($isSupport && $targetUserId) {
			$chat = $manager->getOrCreateChat($targetUserId);
			$chatId = $chat['id'];
			if (!$chat['support_user_id']) {
				$manager->assignSupport($chatId, $currentUserId);
			}
		}

		if (!$chatId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан чат']);
			exit;
		}

		$chat = $manager->getChatById($chatId);
		if (!$chat) {
			http_response_code(404);
			echo json_encode(['error' => 'Чат не найден']);
			exit;
		}

		if (!$isSupport && $chat['user_id'] != $currentUserId) {
			http_response_code(403);
			echo json_encode(['error' => 'Нет доступа к этому чату']);
			exit;
		}

		$attachmentData = [
			'filename' => $file['name'],
			'size' => $file['size'],
			'mime_type' => $mimeType,
			'temp_path' => $file['tmp_name']
		];

		$result = $manager->sendMessage($chatId, $currentUserId, $message, [$attachmentData]);
		echo json_encode([
			'success' => true,
			'message_id' => $result['message_id'],
			'attachment_id' => $result['attachments'][0] ?? null
		], JSON_UNESCAPED_UNICODE);
		break;

	case 'unread_count':
		// Получение количества непрочитанных сообщений
		try {
			$userId = $_GET['user_id'] ?? null;
			
			if ($isSupport && $userId) {
				// Для поддержки - количество непрочитанных от конкретного пользователя
				$count = $manager->getUnreadCountFromUser($userId);
			} else {
				// Для текущего пользователя
				$count = $manager->getUnreadCount($currentUserId, $isSupport);
			}
			
			echo json_encode(['count' => (int)$count]);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Ошибка получения количества сообщений', 'count' => 0]);
		}
		break;

	case 'mark_read':
		// Пометить сообщения как прочитанные
		if ($method !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
			exit;
		}

		$data = json_decode(file_get_contents('php://input'), true);
		$chatId = $data['chat_id'] ?? null;
		$targetUserId = $data['user_id'] ?? null;

		if ($isSupport && $targetUserId) {
			$chat = $manager->getUserChat($targetUserId);
			if ($chat) {
				$chatId = $chat['id'];
			}
		} elseif (!$chatId) {
			$chat = $manager->getOrCreateChat($currentUserId);
			$chatId = $chat['id'];
		}

		if (!$chatId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан чат']);
			exit;
		}

		$manager->markAsRead($chatId, $currentUserId);
		echo json_encode(['success' => true]);
		break;

	case 'attachment':
		// Получение файла вложения
		$attachmentId = $_GET['id'] ?? null;
		if (!$attachmentId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан ID вложения']);
			exit;
		}

		$attachment = $manager->getAttachment($attachmentId);
		if (!$attachment) {
			http_response_code(404);
			echo json_encode(['error' => 'Вложение не найдено']);
			exit;
		}

		// Проверяем права доступа
		require_once __DIR__ . '/../config/database.php';
		$db = Database::getInstance();
		$message = $db->fetchOne(
			"SELECT sm.*, sc.user_id as chat_user_id 
			FROM support_messages sm 
			INNER JOIN support_chats sc ON sc.id = sm.chat_id 
			WHERE sm.id = ?",
			[$attachment['message_id']]
		);

		if (!$message) {
			http_response_code(404);
			echo json_encode(['error' => 'Сообщение не найдено']);
			exit;
		}

		$hasAccess = false;
		if ($isSupport) {
			$hasAccess = true;
		} elseif ($message['chat_user_id'] == $currentUserId) {
			$hasAccess = true;
		}

		if (!$hasAccess) {
			http_response_code(403);
			echo json_encode(['error' => 'Нет доступа к этому файлу']);
			exit;
		}

		$filePath = $manager->getAttachmentPath($attachmentId);
		if (!$filePath || !file_exists($filePath)) {
			http_response_code(404);
			echo json_encode(['error' => 'Файл не найден']);
			exit;
		}

		header('Content-Type: ' . $attachment['mime_type']);
		header('Content-Disposition: attachment; filename="' . htmlspecialchars($attachment['filename']) . '"');
		header('Content-Length: ' . filesize($filePath));
		readfile($filePath);
		exit;

	default:
		http_response_code(400);
		echo json_encode(['error' => 'Неизвестное действие']);
		break;
	}
} catch (Exception $e) {
	// Убеждаемся, что заголовки установлены
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(500);
	}
	
	try {
		$logger = Logger::getInstance();
		$logger->exception($e, [
			'endpoint' => 'support.php',
			'method' => $_SERVER['REQUEST_METHOD'] ?? '',
			'action' => $action ?? ''
		]);
	} catch (Exception $logError) {
		// Игнорируем ошибки логирования
	}
	
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	// Убеждаемся, что заголовки установлены
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(500);
	}
	
	try {
		$logger = Logger::getInstance();
		$logger->exception($e, [
			'endpoint' => 'support.php',
			'method' => $_SERVER['REQUEST_METHOD'] ?? '',
			'action' => $action ?? ''
		]);
	} catch (Exception $logError) {
		// Игнорируем ошибки логирования
	}
	
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
