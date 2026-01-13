<?php
/**
 * API для работы с операциями
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/OperationManager.php';
require_once __DIR__ . '/../classes/Logger.php';

try {
	$auth = new Auth();
	$auth->requireAuth();
	
	// Гости не имеют доступа к справочникам
	if (!$auth->canAccessReferences()) {
		http_response_code(403);
		echo json_encode(['error' => 'Недостаточно прав доступа']);
		exit;
	}

	$manager = new OperationManager();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			$operation = $manager->getById($id);
			if ($operation) {
				echo json_encode($operation);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Операция не найдена']);
			}
		} else {
			echo json_encode($manager->getAll());
		}
		break;

	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$number = $data['number'] ?? '';
		$description = $data['description'] ?? '';
		$unitId = $data['unit_id'] ?? null;
		$cost = $data['cost'] ?? 0;

		if (empty($number) || empty($description)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать номер и описание операции']);
			exit;
		}

		$id = $manager->create($number, $description, $unitId, $cost);
		echo json_encode(['success' => true, 'id' => $id]);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = $data['id'] ?? null;
		$number = $data['number'] ?? '';
		$description = $data['description'] ?? '';
		$unitId = $data['unit_id'] ?? null;
		$cost = $data['cost'] ?? 0;

		if (!$id || empty($number) || empty($description)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID, номер и описание операции']);
			exit;
		}

		$result = $manager->update($id, $number, $description, $unitId, $cost);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Операция не найдена']);
		}
		break;

	case 'DELETE':
		$id = $_GET['id'] ?? null;
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID']);
			exit;
		}

		$result = $manager->delete($id);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Операция не найдена']);
		}
		break;

		default:
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'operations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'operations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
