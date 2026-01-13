<?php
/**
 * API для управления пользователями
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
	
	// Только админы могут управлять пользователями
	if (!$auth->canManageUsers()) {
		http_response_code(403);
		echo json_encode(['error' => 'Недостаточно прав доступа']);
		exit;
	}

	$method = $_SERVER['REQUEST_METHOD'];
	$db = Database::getInstance();
	$currentUserId = $auth->getUserId();
	$isSuperAdmin = $auth->isSuperAdmin();

	switch ($method) {
	case 'GET':
		// Получение списка пользователей или конкретного пользователя
		$userId = $_GET['id'] ?? null;

		if ($userId) {
			// Получение конкретного пользователя
			$user = $db->fetchOne(
				"SELECT id, username, email, first_name, last_name, role, created_at, updated_at FROM users WHERE id = ?",
				[$userId]
			);

			if (!$user) {
				http_response_code(404);
				echo json_encode(['error' => 'Пользователь не найден']);
				exit;
			}

			// Обычные админы не могут видеть супер-админа
			if (!$isSuperAdmin && $user['role'] === 'super_admin') {
				http_response_code(403);
				echo json_encode(['error' => 'Недостаточно прав доступа']);
				exit;
			}

			echo json_encode($user);
		} else {
			// Получение списка пользователей
			if ($isSuperAdmin) {
				// Супер-админ видит всех
				$users = $db->fetchAll(
					"SELECT id, username, email, first_name, last_name, role, created_at, updated_at FROM users ORDER BY id ASC"
				);
			} else {
				// Обычные админы не видят супер-админа, но видят поддержку
				$users = $db->fetchAll(
					"SELECT id, username, email, first_name, last_name, role, created_at, updated_at FROM users WHERE role != 'super_admin' ORDER BY id ASC"
				);
			}

			echo json_encode($users);
		}
		break;

	case 'PUT':
		// Обновление пользователя
		$data = json_decode(file_get_contents('php://input'), true);
		$userId = $data['id'] ?? null;

		if (!$userId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан ID пользователя']);
			exit;
		}

		// Получаем данные пользователя для проверки
		$targetUser = $db->fetchOne(
			"SELECT id, role FROM users WHERE id = ?",
			[$userId]
		);

		if (!$targetUser) {
			http_response_code(404);
			echo json_encode(['error' => 'Пользователь не найден']);
			exit;
		}

		$targetRole = $targetUser['role'];
		$newRole = $data['role'] ?? $targetRole;

		// Проверки прав доступа
		if ($targetRole === 'super_admin') {
			if (!$isSuperAdmin) {
				http_response_code(403);
				echo json_encode(['error' => 'Недостаточно прав для управления супер-администратором']);
				exit;
			}

			// Супер-админ может только передать роль другому пользователю
			if ($newRole !== 'super_admin' && $userId != $currentUserId) {
				http_response_code(403);
				echo json_encode(['error' => 'Можно только передать роль супер-администратора, а не изменить её']);
				exit;
			}

			// Если передаем роль супер-админа другому пользователю
			if ($newRole === 'super_admin' && $userId != $currentUserId) {
				// Проверяем, что получатель не является супер-админом
				if ($targetRole === 'super_admin') {
					http_response_code(400);
					echo json_encode(['error' => 'Пользователь уже является супер-администратором']);
					exit;
				}

				// Текущий супер-админ становится админом
				$db->beginTransaction();
				try {
					$db->execute(
						"UPDATE users SET role = 'admin' WHERE id = ?",
						[$currentUserId]
					);
					// Обновляем сессию текущего пользователя
					$_SESSION['role'] = 'admin';
				} catch (Exception $e) {
					$db->rollBack();
					throw $e;
				}
			}
		} else {
			// Для обычных пользователей проверяем, что не пытаемся назначить роль супер-админа
			if ($newRole === 'super_admin' && !$isSuperAdmin) {
				http_response_code(403);
				echo json_encode(['error' => 'Только супер-администратор может назначать роль супер-администратора']);
				exit;
			}
		}

		// Обновляем данные пользователя
		$username = $data['username'] ?? null;
		$email = $data['email'] ?? null;
		// Обрабатываем пустые строки как null для имени и фамилии
		$first_name = isset($data['first_name']) ? (trim($data['first_name']) ?: null) : null;
		$last_name = isset($data['last_name']) ? (trim($data['last_name']) ?: null) : null;

		$updateFields = [];
		$updateParams = [];

		if ($username !== null) {
			// Проверяем уникальность username
			$existing = $db->fetchOne(
				"SELECT id FROM users WHERE username = ? AND id != ?",
				[$username, $userId]
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
				[$email, $userId]
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

		if ($newRole !== $targetRole) {
			$updateFields[] = "role = ?";
			$updateParams[] = $newRole;
		}

		if (empty($updateFields)) {
			http_response_code(400);
			echo json_encode(['error' => 'Нет данных для обновления']);
			exit;
		}

		$updateParams[] = $userId;
		$sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
		
		$conn = $db->getConnection();
		$conn->beginTransaction();
		
		try {
			$db->execute($sql, $updateParams);
			
			// Если передали роль супер-админа, текущий становится админом
			if ($newRole === 'super_admin' && $userId != $currentUserId && $targetRole !== 'super_admin') {
				$db->execute(
					"UPDATE users SET role = 'admin' WHERE id = ?",
					[$currentUserId]
				);
				// Обновляем сессию текущего пользователя
				$_SESSION['role'] = 'admin';
			}
			
			$conn->commit();
			
			// Получаем обновленные данные
			$updatedUser = $db->fetchOne(
				"SELECT id, username, email, first_name, last_name, role, created_at, updated_at FROM users WHERE id = ?",
				[$userId]
			);

			// Если изменяемый пользователь - это текущий пользователь, обновляем его сессию
			if ($userId == $currentUserId) {
				$auth->refreshUserSession($userId);
			}

			echo json_encode($updatedUser);
		} catch (Exception $e) {
			$conn->rollBack();
			throw $e;
		}
		break;

	case 'DELETE':
		// Удаление пользователя
		$userId = $_GET['id'] ?? null;

		if (!$userId) {
			http_response_code(400);
			echo json_encode(['error' => 'Не указан ID пользователя']);
			exit;
		}

		// Получаем данные пользователя
		$targetUser = $db->fetchOne(
			"SELECT id, role FROM users WHERE id = ?",
			[$userId]
		);

		if (!$targetUser) {
			http_response_code(404);
			echo json_encode(['error' => 'Пользователь не найден']);
			exit;
		}

		// Нельзя удалить супер-админа
		if ($targetUser['role'] === 'super_admin') {
			http_response_code(403);
			echo json_encode(['error' => 'Нельзя удалить супер-администратора']);
			exit;
		}

		// Нельзя удалить самого себя
		if ($userId == $currentUserId) {
			http_response_code(400);
			echo json_encode(['error' => 'Нельзя удалить самого себя']);
			exit;
		}

		$db->execute("DELETE FROM users WHERE id = ?", [$userId]);
		echo json_encode(['success' => true, 'message' => 'Пользователь удален']);
		break;

	default:
		http_response_code(405);
		echo json_encode(['error' => 'Метод не поддерживается']);
	}
} catch (Exception $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'users.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
	$logger = Logger::getInstance();
	$logger->exception($e, [
		'endpoint' => 'users.php',
		'method' => $_SERVER['REQUEST_METHOD'] ?? '',
	]);
	http_response_code(500);
	echo json_encode(['error' => 'Критическая ошибка сервера'], JSON_UNESCAPED_UNICODE);
}
