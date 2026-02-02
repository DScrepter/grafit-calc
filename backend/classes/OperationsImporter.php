<?php
/**
 * Импорт операций из XLS-файла
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

class OperationsImporter {
	private $baseDir;
	private $db;

	public function __construct($baseDir = null) {
		$this->baseDir = $baseDir ?? dirname(__DIR__, 2);
		$this->db = Database::getInstance();
	}

	/**
	 * Импортирует операции из XLS.
	 * @return array{success: bool, inserted: int, skipped: int, errors: string[], message: string}
	 */
	public function import() {
		$xlsFile = $this->baseDir . '/Расценки_новые_2025_только_для_изделий.xls';
		if (!file_exists($xlsFile)) {
			$files = glob($this->baseDir . '/*.xls');
			$xlsFile = $files[0] ?? null;
		}
		if (!$xlsFile || !file_exists($xlsFile)) {
			return [
				'success' => false,
				'inserted' => 0,
				'skipped' => 0,
				'errors' => ['Файл XLS не найден. Поместите Расценки_новые_2025_только_для_изделий.xls в корень проекта.'],
				'message' => 'Файл XLS не найден'
			];
		}

		try {
			$pdo = $this->db->getConnection();
		} catch (Throwable $e) {
			return [
				'success' => false,
				'inserted' => 0,
				'skipped' => 0,
				'errors' => ['Ошибка подключения к БД: ' . $e->getMessage()],
				'message' => 'Ошибка БД'
			];
		}

		$hasMaterialMarks = false;
		$row = $pdo->query("SELECT 1 FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operations' AND COLUMN_NAME = 'material_marks'")->fetch();
		if ($row) {
			$hasMaterialMarks = true;
		}

		$units = $pdo->query("SELECT id, name FROM units")->fetchAll(PDO::FETCH_ASSOC);
		$unitMap = [];
		foreach ($units as $u) {
			$unitMap[mb_strtolower(trim($u['name']))] = (int) $u['id'];
		}
		$unitMap['шт.'] = $unitMap['шт'] ?? $unitMap['шт.'] ?? null;

		try {
			$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
			$spreadsheet = $reader->load($xlsFile);
			$sheet = $spreadsheet->getActiveSheet();
			$rows = $sheet->toArray();
		} catch (Throwable $e) {
			return [
				'success' => false,
				'inserted' => 0,
				'skipped' => 0,
				'errors' => ['Ошибка чтения XLS: ' . $e->getMessage()],
				'message' => 'Ошибка чтения файла'
			];
		}

		$inserted = 0;
		$skipped = 0;
		$errors = [];
		$sqlInsert = $hasMaterialMarks
			? "INSERT INTO operations (number, description, unit_id, cost, material_marks) VALUES (?, ?, ?, ?, ?)"
			: "INSERT INTO operations (number, description, unit_id, cost) VALUES (?, ?, ?, ?)";
		$stmt = $pdo->prepare($sqlInsert);

		for ($i = 2; $i < count($rows); $i++) {
			$row = $rows[$i];
			$number = trim((string) ($row[0] ?? ''));
			$description = trim((string) ($row[1] ?? ''));
			$unitName = trim((string) ($row[2] ?? ''));
			$materialMarks = trim((string) ($row[4] ?? ''));
			$costRaw = $row[5] ?? 0;

			if (empty($number) && empty($description)) {
				continue;
			}
			if (empty($number) || empty($description)) {
				$skipped++;
				$errors[] = "Строка " . ($i + 1) . ": пропущены номер или описание";
				continue;
			}

			$cost = (float) str_replace(',', '.', (string) $costRaw);
			if ($cost < 0) {
				$cost = 0;
			}

			$unitId = null;
			if (!empty($unitName)) {
				$key = mb_strtolower($unitName);
				$unitId = $unitMap[$key] ?? $unitMap['шт'] ?? $unitMap['шт.'] ?? null;
			}

			try {
				if ($hasMaterialMarks) {
					$stmt->execute([$number, $description, $unitId, $cost, $materialMarks]);
				} else {
					$stmt->execute([$number, $description, $unitId, $cost]);
				}
				$inserted++;
			} catch (PDOException $e) {
				if ($e->getCode() == 23000) {
					$skipped++;
					$errors[] = "Строка " . ($i + 1) . ": операция №{$number} уже существует";
				} else {
					$errors[] = "Строка " . ($i + 1) . ": " . $e->getMessage();
				}
			}
		}

		$message = "Добавлено: {$inserted}, пропущено: {$skipped}";
		return [
			'success' => true,
			'inserted' => $inserted,
			'skipped' => $skipped,
			'errors' => $errors,
			'message' => $message
		];
	}

	/**
	 * Проверяет, доступен ли файл для импорта
	 */
	public function isFileAvailable() {
		$xlsFile = $this->baseDir . '/Расценки_новые_2025_только_для_изделий.xls';
		if (!file_exists($xlsFile)) {
			$files = glob($this->baseDir . '/*.xls');
			$xlsFile = $files[0] ?? null;
		}
		return $xlsFile && file_exists($xlsFile);
	}
}
