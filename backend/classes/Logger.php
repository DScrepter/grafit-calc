<?php
/**
 * Класс для логирования ошибок и сообщений
 */

class Logger {
	private static $instance = null;
	private $logDir;
	private $logFile;
	private $config;

	private function __construct() {
		// Загружаем конфигурацию один раз
		if (!isset($GLOBALS['app_config'])) {
			$config_file = __DIR__ . '/../config/config.php';
			if (file_exists($config_file)) {
				$GLOBALS['app_config'] = require $config_file;
			} else {
				$GLOBALS['app_config'] = [
					'database' => [
						'host' => 'localhost',
						'port' => 3306,
						'dbname' => 'cost_calculator_web',
						'username' => 'root',
						'password' => '',
						'charset' => 'utf8mb4'
					],
					'app' => [
						'name' => 'Калькулятор себестоимости',
						'debug' => true,
						'timezone' => 'Europe/Moscow'
					],
					'session' => [
						'lifetime' => 3600,
						'name' => 'cost_calc_session'
					],
					'logging' => [
						'enabled' => true,
						'level' => 'ERROR'
					]
				];
			}
		}
		$this->config = $GLOBALS['app_config'];
		
		// Директория для логов (относительно корня проекта)
		$baseDir = dirname(__DIR__, 2);
		$this->logDir = $baseDir . '/logs';
		
		// Создаем директорию, если её нет
		if (!is_dir($this->logDir)) {
			@mkdir($this->logDir, 0755, true);
		}
		
		// Имя файла лога с датой
		$date = date('Y-m-d');
		$this->logFile = $this->logDir . '/error-' . $date . '.log';
	}

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Запись ошибки
	 */
	public function error($message, $context = []) {
		$this->write('ERROR', $message, $context);
	}

	/**
	 * Запись предупреждения
	 */
	public function warning($message, $context = []) {
		$this->write('WARNING', $message, $context);
	}

	/**
	 * Запись информации
	 */
	public function info($message, $context = []) {
		$this->write('INFO', $message, $context);
	}

	/**
	 * Запись исключения с полной трассировкой
	 */
	public function exception($e, $context = []) {
		if ($e instanceof Exception || $e instanceof Error) {
			$context['file'] = $e->getFile();
			$context['line'] = $e->getLine();
			$context['trace'] = $e->getTraceAsString();
			$context['type'] = get_class($e);
			$this->write('EXCEPTION', $e->getMessage(), $context);
		} else {
			$this->write('ERROR', (string)$e, $context);
		}
	}

	/**
	 * Запись фатальной ошибки
	 */
	public function fatalError($message, $file, $line, $trace = '') {
		$this->write('FATAL ERROR', $message, [
			'file' => $file,
			'line' => $line,
			'trace' => $trace
		]);
	}

	/**
	 * Запись в лог файл
	 */
	private function write($level, $message, $context = []) {
		$timestamp = date('Y-m-d H:i:s');
		$logEntry = "[{$timestamp}] [{$level}] {$message}";
		
		// Добавляем контекст, если есть
		if (!empty($context)) {
			$logEntry .= "\n" . $this->formatContext($context);
		}
		
		$logEntry .= "\n" . str_repeat('-', 80) . "\n";
		
		// Записываем в файл
		$result = @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
		
		// Если запись не удалась, пробуем через error_log (fallback)
		if ($result === false) {
			error_log("Logger: Не удалось записать в файл {$this->logFile}. Сообщение: {$message}");
		}
	}

	/**
	 * Форматирование контекста для записи
	 */
	private function formatContext($context) {
		$formatted = [];
		foreach ($context as $key => $value) {
			if (is_array($value) || is_object($value)) {
				$value = print_r($value, true);
			}
			$formatted[] = "  {$key}: {$value}";
		}
		return implode("\n", $formatted);
	}

	/**
	 * Получить последние N записей из лога
	 */
	public function getLastEntries($lines = 50) {
		if (!file_exists($this->logFile)) {
			return [];
		}
		
		$content = file_get_contents($this->logFile);
		$entries = explode(str_repeat('-', 80), $content);
		$entries = array_filter($entries, function($entry) {
			return trim($entry) !== '';
		});
		
		return array_slice(array_reverse($entries), 0, $lines);
	}

	/**
	 * Получить все логи за указанную дату
	 */
	public function getLogsByDate($date = null) {
		if ($date === null) {
			$date = date('Y-m-d');
		}
		
		$logFile = $this->logDir . '/error-' . $date . '.log';
		if (!file_exists($logFile)) {
			return '';
		}
		
		return file_get_contents($logFile);
	}
}
