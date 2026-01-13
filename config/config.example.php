<?php
/**
 * Пример конфигурации приложения
 * Скопируйте этот файл в config.php и заполните своими данными
 */

return [
	'database' => [
		'host' => 'localhost',
		'port' => 3306,
		'dbname' => 'cost_calculator_web',
		'username' => 'your_username',
		'password' => 'your_password',
		'charset' => 'utf8mb4'
	],
	'app' => [
		'name' => 'Калькулятор себестоимости',
		'debug' => true,
		'timezone' => 'Europe/Moscow'
	],
	'session' => [
		'lifetime' => 3600, // 1 час
		'name' => 'cost_calc_session'
	],
	'logging' => [
		'enabled' => true,
		'level' => 'ERROR' // ERROR, WARNING, INFO, или ALL
	]
];
