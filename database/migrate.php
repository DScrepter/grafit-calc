<?php
/**
 * Скрипт миграции данных из SQLite в MySQL
 * 
 * Использование:
 * php migrate.php
 * 
 * ВАЖНО: Если миграция уже запускалась частично, сначала очистите таблицы:
 * mysql -u root -p cost_calculator_web < clear_tables.sql
 * или выполните SQL из файла clear_tables.sql через phpMyAdmin
 */

require_once __DIR__ . '/../backend/config/database.php';

// Путь к SQLite базе данных
// Если файл не найден по относительному пути, попробуем абсолютный
$sqlitePath = 'E:\cost-calc\data\database\cost_calculator.db';
if (!file_exists($sqlitePath)) {
	// Альтернативный путь для случаев, когда скрипт запускается из другой директории
	$sqlitePath = dirname(__DIR__, 2) . '/data/database/cost_calculator.db';
}

if (!file_exists($sqlitePath)) {
	die("Ошибка: SQLite база данных не найдена: $sqlitePath\n");
}

echo "Начинаем миграцию данных из SQLite в MySQL...\n";

// Подключаемся к SQLite
try {
	$sqlite = new PDO("sqlite:$sqlitePath");
	$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	echo "Подключение к SQLite успешно\n";
} catch (PDOException $e) {
	die("Ошибка подключения к SQLite: " . $e->getMessage() . "\n");
}

// Подключаемся к MySQL
try {
	$mysql = Database::getInstance();
	$mysqlConn = $mysql->getConnection();
	echo "Подключение к MySQL успешно\n";
} catch (Exception $e) {
	die("Ошибка подключения к MySQL: " . $e->getMessage() . "\n");
}

// Начинаем транзакцию
$mysqlConn->beginTransaction();

try {
	// 1. Миграция единиц измерения
	echo "\nМиграция единиц измерения...\n";
	$units = $sqlite->query("SELECT * FROM units ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	$unitMap = []; // Маппинг: старый ID => новый ID
	foreach ($units as $unit) {
		$oldId = $unit['id'];
		// Не указываем ID при вставке, чтобы MySQL сам сгенерировал новый
		$stmt = $mysqlConn->prepare("INSERT INTO units (name, created_at, updated_at) VALUES (?, ?, ?)");
		$stmt->execute([$unit['name'], $unit['created_at'], $unit['updated_at']]);
		$newId = $mysql->lastInsertId();
		$unitMap[$oldId] = $newId;
		echo "  - Единица: {$unit['name']} (старый ID: {$oldId} => новый ID: {$newId})\n";
	}
	echo "Мигрировано единиц: " . count($units) . "\n";

	// 2. Миграция материалов
	echo "\nМиграция материалов...\n";
	$materials = $sqlite->query("SELECT * FROM materials ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	$materialMap = []; // Маппинг: старый ID => новый ID
	foreach ($materials as $material) {
		$oldId = $material['id'];
		// Не указываем ID при вставке, чтобы MySQL сам сгенерировал новый
		$stmt = $mysqlConn->prepare("INSERT INTO materials (mark, density, price, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
		$stmt->execute([
			$material['mark'],
			$material['density'],
			$material['price'],
			$material['created_at'],
			$material['updated_at']
		]);
		$newId = $mysql->lastInsertId();
		$materialMap[$oldId] = $newId;
		echo "  - Материал: {$material['mark']} (старый ID: {$oldId} => новый ID: {$newId})\n";
	}
	echo "Мигрировано материалов: " . count($materials) . "\n";

	// 3. Миграция операций
	echo "\nМиграция операций...\n";
	$operations = $sqlite->query("SELECT * FROM operations ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	$operationMap = []; // Маппинг: старый ID => новый ID
	foreach ($operations as $operation) {
		$oldId = $operation['id'];
		// Преобразуем unit_id через маппинг
		$oldUnitId = $operation['unit_id'];
		$newUnitId = isset($unitMap[$oldUnitId]) ? $unitMap[$oldUnitId] : null;
		
		// Не указываем ID при вставке, чтобы MySQL сам сгенерировал новый
		$stmt = $mysqlConn->prepare("INSERT INTO operations (number, description, unit_id, cost, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->execute([
			$operation['number'],
			$operation['description'],
			$newUnitId,
			$operation['cost'],
			$operation['created_at'],
			$operation['updated_at']
		]);
		$newId = $mysql->lastInsertId();
		$operationMap[$oldId] = $newId;
		echo "  - Операция: {$operation['number']} - {$operation['description']} (старый ID: {$oldId} => новый ID: {$newId})\n";
	}
	echo "Мигрировано операций: " . count($operations) . "\n";

	// 4. Миграция типов изделий
	echo "\nМиграция типов изделий...\n";
	$productTypes = $sqlite->query("SELECT * FROM product_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	$productTypeMap = []; // Маппинг: старый ID => новый ID
	foreach ($productTypes as $type) {
		$oldId = $type['id'];
		// Не указываем ID при вставке, чтобы MySQL сам сгенерировал новый
		$stmt = $mysqlConn->prepare("INSERT INTO product_types (name, description, volume_formula, waste_formula, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->execute([
			$type['name'],
			$type['description'],
			$type['volume_formula'],
			$type['waste_formula'],
			$type['created_at'],
			$type['updated_at']
		]);
		$newId = $mysql->lastInsertId();
		$productTypeMap[$oldId] = $newId;
		echo "  - Тип изделия: {$type['name']} (старый ID: {$oldId} => новый ID: {$newId})\n";
	}
	echo "Мигрировано типов изделий: " . count($productTypes) . "\n";

	// 5. Миграция параметров типов изделий
	echo "\nМиграция параметров типов изделий...\n";
	$parameters = $sqlite->query("SELECT * FROM product_type_parameters ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	foreach ($parameters as $param) {
		$oldProductTypeId = $param['product_type_id'];
		// Используем новый ID типа изделия из маппинга
		if (!isset($productTypeMap[$oldProductTypeId])) {
			echo "  ПРЕДУПРЕЖДЕНИЕ: Тип изделия с ID {$oldProductTypeId} не найден в маппинге, пропускаем параметр\n";
			continue;
		}
		$newProductTypeId = $productTypeMap[$oldProductTypeId];
		
		// Не указываем ID при вставке
		$stmt = $mysqlConn->prepare("INSERT INTO product_type_parameters (product_type_id, name, label, unit, required, default_value, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$stmt->execute([
			$newProductTypeId,
			$param['name'],
			$param['label'],
			$param['unit'],
			$param['required'] ? 1 : 0,
			$param['default_value'],
			$param['sequence']
		]);
	}
	echo "Мигрировано параметров: " . count($parameters) . "\n";

	// 6. Миграция коэффициентов
	echo "\nМиграция коэффициентов...\n";
	$coefficients = $sqlite->query("SELECT * FROM coefficients ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
	foreach ($coefficients as $coef) {
		$oldId = $coef['id'];
		// Не указываем ID при вставке, чтобы MySQL сам сгенерировал новый
		$stmt = $mysqlConn->prepare("INSERT INTO coefficients (name, value, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
		$stmt->execute([
			$coef['name'],
			$coef['value'],
			$coef['description'],
			$coef['created_at'],
			$coef['updated_at']
		]);
		$newId = $mysql->lastInsertId();
		echo "  - Коэффициент: {$coef['name']} ({$coef['value']}%) (старый ID: {$oldId} => новый ID: {$newId})\n";
	}
	echo "Мигрировано коэффициентов: " . count($coefficients) . "\n";

	// Коммитим транзакцию
	$mysqlConn->commit();
	echo "\nМиграция успешно завершена!\n";
	echo "\nИтоги:\n";
	echo "  - Единиц измерения: " . count($units) . "\n";
	echo "  - Материалов: " . count($materials) . "\n";
	echo "  - Операций: " . count($operations) . "\n";
	echo "  - Типов изделий: " . count($productTypes) . "\n";
	echo "  - Параметров: " . count($parameters) . "\n";
	echo "  - Коэффициентов: " . count($coefficients) . "\n";

} catch (Exception $e) {
	$mysqlConn->rollBack();
	echo "\nОшибка миграции: " . $e->getMessage() . "\n";
	echo "Изменения отменены.\n";
	exit(1);
}
