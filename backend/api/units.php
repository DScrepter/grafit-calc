<?php
/**
 * API для работы с единицами измерения
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';
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

	$db = Database::getInstance();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			$unit = $db->fetchOne("SELECT * FROM units WHERE id = ?", [$id]);
			if ($unit) {
				echo json_encode($unit);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Единица измерения не найдена']);
			}
		} else {
			echo json_encode($db->fetchAll("SELECT * FROM units ORDER BY name"));
		}
		break;

	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$name = $data['name'] ?? '';

		if (empty($name)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать название единицы измерения']);
			exit;
		}

		$db->execute("INSERT INTO units (name) VALUES (?)", [$name]);
		echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = $data['id'] ?? null;
		$name = $data['name'] ?? '';

		if (!$id || empty($name)) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID и название']);
			exit;
		}

		$result = $db->execute("UPDATE units SET name = ? WHERE id = ?", [$name, $id]);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Единица измерения не найдена']);
		}
		break;

	case 'DELETE':
		$id = $_GET['id'] ?? null;
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID']);
			exit;
		}

		$result = $db->execute("DELETE FROM units WHERE id = ?", [$id]);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Единица измерения не найдена']);
		}
		break;

		default:
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'units.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'units.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
