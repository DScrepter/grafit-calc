<?php
/**
 * API для работы с сохраненными расчетами
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
	
	// Гости не имеют доступа к калькулятору
	if (!$auth->canAccessReferences()) {
		http_response_code(403);
		echo json_encode(['error' => 'Недостаточно прав доступа']);
		exit;
	}

	$db = Database::getInstance();
	$userId = $auth->getUserId();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'GET':
		$id = $_GET['id'] ?? null;
		if ($id) {
			// Получение одного расчета
			$calculation = $db->fetchOne(
				"SELECT c.*, m.mark as material_name, pt.name as product_type_name
				FROM calculations c
				LEFT JOIN materials m ON c.material_id = m.id
				LEFT JOIN product_types pt ON c.product_type_id = pt.id
				WHERE c.id = ?",
				[$id]
			);
			if ($calculation) {
				// Декодируем JSON поля
				if (isset($calculation['parameters'])) {
					$calculation['parameters'] = json_decode($calculation['parameters'], true);
				}
				if (isset($calculation['operations'])) {
					$calculation['operations'] = json_decode($calculation['operations'], true);
				}
				if (isset($calculation['result'])) {
					$calculation['result'] = json_decode($calculation['result'], true);
				}
				
				// Получаем лейблы параметров из типа изделия
				$productTypeId = $calculation['product_type_id'];
				$paramLabels = $db->fetchAll(
					"SELECT name, label FROM product_type_parameters WHERE product_type_id = ?",
					[$productTypeId]
				);
				$paramLabelMap = [];
				foreach ($paramLabels as $param) {
					$paramLabelMap[$param['name']] = $param['label'];
				}
				$calculation['parameter_labels'] = $paramLabelMap;
				
				echo json_encode($calculation, JSON_UNESCAPED_UNICODE);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Расчет не найден']);
			}
		} else {
			// Список расчетов с пагинацией
			$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
			$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
			$offset = ($page - 1) * $limit;

			// Получаем общее количество
			$total = $db->fetchOne(
				"SELECT COUNT(*) as count FROM calculations"
			)['count'];

			// Получаем список (проверяем наличие updated_at через информацию о колонках)
			$hasUpdatedAt = false;
			try {
				$columns = $db->fetchAll("SHOW COLUMNS FROM calculations LIKE 'updated_at'");
				$hasUpdatedAt = !empty($columns);
			} catch (Exception $e) {
				// Игнорируем ошибку
			}

			$updatedAtField = $hasUpdatedAt ? 'c.updated_at,' : '';
			$calculations = $db->fetchAll(
				"SELECT c.id, c.product_name, c.created_at, {$updatedAtField}
					m.mark as material_name, pt.name as product_type_name,
					JSON_EXTRACT(c.result, '$.total_cost_without_packaging') as total_cost
				FROM calculations c
				LEFT JOIN materials m ON c.material_id = m.id
				LEFT JOIN product_types pt ON c.product_type_id = pt.id
				ORDER BY c.created_at DESC
				LIMIT ? OFFSET ?",
				[$limit, $offset]
			);

			// Обрабатываем JSON поля
			foreach ($calculations as &$calc) {
				if (isset($calc['total_cost'])) {
					$calc['total_cost'] = floatval($calc['total_cost']);
				}
			}

			echo json_encode([
				'calculations' => $calculations,
				'total' => intval($total),
				'page' => $page,
				'limit' => $limit,
				'pages' => ceil($total / $limit)
			], JSON_UNESCAPED_UNICODE);
		}
		break;

	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$productName = trim($data['product_name'] ?? '');
		$materialId = isset($data['material_id']) ? intval($data['material_id']) : null;
		$productTypeId = isset($data['product_type_id']) ? intval($data['product_type_id']) : null;
		$parameters = $data['parameters'] ?? [];
		$operations = $data['operations'] ?? [];
		$result = $data['result'] ?? [];

		// Валидация
		if (empty($productName)) {
			http_response_code(400);
			echo json_encode(['error' => 'Название изделия обязательно для заполнения'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		if (!$materialId || !$productTypeId) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать материал и тип изделия'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Сохраняем JSON поля
		$parametersJson = json_encode($parameters, JSON_UNESCAPED_UNICODE);
		$operationsJson = json_encode($operations, JSON_UNESCAPED_UNICODE);
		$resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);

		$db->execute(
			"INSERT INTO calculations (user_id, product_name, material_id, product_type_id, parameters, operations, result)
			VALUES (?, ?, ?, ?, ?, ?, ?)",
			[$userId, $productName, $materialId, $productTypeId, $parametersJson, $operationsJson, $resultJson]
		);

		echo json_encode(['success' => true, 'id' => $db->lastInsertId()], JSON_UNESCAPED_UNICODE);
		break;

	case 'PUT':
		$data = json_decode(file_get_contents('php://input'), true);
		$id = isset($data['id']) ? intval($data['id']) : null;
		$productName = trim($data['product_name'] ?? '');
		$materialId = isset($data['material_id']) ? intval($data['material_id']) : null;
		$productTypeId = isset($data['product_type_id']) ? intval($data['product_type_id']) : null;
		$parameters = $data['parameters'] ?? [];
		$operations = $data['operations'] ?? [];
		$result = $data['result'] ?? [];

		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID расчета'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Валидация
		if (empty($productName)) {
			http_response_code(400);
			echo json_encode(['error' => 'Название изделия обязательно для заполнения'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		if (!$materialId || !$productTypeId) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать материал и тип изделия'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Проверяем, что расчет существует
		$existing = $db->fetchOne(
			"SELECT id FROM calculations WHERE id = ?",
			[$id]
		);

		if (!$existing) {
			http_response_code(404);
			echo json_encode(['error' => 'Расчет не найден'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Сохраняем JSON поля
		$parametersJson = json_encode($parameters, JSON_UNESCAPED_UNICODE);
		$operationsJson = json_encode($operations, JSON_UNESCAPED_UNICODE);
		$resultJson = json_encode($result, JSON_UNESCAPED_UNICODE);

		$db->execute(
			"UPDATE calculations 
			SET product_name = ?, material_id = ?, product_type_id = ?, 
				parameters = ?, operations = ?, result = ?
			WHERE id = ?",
			[$productName, $materialId, $productTypeId, $parametersJson, $operationsJson, $resultJson, $id]
		);

		echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
		break;

	case 'DELETE':
		$id = $_GET['id'] ?? null;
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Проверяем, что расчет существует
		$existing = $db->fetchOne(
			"SELECT id FROM calculations WHERE id = ?",
			[$id]
		);

		if (!$existing) {
			http_response_code(404);
			echo json_encode(['error' => 'Расчет не найден'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$result = $db->execute("DELETE FROM calculations WHERE id = ?", [$id]);
		if ($result) {
			echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Расчет не найден'], JSON_UNESCAPED_UNICODE);
		}
		break;

	default:
		http_response_code(405);
		echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'calculations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'calculations.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
