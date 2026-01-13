<?php
/**
 * Подключение к базе данных MySQL
 */

require_once __DIR__ . '/config.php';

class Database {
	private static $instance = null;
	private $pdo;
	private $config;
	private $logger;

	private function __construct() {
		$this->config = require __DIR__ . '/config.php';
		// Logger подключаем только если класс существует (избегаем циклических зависимостей)
		if (class_exists('Logger')) {
			$this->logger = Logger::getInstance();
		}
		$this->connect();
	}

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function connect() {
		try {
			$dsn = sprintf(
				"mysql:host=%s;port=%d;dbname=%s;charset=%s",
				$this->config['database']['host'],
				$this->config['database']['port'],
				$this->config['database']['dbname'],
				$this->config['database']['charset']
			);

			$options = [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false,
			];

			$this->pdo = new PDO(
				$dsn,
				$this->config['database']['username'],
				$this->config['database']['password'],
				$options
			);
		} catch (PDOException $e) {
			if ($this->logger) {
				$this->logger->exception($e, [
					'action' => 'database_connection',
					'host' => $this->config['database']['host'],
					'dbname' => $this->config['database']['dbname']
				]);
			} else {
				error_log("Database connection error: " . $e->getMessage());
			}
			throw new Exception("Ошибка подключения к базе данных");
		}
	}

	public function getConnection() {
		return $this->pdo;
	}

	public function query($sql, $params = []) {
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			return $stmt;
		} catch (PDOException $e) {
			if ($this->logger) {
				$this->logger->exception($e, [
					'action' => 'database_query',
					'sql' => $sql,
					'params' => $params
				]);
			} else {
				error_log("Query error: " . $e->getMessage());
			}
			throw new Exception("Ошибка выполнения запроса");
		}
	}

	public function fetchAll($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt->fetchAll();
	}

	public function fetchOne($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt->fetch();
	}

	public function execute($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt->rowCount();
	}

	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}

	public function beginTransaction() {
		return $this->pdo->beginTransaction();
	}

	public function commit() {
		return $this->pdo->commit();
	}

	public function rollBack() {
		return $this->pdo->rollBack();
	}
}
