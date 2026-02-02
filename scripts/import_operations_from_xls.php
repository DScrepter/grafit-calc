<?php
/**
 * Разовый импорт операций из файла Расценки_новые_2025_только_для_изделий.xls
 * Запуск: php scripts/import_operations_from_xls.php
 * Также доступен через: Обновления БД → Импортировать операции из XLS
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/backend/classes/OperationsImporter.php';

$importer = new OperationsImporter($baseDir);
$result = $importer->import();

if (!$result['success']) {
    echo "Ошибка: " . $result['message'] . "\n";
    foreach ($result['errors'] ?? [] as $e) {
        echo "  - {$e}\n";
    }
    exit(1);
}

echo "--- Итог ---\n";
echo $result['message'] . "\n";
if (!empty($result['errors'])) {
    echo "\nПредупреждения:\n";
    foreach ($result['errors'] as $e) {
        echo "  - {$e}\n";
    }
}
