<?php
/**
 * API для экспорта расчетов в PDF
 */

require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Logger.php';

try {
	$auth = new Auth();
	$auth->requireAuth();
	
	// Гости не имеют доступа
	if (!$auth->canAccessReferences()) {
		http_response_code(403);
		echo json_encode(['error' => 'Недостаточно прав доступа']);
		exit;
	}

	$db = Database::getInstance();
	$userId = $auth->getUserId();
	$id = $_GET['id'] ?? null;
	$format = $_GET['format'] ?? 'html';
	
	// Проверяем, переданы ли данные расчета напрямую (для несохраненных расчетов)
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	$calculation = null;
	$parameters = [];
	$operations = [];
	$result = [];
	$paramLabelMap = []; // Инициализируем маппинг лейблов
	
	if ($method === 'POST' && $format === 'pdf') {
		// Получаем данные расчета из POST запроса
		$input = file_get_contents('php://input');
		$data = json_decode($input, true);
		
		if ($data && isset($data['calculation'])) {
			$calculation = $data['calculation'];
			$parameters = $data['parameters'] ?? [];
			$operations = $data['operations'] ?? [];
			$result = $data['result'] ?? [];
			
			// Если операции сохранены только с operation_id, получаем полную информацию из БД
			if (!empty($operations) && isset($operations[0]['operation_id']) && !isset($operations[0]['operation_number'])) {
				$operationIds = array_column($operations, 'operation_id');
				if (!empty($operationIds)) {
					$placeholders = implode(',', array_fill(0, count($operationIds), '?'));
					$dbOperations = $db->fetchAll(
						"SELECT id, number, description, cost FROM operations WHERE id IN ($placeholders)",
						$operationIds
					);
					
					// Создаем маппинг operation_id -> operation
					$operationMap = [];
					foreach ($dbOperations as $dbOp) {
						$operationMap[$dbOp['id']] = $dbOp;
					}
					
					// Объединяем данные из БД с сохраненными коэффициентами
					$fullOperations = [];
					foreach ($operations as $op) {
						$operationId = $op['operation_id'];
						$complexityCoefficient = $op['complexity_coefficient'] ?? 1.0;
						
						if (isset($operationMap[$operationId])) {
							$dbOp = $operationMap[$operationId];
							$operationCost = (float)$dbOp['cost'];
							$totalCost = $operationCost * $complexityCoefficient;
							
							$fullOperations[] = [
								'operation_id' => $operationId,
								'operation_number' => $dbOp['number'],
								'operation_description' => $dbOp['description'],
								'operation_cost' => $operationCost,
								'complexity_coefficient' => $complexityCoefficient,
								'total_cost' => $totalCost
							];
						}
					}
					$operations = $fullOperations;
				}
			}
			
			// Получаем лейблы параметров для POST запроса (несохраненные расчеты)
			if (isset($data['parameter_labels']) && is_array($data['parameter_labels'])) {
				// Если лейблы переданы в данных
				$paramLabelMap = $data['parameter_labels'];
			} elseif (isset($data['calculation']['product_type_id'])) {
				// Получаем лейблы из БД
				$productTypeId = $data['calculation']['product_type_id'];
				$paramLabels = $db->fetchAll(
					"SELECT name, label FROM product_type_parameters WHERE product_type_id = ?",
					[$productTypeId]
				);
				foreach ($paramLabels as $param) {
					$paramLabelMap[$param['name']] = $param['label'];
				}
			}
		} else {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо передать данные расчета'], JSON_UNESCAPED_UNICODE);
			exit;
		}
	} else {
		// Получаем расчет из базы данных
		if (!$id) {
			http_response_code(400);
			echo json_encode(['error' => 'Необходимо указать ID расчета'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Получаем расчет
		$calculation = $db->fetchOne(
			"SELECT c.*, m.mark as material_name, pt.name as product_type_name
			FROM calculations c
			LEFT JOIN materials m ON c.material_id = m.id
			LEFT JOIN product_types pt ON c.product_type_id = pt.id
			WHERE c.id = ? AND c.user_id = ?",
			[$id, $userId]
		);

		if (!$calculation) {
			http_response_code(404);
			echo json_encode(['error' => 'Расчет не найден'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		// Декодируем JSON поля
		$parameters = json_decode($calculation['parameters'], true) ?? [];
		$operations = json_decode($calculation['operations'], true) ?? [];
		$result = json_decode($calculation['result'], true) ?? [];
		
		// Если операции сохранены только с operation_id, получаем полную информацию из БД
		if (!empty($operations) && isset($operations[0]['operation_id']) && !isset($operations[0]['operation_number'])) {
			$operationIds = array_column($operations, 'operation_id');
			if (!empty($operationIds)) {
				$placeholders = implode(',', array_fill(0, count($operationIds), '?'));
				$dbOperations = $db->fetchAll(
					"SELECT id, number, description, cost FROM operations WHERE id IN ($placeholders)",
					$operationIds
				);
				
				// Создаем маппинг operation_id -> operation
				$operationMap = [];
				foreach ($dbOperations as $dbOp) {
					$operationMap[$dbOp['id']] = $dbOp;
				}
				
				// Объединяем данные из БД с сохраненными коэффициентами
				$fullOperations = [];
				foreach ($operations as $op) {
					$operationId = $op['operation_id'];
					$complexityCoefficient = $op['complexity_coefficient'] ?? 1.0;
					
					if (isset($operationMap[$operationId])) {
						$dbOp = $operationMap[$operationId];
						$operationCost = (float)$dbOp['cost'];
						$totalCost = $operationCost * $complexityCoefficient;
						
						$fullOperations[] = [
							'operation_id' => $operationId,
							'operation_number' => $dbOp['number'],
							'operation_description' => $dbOp['description'],
							'operation_cost' => $operationCost,
							'complexity_coefficient' => $complexityCoefficient,
							'total_cost' => $totalCost
						];
					}
				}
				$operations = $fullOperations;
			}
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
	}

	if ($format === 'pdf') {
		// Генерируем PDF на сервере
		try {
			require_once __DIR__ . '/../classes/PDFGenerator.php';
			$pdfGenerator = new PDFGenerator();
			$pdfGenerator->generateCalculationPDF($calculation, $parameters, $operations, $result, $paramLabelMap);
			
			$filename = ($calculation['product_name'] ?? 'calculation') . '.pdf';
			$filename = preg_replace('/[^a-zA-Zа-яА-Я0-9\s\-_\.]/u', '', $filename);
			$filename = mb_convert_encoding($filename, 'UTF-8', 'UTF-8');
			
			if (!headers_sent()) {
				header('Content-Type: application/pdf; charset=utf-8');
				header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
			}
			
			$pdfGenerator->output($filename);
			exit;
		} catch (Exception $e) {
			// Если TCPDF не установлен, возвращаем JSON для клиентской генерации
			$logger = Logger::getInstance();
			$logger->exception($e, [
				'endpoint' => 'export.php',
				'calculation_id' => $id ?? null,
				'note' => 'TCPDF не установлен, используем клиентскую генерацию'
			]);
			
			if (!headers_sent()) {
				header('Content-Type: application/json; charset=utf-8');
			}
			echo json_encode([
				'calculation' => $calculation,
				'parameters' => $parameters,
				'operations' => $operations,
				'result' => $result,
				'parameter_labels' => $paramLabelMap
			], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}
	
	// Формируем HTML для экспорта (для печати)
	$html = generateExportHTML($calculation, $parameters, $operations, $result, $paramLabelMap ?? []);

	// Возвращаем HTML
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=utf-8');
	}
	echo $html;

} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'export.php',
		'calculation_id' => $id ?? null,
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'export.php',
		'calculation_id' => $id ?? null,
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}

function generateExportHTML($calculation, $parameters, $operations, $result, $paramLabelMap = []) {
	$productName = htmlspecialchars($calculation['product_name']);
	$materialName = htmlspecialchars($calculation['material_name'] ?? '');
	$productTypeName = htmlspecialchars($calculation['product_type_name'] ?? '');
	$createdAt = date('d.m.Y H:i', strtotime($calculation['created_at']));

	$html = '<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Калькуляция: ' . $productName . '</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			color: #333;
		}
		.header {
			border-bottom: 2px solid #333;
			padding-bottom: 10px;
			margin-bottom: 20px;
		}
		.header h1 {
			margin: 0;
			font-size: 24px;
		}
		.info-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		.info-table td {
			padding: 8px;
			border: 1px solid #ddd;
		}
		.info-table td:first-child {
			background-color: #f5f5f5;
			font-weight: bold;
			width: 200px;
		}
		.section {
			margin-top: 30px;
		}
		.section-title {
			font-size: 18px;
			font-weight: bold;
			margin-bottom: 10px;
			border-bottom: 1px solid #333;
			padding-bottom: 5px;
		}
		.results-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}
		.results-table th,
		.results-table td {
			padding: 8px;
			border: 1px solid #ddd;
			text-align: left;
		}
		.results-table th {
			background-color: #f5f5f5;
			font-weight: bold;
		}
		.total {
			font-size: 18px;
			font-weight: bold;
			color: #000;
			margin-top: 20px;
			padding-top: 10px;
			border-top: 2px solid #333;
		}
		@media print {
			body { margin: 0; }
			.no-print { display: none; }
		}
	</style>
</head>
<body>
	<div class="header">
		<h1>Калькуляция себестоимости</h1>
		<p>Дата создания: ' . $createdAt . '</p>
	</div>

	<table class="info-table">
		<tr>
			<td>Название изделия</td>
			<td>' . $productName . '</td>
		</tr>
		<tr>
			<td>Материал</td>
			<td>' . $materialName . '</td>
		</tr>
		<tr>
			<td>Тип изделия</td>
			<td>' . $productTypeName . '</td>
		</tr>
	</table>';

	// Параметры изделия
	if (!empty($parameters)) {
		$html .= '<div class="section">
			<div class="section-title">Параметры изделия</div>
			<table class="results-table">
				<thead>
					<tr>
						<th>Параметр</th>
						<th>Значение</th>
					</tr>
				</thead>
				<tbody>';
		foreach ($parameters as $key => $value) {
			// Используем лейбл если есть, иначе имя параметра
			$label = $paramLabelMap[$key] ?? $key;
			$html .= '<tr>
				<td>' . htmlspecialchars($label) . '</td>
				<td>' . htmlspecialchars($value) . '</td>
			</tr>';
		}
		$html .= '</tbody></table></div>';
	}

	// Результаты расчета
	if (!empty($result)) {
		$html .= '<div class="section">
			<div class="section-title">Результаты расчета</div>
			<table class="results-table">
				<tr>
					<td>Объем заготовки</td>
					<td>' . number_format($result['workpiece_volume'] ?? 0, 2, '.', ' ') . ' мм³</td>
				</tr>
				<tr>
					<td>Объем изделия</td>
					<td>' . number_format($result['product_volume'] ?? 0, 2, '.', ' ') . ' мм³</td>
				</tr>
				<tr>
					<td>Объем отходов</td>
					<td>' . number_format($result['waste_volume'] ?? 0, 2, '.', ' ') . ' мм³</td>
				</tr>
				<tr>
					<td>Масса заготовки</td>
					<td>' . number_format($result['workpiece_mass'] ?? 0, 4, '.', ' ') . ' кг</td>
				</tr>
				<tr>
					<td>Масса изделия</td>
					<td>' . number_format($result['product_mass'] ?? 0, 4, '.', ' ') . ' кг</td>
				</tr>
				<tr>
					<td>Масса отходов</td>
					<td>' . number_format($result['waste_mass'] ?? 0, 4, '.', ' ') . ' кг</td>
				</tr>
				<tr>
					<td>Стоимость материала</td>
					<td>' . number_format($result['material_cost'] ?? 0, 2, '.', ' ') . ' руб</td>
				</tr>
				<tr>
					<td>Зарплата (операции)</td>
					<td>' . number_format($result['total_operations_cost'] ?? 0, 2, '.', ' ') . ' руб</td>
				</tr>';

		if (!empty($result['coefficients'])) {
			$html .= '<tr>
				<td colspan="2"><strong>Коэффициенты:</strong></td>
			</tr>';
			foreach ($result['coefficients'] as $coef) {
				$html .= '<tr>
					<td>' . htmlspecialchars($coef['name']) . ' (' . $coef['value'] . '%)</td>
					<td>' . number_format($coef['amount'] ?? 0, 2, '.', ' ') . ' руб</td>
				</tr>';
			}
			$html .= '<tr>
				<td>Итого коэффициенты</td>
				<td>' . number_format($result['coefficients_cost'] ?? 0, 2, '.', ' ') . ' руб</td>
			</tr>';
		}

		$html .= '</table>
		<div class="total">
			Общая себестоимость: ' . number_format($result['total_cost_without_packaging'] ?? 0, 2, '.', ' ') . ' руб
		</div>
		</div>';
	}

	// Операции
	if (!empty($operations)) {
		$html .= '<div class="section">
			<div class="section-title">Операции</div>
			<table class="results-table">
				<thead>
					<tr>
						<th>Номер</th>
						<th>Описание</th>
						<th>Коэф. сложности</th>
						<th>Стоимость</th>
					</tr>
				</thead>
				<tbody>';
		foreach ($operations as $op) {
			$html .= '<tr>
				<td>' . htmlspecialchars($op['operation_number'] ?? '') . '</td>
				<td>' . htmlspecialchars($op['operation_description'] ?? '') . '</td>
				<td>' . number_format($op['complexity_coefficient'] ?? 1, 2, '.', ' ') . '</td>
				<td>' . number_format($op['total_cost'] ?? 0, 2, '.', ' ') . ' руб</td>
			</tr>';
		}
		$html .= '</tbody></table></div>';
	}

	$html .= '</body></html>';

	return $html;
}
