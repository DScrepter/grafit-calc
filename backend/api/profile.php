<?php
/**
 * API для редактирования собственного профиля
 */

require_once __DIR__ . '/error_handler.php';
if (!headers_sent()) {
	header('Content-Type: application/json; charset=utf-8');
}
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

try {
	$auth = new Auth();
	$auth->requireAuth();

	$method = $_SERVER['REQUEST_METHOD'];
	$db = Database::getInstance();
	$currentUserId = $auth->getUserId();

	switch ($method) {
	case 'GET':
		// Получение данных текущего пользователя
		$userData = $auth->getUserData();
		echo json_encode($userData);
		break;

	case 'PUT':
		// Обновление данных текущего пользователя
		$data = json_decode(file_get_contents('php://input'), true);

		$username = $data['username'] ?? null;
		$email = $data['email'] ?? null;
		// Обрабатываем пустые строки как null для имени и фамилии
		$first_name = isset($data['first_name']) ? (trim($data['first_name']) ?: null) : null;
		$last_name = isset($data['last_name']) ? (trim($data['last_name']) ?: null) : null;
		$password = $data['password'] ?? null;
		$current_password = $data['current_password'] ?? null;

		$updateFields = [];
		$updateParams = [];

		if ($username !== null) {
			// Проверяем уникальность username
			$existing = $db->fetchOne(
				"SELECT id FROM users WHERE username = ? AND id != ?",
				[$username, $currentUserId]
			);
			if ($existing) {
				http_response_code(400);
				echo json_encode(['error' => 'Пользователь с таким именем уже существует']);
				exit;
			}
			$updateFields[] = "username = ?";
			$updateParams[] = $username;
		}

		if ($email !== null) {
			// Проверяем уникальность email
			$existing = $db->fetchOne(
				"SELECT id FROM users WHERE email = ? AND id != ?",
				[$email, $currentUserId]
			);
			if ($existing) {
				http_response_code(400);
				echo json_encode(['error' => 'Пользователь с таким email уже существует']);
				exit;
			}
			$updateFields[] = "email = ?";
			$updateParams[] = $email;
		}

		// Обновляем имя и фамилию (включая установку в NULL при пустых значениях)
		// Всегда обновляем, если поле присутствует в запросе (даже если null)
		if (array_key_exists('first_name', $data)) {
			$updateFields[] = "first_name = ?";
			$updateParams[] = $first_name; // уже обработано выше как null если пусто
		}

		if (array_key_exists('last_name', $data)) {
			$updateFields[] = "last_name = ?";
			$updateParams[] = $last_name; // уже обработано выше как null если пусто
		}

		// Обновление пароля
		if ($password !== null) {
			if (empty($current_password)) {
				http_response_code(400);
				echo json_encode(['error' => 'Необходимо указать текущий пароль для смены пароля']);
				exit;
			}

			// Проверяем текущий пароль
			$user = $db->fetchOne(
				"SELECT password_hash FROM users WHERE id = ?",
				[$currentUserId]
			);

			if (!$user || !password_verify($current_password, $user['password_hash'])) {
				http_response_code(400);
				echo json_encode(['error' => 'Неверный текущий пароль']);
				exit;
			}

			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			$updateFields[] = "password_hash = ?";
			$updateParams[] = $passwordHash;
		}

		if (empty($updateFields)) {
			http_response_code(400);
			echo json_encode(['error' => 'Нет данных для обновления']);
			exit;
		}

		$updateParams[] = $currentUserId;
		$sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
		
		$db->execute($sql, $updateParams);
		
		// Обновляем сессию пользователя
		$auth->refreshUserSession();
		
		// Получаем обновленные данные
		$updatedUser = $auth->getUserData();

		echo json_encode($updatedUser);
		break;

	default:
		http_response_code(405);
		echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'profile.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'profile.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
