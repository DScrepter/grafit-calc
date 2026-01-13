<?php
/**
 * API для авторизации
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';

try {
	$auth = new Auth();
	$method = $_SERVER['REQUEST_METHOD'];

	switch ($method) {
	case 'POST':
		$data = json_decode(file_get_contents('php://input'), true);
		$action = $data['action'] ?? '';

		if ($action === 'login') {
			$username = $data['username'] ?? '';
			$password = $data['password'] ?? '';

			if (empty($username) || empty($password)) {
				http_response_code(400);
				echo json_encode(['error' => 'Необходимо указать имя пользователя и пароль']);
				exit;
			}

			if ($auth->login($username, $password)) {
				$userData = $auth->getUserData();
				echo json_encode([
					'success' => true,
					'user' => $userData
				], JSON_UNESCAPED_UNICODE);
			} else {
				http_response_code(401);
				echo json_encode(['error' => 'Неверное имя пользователя или пароль']);
			}
		} elseif ($action === 'register') {
			$username = $data['username'] ?? '';
			$email = $data['email'] ?? '';
			$password = $data['password'] ?? '';
			$first_name = $data['first_name'] ?? null;
			$last_name = $data['last_name'] ?? null;

			if (empty($username) || empty($email) || empty($password)) {
				http_response_code(400);
				echo json_encode(['error' => 'Необходимо заполнить все обязательные поля']);
				exit;
			}

			// Валидация имени пользователя (только латиница)
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
				http_response_code(400);
				echo json_encode(['error' => 'Имя пользователя может содержать только латинские буквы, цифры и подчеркивание']);
				exit;
			}

			if ($auth->register($username, $email, $password, $first_name, $last_name)) {
				echo json_encode(['success' => true, 'message' => 'Регистрация успешна']);
			} else {
				http_response_code(400);
				echo json_encode(['error' => 'Пользователь с таким именем или email уже существует']);
			}
		} elseif ($action === 'logout') {
			$auth->logout();
			echo json_encode(['success' => true]);
		} else {
			http_response_code(400);
			echo json_encode(['error' => 'Неизвестное действие']);
		}
		break;

	case 'GET':
		if ($auth->isLoggedIn()) {
			// Обновляем сессию из БД, чтобы получить актуальные данные
			$auth->refreshUserSession();
			$userData = $auth->getUserData();
			echo json_encode([
				'logged_in' => true,
				'user' => $userData
			]);
		} else {
			echo json_encode(['logged_in' => false]);
		}
		break;

		default:
			http_response_code(405);
			echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'auth.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'auth.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
