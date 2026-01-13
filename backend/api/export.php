<?php
/**
 * API для экспорта расчетов в PDF
 */

require_once __DIR__ . '/error_handler.php';
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

	// Формируем HTML для экспорта
	$html = generateExportHTML($calculation, $parameters, $operations, $result);

	// Возвращаем HTML (можно конвертировать в PDF через jsPDF на клиенте)
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

function generateExportHTML($calculation, $parameters, $operations, $result) {
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
			$html .= '<tr>
				<td>' . htmlspecialchars($key) . '</td>
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
