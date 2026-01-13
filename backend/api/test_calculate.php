<?php
/**
 * Тестовый скрипт для проверки расчета
 * Удалите этот файл после проверки
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Calculator.php';
require_once __DIR__ . '/../classes/MaterialManager.php';
require_once __DIR__ . '/../classes/ProductTypeManager.php';
require_once __DIR__ . '/../classes/Logger.php';

$logger = Logger::getInstance();

// Обработчик ошибок для логирования
set_error_handler(function($severity, $message, $file, $line) use ($logger) {
	$logger->fatalError($message, $file, $line, '');
	return false; // Продолжаем стандартную обработку
}, E_ALL);

// Обработчик фатальных ошибок
register_shutdown_function(function() use ($logger) {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		$logger->fatalError($error['message'], $error['file'], $error['line'], '');
	}
});

try {
	// Получаем первый доступный материал
	$materialManager = new MaterialManager();
	$materials = $materialManager->getAll();
	if (empty($materials)) {
		throw new Exception("В базе данных нет материалов! Добавьте материалы через API или интерфейс.");
	}
	$materialId = $materials[0]['id'];
	$material = $materials[0];
	
	// Получаем первый доступный тип изделия
	$productTypeManager = new ProductTypeManager();
	$productTypes = $productTypeManager->getAll();
	if (empty($productTypes)) {
		throw new Exception("В базе данных нет типов изделий! Добавьте типы изделий через API или интерфейс.");
	}
	$productTypeId = $productTypes[0]['id'];
	$productType = $productTypes[0];
	
	echo "<h2>Тестовый расчет</h2>";
	echo "<p><strong>Материал:</strong> {$material['mark']} (ID: {$materialId})</p>";
	echo "<p><strong>Тип изделия:</strong> {$productType['name']} (ID: {$productTypeId})</p>";
	
	// Формируем параметры на основе параметров типа изделия
	$parameters = [];
	foreach ($productType['parameters'] as $param) {
		$value = $param['default_value'] ?? 100;
		// Устанавливаем тестовые значения для разных типов параметров
		if ($param['name'] === 'length') $value = 100;
		if ($param['name'] === 'width') $value = 50;
		if ($param['name'] === 'height') $value = 20;
		if ($param['name'] === 'outer_diameter') $value = 100;
		if ($param['name'] === 'inner_diameter') $value = 50;
		if ($param['name'] === 'diameter') $value = 100;
		if ($param['name'] === 'thickness') $value = 10;
		$parameters[$param['name']] = $value;
	}
	
	$calculator = new Calculator();
	
	// Тестовые данные
	$result = $calculator->calculate(
		'Тестовое изделие',
		$materialId,
		$productTypeId,
		$parameters,
		[] // operations
	);
	
	echo "<h3>Результат расчета:</h3>";
	echo "<pre>";
	print_r($result);
	echo "</pre>";
	
} catch (Throwable $e) {
	// Логируем ошибку (ловим и Exception, и Error)
	$logger->exception($e, [
		'test_script' => true,
		'material_id' => $materialId ?? null,
		'product_type_id' => $productTypeId ?? null
	]);
	
	echo "<h2 style='color: red;'>Ошибка</h2>";
	echo "<p><strong>Тип:</strong> " . get_class($e) . "</p>";
	echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
	echo "<p><strong>Файл:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
	echo "<p><strong>Строка:</strong> " . $e->getLine() . "</p>";
	echo "<h3>Трассировка:</h3>";
	echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
	echo "<hr>";
	echo "<p><strong>Ошибка записана в лог файл.</strong> Проверьте файлы в директории <code>logs/</code></p>";
}
