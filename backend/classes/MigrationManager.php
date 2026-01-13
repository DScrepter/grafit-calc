<?php
/**
 * Менеджер обновлений базы данных
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Logger.php';

class MigrationManager {
	private $db;
	private $logger;
	private $migrationsPath;

	public function __construct() {
		$this->db = Database::getInstance();
		$this->logger = Logger::getInstance();
		$this->migrationsPath = __DIR__ . '/../../database/migrations';
		$this->ensureMigrationsTable();
	}

	/**
	 * Создает таблицу migrations, если её нет
	 */
	private function ensureMigrationsTable() {
		try {
			$this->db->execute("
				CREATE TABLE IF NOT EXISTS migrations (
					id INT NOT NULL AUTO_INCREMENT,
					migration_name VARCHAR(255) NOT NULL UNIQUE,
					applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id),
					INDEX idx_migration_name (migration_name)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			");
		} catch (Exception $e) {
			$this->logger->exception($e, ['action' => 'ensure_migrations_table']);
			throw $e;
		}
	}

	/**
	 * Получает список примененных обновлений
	 */
	public function getAppliedMigrations() {
		$migrations = $this->db->fetchAll("SELECT migration_name FROM migrations ORDER BY migration_name");
		return array_column($migrations, 'migration_name');
	}

	/**
	 * Получает список доступных обновлений из файлов
	 */
	public function getAvailableMigrations() {
		$migrations = [];
		if (!is_dir($this->migrationsPath)) {
			return $migrations;
		}

		$files = scandir($this->migrationsPath);
		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
				$migrations[] = $file;
			}
		}

		sort($migrations);
		return $migrations;
	}

	/**
	 * Получает список обновлений, которые нужно применить
	 */
	public function getPendingMigrations() {
		$applied = $this->getAppliedMigrations();
		$available = $this->getAvailableMigrations();
		return array_diff($available, $applied);
	}

	/**
	 * Применяет обновление
	 */
	public function applyMigration($migrationName) {
		$filePath = $this->migrationsPath . '/' . $migrationName;
		
		if (!file_exists($filePath)) {
			throw new Exception("Файл обновления не найден: {$migrationName}");
		}

		// Проверяем, не применено ли уже обновление
		$applied = $this->db->fetchOne(
			"SELECT id FROM migrations WHERE migration_name = ?",
			[$migrationName]
		);

		if ($applied) {
			throw new Exception("Обновление уже применено: {$migrationName}");
		}

		// Читаем SQL из файла
		$sql = file_get_contents($filePath);
		if ($sql === false) {
			throw new Exception("Не удалось прочитать файл обновления: {$migrationName}");
		}

		// Удаляем комментарии (строки, начинающиеся с --)
		$lines = explode("\n", $sql);
		$cleanedLines = [];
		foreach ($lines as $line) {
			$trimmed = trim($line);
			// Пропускаем пустые строки и комментарии
			if (!empty($trimmed) && !preg_match('/^\s*--/', $trimmed)) {
				$cleanedLines[] = $line;
			}
		}
		
		$sql = implode("\n", $cleanedLines);
		$sql = trim($sql);
		
		if (empty($sql)) {
			throw new Exception("Файл обновления пуст или содержит только комментарии: {$migrationName}");
		}

		// Разбиваем на отдельные запросы по точке с запятой
		// Простой подход: разбиваем по ; и фильтруем пустые
		$parts = explode(';', $sql);
		$queries = [];
		
		foreach ($parts as $part) {
			$part = trim($part);
			// Пропускаем пустые части и комментарии
			if (!empty($part) && !preg_match('/^\s*--/', $part)) {
				$queries[] = $part;
			}
		}

		if (empty($queries)) {
			throw new Exception("В файле обновления нет валидных SQL запросов: {$migrationName}");
		}

		// Проверяем тип обновления для предупреждений
		$isDangerous = $this->isDangerousMigration($sql);

		// Применяем обновление в транзакции
		// ВАЖНО: Транзакция гарантирует, что при ошибке все изменения откатятся
		// Однако, некоторые операции (например, DROP TABLE) могут не поддерживать транзакции в MySQL
		$this->db->beginTransaction();
		try {
			foreach ($queries as $query) {
				if (!empty(trim($query))) {
					$this->db->execute($query);
				}
			}

			// Сохраняем информацию о примененном обновлении
			$this->db->execute(
				"INSERT INTO migrations (migration_name) VALUES (?)",
				[$migrationName]
			);

			$this->db->commit();
			$this->logger->info("Обновление применено: {$migrationName}");
			return true;
		} catch (Exception $e) {
			// При ошибке откатываем все изменения
			$this->db->rollBack();
			$this->logger->exception($e, [
				'action' => 'apply_migration',
				'migration' => $migrationName
			]);
			throw new Exception("Ошибка применения обновления {$migrationName}: " . $e->getMessage());
		}
	}

	/**
	 * Применяет все ожидающие обновления
	 */
	public function applyPendingMigrations() {
		$pending = $this->getPendingMigrations();
		$applied = [];
		$errors = [];

		foreach ($pending as $migration) {
			try {
				$this->applyMigration($migration);
				$applied[] = $migration;
			} catch (Exception $e) {
				$errors[] = [
					'migration' => $migration,
					'error' => $e->getMessage()
				];
			}
		}

		return [
			'applied' => $applied,
			'errors' => $errors,
			'success' => empty($errors)
		];
	}

	/**
	 * Проверяет, является ли обновление потенциально опасным
	 */
	private function isDangerousMigration($sql) {
		$dangerousPatterns = [
			'/DROP\s+TABLE/i',
			'/DROP\s+COLUMN/i',
			'/TRUNCATE\s+TABLE/i',
			'/DELETE\s+FROM/i',
			'/UPDATE\s+.*\s+SET/i'
		];

		foreach ($dangerousPatterns as $pattern) {
			if (preg_match($pattern, $sql)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Получает информацию об обновлении (безопасность, описание)
	 */
	public function getMigrationInfo($migrationName) {
		$filePath = $this->migrationsPath . '/' . $migrationName;
		
		if (!file_exists($filePath)) {
			return null;
		}

		$sql = file_get_contents($filePath);
		$isDangerous = $this->isDangerousMigration($sql);

		// Пытаемся извлечь описание из комментариев
		$description = '';
		$lines = explode("\n", $sql);
		foreach ($lines as $line) {
			if (preg_match('/--\s*(.+)/', $line, $matches)) {
				$comment = trim($matches[1]);
				if (stripos($comment, 'обновление:') !== false || stripos($comment, 'миграция:') !== false || stripos($comment, 'migration:') !== false) {
					$description = $comment;
					break;
				}
			}
		}

		return [
			'name' => $migrationName,
			'is_dangerous' => $isDangerous,
			'description' => $description ?: 'Описание отсутствует'
		];
	}

	/**
	 * Получает статус обновлений
	 */
	public function getStatus() {
		$applied = $this->getAppliedMigrations();
		$available = $this->getAvailableMigrations();
		$pending = $this->getPendingMigrations();

		// Получаем информацию о каждом обновлении
		$pendingInfo = [];
		foreach ($pending as $migration) {
			$info = $this->getMigrationInfo($migration);
			$pendingInfo[] = $info ?: ['name' => $migration, 'is_dangerous' => false, 'description' => ''];
		}

		return [
			'applied' => $applied,
			'available' => $available,
			'pending' => array_values($pending),
			'pending_info' => $pendingInfo,
			'total_applied' => count($applied),
			'total_available' => count($available),
			'total_pending' => count($pending)
		];
	}
}
