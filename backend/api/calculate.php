<?php
/**
 * API для расчета себестоимости
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Calculator.php';
require_once __DIR__ . '/../classes/Logger.php';

$auth = new Auth();
$auth->requireAuth();

// Гости не имеют доступа к калькулятору
if (!$auth->canAccessReferences()) {
	http_response_code(403);
	echo json_encode(['error' => 'Недостаточно прав доступа']);
	exit;
}

$calculator = new Calculator();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Метод не поддерживается']);
	exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
	http_response_code(400);
	echo json_encode(['error' => 'Ошибка парсинга JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
	exit;
}

$productName = $data['product_name'] ?? '';
$materialId = isset($data['material_id']) ? (int)$data['material_id'] : null;
$productTypeId = isset($data['product_type_id']) ? (int)$data['product_type_id'] : null;
$parameters = $data['parameters'] ?? [];
$operations = $data['operations'] ?? [];
$quantity = isset($data['quantity']) ? max(1, (int)$data['quantity']) : 5;

if (empty($productName) || !$materialId || !$productTypeId) {
	http_response_code(400);
	echo json_encode(['error' => 'Необходимо указать название изделия, материал и тип изделия'], JSON_UNESCAPED_UNICODE);
	exit;
}

try {
	$result = $calculator->calculate($productName, $materialId, $productTypeId, $parameters, $operations, $quantity);
	echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'calculate.php',
		'product_name' => $productName,
		'material_id' => $materialId,
		'product_type_id' => $productTypeId,
	]);
	http_response_code(400);
	echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'calculate.php',
		'product_name' => $productName,
		'material_id' => $materialId,
		'product_type_id' => $productTypeId,
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка выполнения: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
