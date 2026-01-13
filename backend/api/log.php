<?php
/**
 * API для логирования ошибок с фронтенда
 */

// Защита от повторного подключения
if (defined('LOG_API_LOADED')) {
	return;
}
define('LOG_API_LOADED', true);

header('Content-Type: application/json; charset=utf-8');

// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../classes/Logger.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Метод не поддерживается']);
	exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
	http_response_code(400);
	echo json_encode(['error' => 'Неверный формат данных']);
	exit;
}

$logger = Logger::getInstance();

$level = $data['level'] ?? 'ERROR';
$message = $data['message'] ?? 'Неизвестная ошибка';
$context = $data['context'] ?? [];

// Добавляем дополнительную информацию
$context['url'] = $data['url'] ?? $_SERVER['REQUEST_URI'] ?? '';
$context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$context['referer'] = $_SERVER['HTTP_REFERER'] ?? '';
$context['timestamp'] = date('Y-m-d H:i:s');

// Логируем в зависимости от уровня
switch (strtoupper($level)) {
	case 'ERROR':
	case 'EXCEPTION':
		$logger->error($message, $context);
		break;
	case 'WARNING':
		$logger->warning($message, $context);
		break;
	case 'INFO':
		$logger->info($message, $context);
		break;
	default:
		$logger->error($message, $context);
}

echo json_encode(['success' => true]);
