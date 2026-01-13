<?php
/**
 * API для работы с материалами
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/MaterialManager.php';
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

	$manager = new MaterialManager();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			$material = $manager->getById($id);
			if ($material) {
				echo json_encode($material);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Материал не найден']);
			}
		} else {
			echo json_encode($manager->getAll());
		}
		break;

	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$mark = $data['mark'] ?? '';
		$density = $data['density'] ?? 0;
		$price = $data['price'] ?? 0;

		if (empty($mark)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать марку материала']);
			exit;
		}

		$id = $manager->create($mark, $density, $price);
		echo json_encode(['success' => true, 'id' => $id]);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = $data['id'] ?? null;
		$mark = $data['mark'] ?? '';
		$density = $data['density'] ?? 0;
		$price = $data['price'] ?? 0;

		if (!$id || empty($mark)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID и марку материала']);
			exit;
		}

		$result = $manager->update($id, $mark, $density, $price);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Материал не найден']);
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
			echo json_encode(['error' => 'Материал не найден']);
		}
		break;

		default:
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'materials.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'materials.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
