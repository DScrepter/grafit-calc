<?php
/**
 * API для управления обновлениями базы данных
 * Доступно только для супер-администраторов
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/MigrationManager.php';
require_once __DIR__ . '/../classes/Logger.php';

try {
	$auth = new Auth();
	$auth->requireAuth();
	
	// Только супер-администраторы могут управлять обновлениями
	if (!$auth->isSuperAdmin()) {
		http_response_code(403);
		echo json_encode(['error' => 'Доступ разрешен только супер-администраторам'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$manager = new MigrationManager();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		// Получение статуса обновлений
		$status = $manager->getStatus();
		echo json_encode($status, JSON_UNESCAPED_UNICODE);
		break;

	case 'POST':
		// Применение обновлений
		$data = json_decode(file_get_contents('php://input'), true);
		$migrationName = $data['migration'] ?? null;

		if ($migrationName) {
			// Применение конкретного обновления
			try {
				$manager->applyMigration($migrationName);
				echo json_encode([
					'success' => true,
					'message' => "Обновление {$migrationName} успешно применено"
				], JSON_UNESCAPED_UNICODE);
			} catch (Exception $e) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'error' => $e->getMessage()
				], JSON_UNESCAPED_UNICODE);
			}
		} else {
			// Применение всех ожидающих обновлений
			$result = $manager->applyPendingMigrations();
			if ($result['success']) {
				echo json_encode([
					'success' => true,
					'applied' => $result['applied'],
					'message' => 'Все обновления успешно применены'
				], JSON_UNESCAPED_UNICODE);
			} else {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'applied' => $result['applied'],
					'errors' => $result['errors'],
					'message' => 'Некоторые обновления не удалось применить'
				], JSON_UNESCAPED_UNICODE);
			}
		}
		break;

	default:
		http_response_code(405);
		echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'migrations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'migrations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
