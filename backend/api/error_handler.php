<?php
/**
 * Общий обработчик ошибок для API endpoints
 */

// Защита от повторного подключения
if (defined('ERROR_HANDLER_LOADED')) {
	return;
}
define('ERROR_HANDLER_LOADED', true);

// Устанавливаем заголовок JSON в самом начале, до любых возможных ошибок
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}

// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Защита от рекурсии при логировании
if (!isset($GLOBALS['error_handler_recursion'])) {
	$GLOBALS['error_handler_recursion'] = false;
}

// Подключаем Logger только если он еще не подключен
if (!class_exists('Logger')) {
	require_once __DIR__ . '/../classes/Logger.php';
}

$logger = null;
try {
	$logger = Logger::getInstance();
} catch (Exception $e) {
	// Если Logger не может быть создан, используем стандартный error_log
	$logger = null;
} catch (Error $e) {
	// Если Logger не может быть создан из-за ошибки компиляции, используем стандартный error_log
	$logger = null;
}

// Обработчик ошибок
set_error_handler(function($severity, $message, $file, $line) use (&$logger) {
	// Защита от рекурсии
	if (isset($GLOBALS['error_handler_recursion']) && $GLOBALS['error_handler_recursion']) {
		return false;
	}
	
	// Не логируем notices и warnings, если они не критичны
	if ($severity === E_NOTICE || $severity === E_WARNING) {
		return false; // Продолжаем стандартную обработку
	}
	
	$GLOBALS['error_handler_recursion'] = true;
	try {
		if ($logger) {
			$logger->error("PHP Error: {$message}", [
				'severity' => $severity,
				'file' => $file,
				'line' => $line,
			]);
		} else {
			error_log("PHP Error: {$message} in {$file} on line {$line}");
		}
	} catch (Exception $e) {
		error_log("Error in error handler: " . $e->getMessage());
	} catch (Error $e) {
		error_log("Error in error handler: " . $e->getMessage());
	} finally {
		$GLOBALS['error_handler_recursion'] = false;
	}
	
	return false; // Продолжаем стандартную обработку
}, E_ALL & ~E_NOTICE & ~E_WARNING);

// Обработчик исключений
set_exception_handler(function($exception) use (&$logger) {
	// Защита от рекурсии
	if (isset($GLOBALS['error_handler_recursion']) && $GLOBALS['error_handler_recursion']) {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(500);
			echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
		}
		return;
	}
	
	$GLOBALS['error_handler_recursion'] = true;
	try {
		if ($logger) {
			$logger->exception($exception, [
				'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
				'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
			]);
		} else {
			error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
		}
	} catch (Exception $e) {
		error_log("Error in exception handler: " . $e->getMessage());
	} catch (Error $e) {
		error_log("Error in exception handler: " . $e->getMessage());
	} finally {
		$GLOBALS['error_handler_recursion'] = false;
	}
	
	// Если заголовки еще не отправлены, отправляем JSON ответ
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(500);
		echo json_encode([
			'error' => 'Внутренняя ошибка сервера',
			'message' => $exception->getMessage(),
		], JSON_UNESCAPED_UNICODE);
	}
});

// Обработчик фатальных ошибок
register_shutdown_function(function() use (&$logger) {
	// Защита от рекурсии
	if (isset($GLOBALS['error_handler_recursion']) && $GLOBALS['error_handler_recursion']) {
		return;
	}
	
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		$GLOBALS['error_handler_recursion'] = true;
		try {
			if ($logger) {
				$logger->fatalError($error['message'], $error['file'], $error['line'], '');
			} else {
				error_log("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
			}
		} catch (Exception $e) {
			error_log("Error in shutdown handler: " . $e->getMessage());
		} catch (Error $e) {
			error_log("Error in shutdown handler: " . $e->getMessage());
		} finally {
			$GLOBALS['error_handler_recursion'] = false;
		}
		
		// Если заголовки еще не отправлены, отправляем JSON ответ
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(500);
			echo json_encode([
				'error' => 'Критическая ошибка сервера',
				'message' => $error['message'],
			], JSON_UNESCAPED_UNICODE);
		}
	}
});
