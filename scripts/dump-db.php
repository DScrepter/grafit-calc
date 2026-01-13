<?php
/**
 * PHP скрипт для создания дампа базы данных
 * Использование: php scripts/dump-db.php [output_file.sql]
 * 
 * Этот скрипт использует конфигурацию из config/config.php
 * и создает SQL дамп базы данных
 */

require_once __DIR__ . '/../backend/config/config.php';

$config = require __DIR__ . '/../backend/config/config.php';

// Параметры подключения
$dbHost = $config['database']['host'];
$dbPort = $config['database']['port'];
$dbName = $config['database']['dbname'];
$dbUser = $config['database']['username'];
$dbPass = $config['database']['password'];

// Имя выходного файла
$outputFile = $argv[1] ?? __DIR__ . '/../database/dump_' . date('Ymd_His') . '.sql';

// Создаем директорию если не существует
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
	mkdir($outputDir, 0755, true);
}

echo "Создание дампа базы данных...\n";
echo "База данных: $dbName\n";
echo "Хост: $dbHost:$dbPort\n";
echo "Выходной файл: $outputFile\n\n";

// Пытаемся использовать mysqldump через exec
$mysqldumpCmd = 'mysqldump';
$passwordArg = !empty($dbPass) ? "-p" . escapeshellarg($dbPass) : "";

$command = sprintf(
	'%s -h %s -P %d -u %s %s --single-transaction --routines --triggers --add-drop-table --default-character-set=utf8mb4 %s > %s 2>&1',
	escapeshellcmd($mysqldumpCmd),
	escapeshellarg($dbHost),
	$dbPort,
	escapeshellarg($dbUser),
	$passwordArg,
	escapeshellarg($dbName),
	escapeshellarg($outputFile)
);

exec($command, $output, $returnCode);

if ($returnCode === 0 && file_exists($outputFile) && filesize($outputFile) > 0) {
	$fileSize = filesize($outputFile);
	$fileSizeHuman = $fileSize > 1024 * 1024 
		? round($fileSize / (1024 * 1024), 2) . ' MB'
		: round($fileSize / 1024, 2) . ' KB';
	
	echo "✓ База данных успешно экспортирована в $outputFile\n";
	echo "Размер файла: $fileSizeHuman\n";
	exit(0);
} else {
	// Если mysqldump не доступен, создаем дамп через PDO
	echo "mysqldump недоступен, создаем дамп через PDO...\n";
	
	try {
		$dsn = sprintf(
			"mysql:host=%s;port=%d;dbname=%s;charset=%s",
			$dbHost,
			$dbPort,
			$dbName,
			$config['database']['charset']
		);
		
		$pdo = new PDO($dsn, $dbUser, $dbPass, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		]);
		
		$fp = fopen($outputFile, 'w');
		if (!$fp) {
			throw new Exception("Не удалось создать файл $outputFile");
		}
		
		// Записываем заголовок
		fwrite($fp, "-- SQL Dump created by dump-db.php\n");
		fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
		fwrite($fp, "-- Database: $dbName\n\n");
		fwrite($fp, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
		fwrite($fp, "SET time_zone = \"+00:00\";\n\n");
		
		// Получаем список всех таблиц
		$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
		
		foreach ($tables as $table) {
			fwrite($fp, "\n-- --------------------------------------------------------\n");
			fwrite($fp, "-- Table structure for table `$table`\n");
			fwrite($fp, "-- --------------------------------------------------------\n\n");
			
			// Получаем CREATE TABLE
			$createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
			fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
			fwrite($fp, $createTable['Create Table'] . ";\n\n");
			
			// Получаем данные
			$rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
			
			if (count($rows) > 0) {
				fwrite($fp, "-- Dumping data for table `$table`\n\n");
				
				$columns = array_keys($rows[0]);
				$columnList = '`' . implode('`, `', $columns) . '`';
				
				foreach ($rows as $row) {
					$values = [];
					foreach ($row as $value) {
						if ($value === null) {
							$values[] = 'NULL';
						} else {
							$values[] = $pdo->quote($value);
						}
					}
					fwrite($fp, "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n");
				}
				fwrite($fp, "\n");
			}
		}
		
		fclose($fp);
		
		$fileSize = filesize($outputFile);
		$fileSizeHuman = $fileSize > 1024 * 1024 
			? round($fileSize / (1024 * 1024), 2) . ' MB'
			: round($fileSize / 1024, 2) . ' KB';
		
		echo "✓ База данных успешно экспортирована в $outputFile\n";
		echo "Размер файла: $fileSizeHuman\n";
		exit(0);
		
	} catch (Exception $e) {
		echo "✗ Ошибка при создании дампа: " . $e->getMessage() . "\n";
		if (file_exists($outputFile)) {
			unlink($outputFile);
		}
		exit(1);
	}
}
