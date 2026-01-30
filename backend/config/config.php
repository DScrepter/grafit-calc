<?php
/**
 * Конфигурация приложения
 */

// Загружаем конфигурацию из файла или используем значения по умолчанию
$config_file = __DIR__ . '/../../config/config.php';
if (file_exists($config_file)) {
	$config = require $config_file;
} else {
	// Значения по умолчанию
	$config = [
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
			'lifetime' => 86400, // 1 день
			'name' => 'cost_calc_session'
		],
		'logging' => [
			'enabled' => true,
			'level' => 'ERROR'
		]
	];
}

// Устанавливаем часовой пояс
date_default_timezone_set($config['app']['timezone']);

return $config;
