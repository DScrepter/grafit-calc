<?php
/**
 * Общий обработчик ошибок для API endpoints
 */

// Устанавливаем заголовок JSON в самом начале, до любых возможных ошибок
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}

// Отключаем вывод ошибок в браузер
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../classes/Logger.php';

$logger = Logger::getInstance();

// Обработчик ошибок
set_error_handler(function($severity, $message, $file, $line) use ($logger) {
	// Не логируем notices и warnings, если они не критичны
	if ($severity === E_NOTICE || $severity === E_WARNING) {
		return false; // Продолжаем стандартную обработку
	}
	
	$logger->error("PHP Error: {$message}", [
		'severity' => $severity,
		'file' => $file,
		'line' => $line,
	]);
	
	return false; // Продолжаем стандартную обработку
}, E_ALL & ~E_NOTICE & ~E_WARNING);

// Обработчик исключений
set_exception_handler(function($exception) use ($logger) {
	$logger->exception($exception, [
		'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
		'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	
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
register_shutdown_function(function() use ($logger) {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		$logger->fatalError($error['message'], $error['file'], $error['line'], '');
		
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
