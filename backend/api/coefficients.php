<?php
/**
 * API для работы с коэффициентами
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

	$fixedCoefficientNames = ['N', 'Kz_порог', 'Kz', 'K', 'M'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			$coefficient = $db->fetchOne("SELECT * FROM coefficients WHERE id = ?", [$id]);
			if ($coefficient) {
				echo json_encode($coefficient);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Коэффициент не найден']);
			}
		} else {
			echo json_encode($db->fetchAll("SELECT * FROM coefficients ORDER BY name"));
		}
		break;

	case 'POST':
		// Создание новых коэффициентов отключено — только фиксированный набор
		http_response_code(403);
		echo json_encode(['error' => 'Добавление коэффициентов отключено. Редактируйте существующие коэффициенты.'], JSON_UNESCAPED_UNICODE);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = $data['id'] ?? null;
		$value = $data['value'] ?? 0;
		$description = isset($data['description']) ? $data['description'] : null;

		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID']);
			exit;
		}

		$existing = $db->fetchOne("SELECT id, name FROM coefficients WHERE id = ?", [$id]);
		if (!$existing) {
			http_response_code(404);
			echo json_encode(['error' => 'Коэффициент не найден']);
			exit;
		}
		// Для фиксированных коэффициентов не меняем name, только value и description
		$name = $existing['name'];
		if (in_array($name, $fixedCoefficientNames)) {
			$result = $db->execute("UPDATE coefficients SET value = ?, description = ? WHERE id = ?",
				[$value, $description, $id]);
		} else {
			$name = $data['name'] ?? $name;
			if (empty($name)) {
				http_response_code(400);
				echo json_encode(['error' => 'Необходимо указать название']);
				exit;
			}
			$result = $db->execute("UPDATE coefficients SET name = ?, value = ?, description = ? WHERE id = ?",
				[$name, $value, $description, $id]);
		}
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Коэффициент не найден']);
		}
		break;

	case 'DELETE':
		$id = $_GET['id'] ?? null;
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID']);
			exit;
		}
		$row = $db->fetchOne("SELECT name FROM coefficients WHERE id = ?", [$id]);
		if (!$row) {
			http_response_code(404);
			echo json_encode(['error' => 'Коэффициент не найден']);
			exit;
		}
		if (in_array($row['name'], $fixedCoefficientNames)) {
			http_response_code(403);
			echo json_encode(['error' => 'Удаление системного коэффициента запрещено'], JSON_UNESCAPED_UNICODE);
			exit;
		}
		$result = $db->execute("DELETE FROM coefficients WHERE id = ?", [$id]);
		if ($result) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Коэффициент не найден']);
		}
		break;

		default:
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'coefficients.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'coefficients.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
