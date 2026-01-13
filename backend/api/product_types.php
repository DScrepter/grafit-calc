<?php
/**
 * API для работы с типами изделий
 */

// Устанавливаем заголовки в самом начале, до любых операций
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}

// Обработка ошибок должна быть подключена первой
require_once __DIR__ . '/error_handler.php';

try {
	require_once __DIR__ . '/../classes/Auth.php';
	require_once __DIR__ . '/../classes/ProductTypeManager.php';
	require_once __DIR__ . '/../classes/Logger.php';

	$auth = new Auth();
	
	// Проверяем авторизацию
	if (!$auth->isLoggedIn()) {
		http_response_code(401);
		echo json_encode(['error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	
	// Гости не имеют доступа к справочникам
	if (!$auth->canAccessReferences()) {
		http_response_code(403);
		echo json_encode(['error' => 'Недостаточно прав доступа'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$manager = new ProductTypeManager();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			$type = $manager->getById($id);
			if ($type) {
				echo json_encode($type, JSON_UNESCAPED_UNICODE);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Тип изделия не найден'], JSON_UNESCAPED_UNICODE);
			}
		} else {
			echo json_encode($manager->getAll(), JSON_UNESCAPED_UNICODE);
		}
		break;

	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$name = $data['name'] ?? '';
		$description = $data['description'] ?? '';
		$volumeFormula = $data['volume_formula'] ?? '';
		$wasteFormula = $data['waste_formula'] ?? '';
		$parameters = $data['parameters'] ?? [];

		if (empty($name) || empty($volumeFormula) || empty($wasteFormula)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать название, формулу объема и формулу отходов'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$id = $manager->create($name, $description, $volumeFormula, $wasteFormula, $parameters);
		echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = $data['id'] ?? null;
		$name = $data['name'] ?? '';
		$description = $data['description'] ?? '';
		$volumeFormula = $data['volume_formula'] ?? '';
		$wasteFormula = $data['waste_formula'] ?? '';
		$parameters = $data['parameters'] ?? [];

		if (!$id || empty($name) || empty($volumeFormula) || empty($wasteFormula)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID, название, формулу объема и формулу отходов'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$result = $manager->update($id, $name, $description, $volumeFormula, $wasteFormula, $parameters);
		if ($result) {
			echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Тип изделия не найден'], JSON_UNESCAPED_UNICODE);
		}
		break;

	case 'DELETE':
		$id = $_GET['id'] ?? null;
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$result = $manager->delete($id);
		if ($result) {
			echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Тип изделия не найден'], JSON_UNESCAPED_UNICODE);
		}
		break;

	default:
		http_response_code(405);
		echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'product_types.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'product_types.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
