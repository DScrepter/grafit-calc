<?php
/**
 * Класс для авторизации пользователей
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
	private $db;

	public function __construct() {
		$this->db = Database::getInstance();
		$this->startSession();
	}

	private function startSession() {
		if (session_status() === PHP_SESSION_NONE) {
			$config = require __DIR__ . '/../config/config.php';
			session_name($config['session']['name']);
			session_set_cookie_params($config['session']['lifetime']);
			session_start();
		}
	}

	public function login($username, $password) {
		// Проверяем, существуют ли новые поля в БД
		$hasNewFields = false;
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
			$hasNewFields = $stmt->fetch() !== false;
		} catch (Exception $e) {
			// Если ошибка, предполагаем что полей нет
			$hasNewFields = false;
		}

		// Формируем запрос в зависимости от наличия полей
		if ($hasNewFields) {
			$user = $this->db->fetchOne(
				"SELECT id, username, email, password_hash, first_name, last_name, role FROM users WHERE username = ? OR email = ?",
				[$username, $username]
			);
		} else {
			$user = $this->db->fetchOne(
				"SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ?",
				[$username, $username]
			);
		}

		if ($user && password_verify($password, $user['password_hash'])) {
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['username'] = $user['username'];
			$_SESSION['email'] = $user['email'];
			$_SESSION['first_name'] = $user['first_name'] ?? null;
			$_SESSION['last_name'] = $user['last_name'] ?? null;
			$_SESSION['role'] = $user['role'] ?? 'guest';
			return true;
		}

		return false;
	}

	public function register($username, $email, $password, $first_name = null, $last_name = null) {
		// Проверяем, существует ли пользователь
		$existing = $this->db->fetchOne(
			"SELECT id FROM users WHERE username = ? OR email = ?",
			[$username, $email]
		);

		if ($existing) {
			return false; // Пользователь уже существует
		}

		// Проверяем, существуют ли новые поля в БД
		$hasNewFields = false;
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
			$hasNewFields = $stmt->fetch() !== false;
		} catch (Exception $e) {
			$hasNewFields = false;
		}

		// Создаем нового пользователя
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);
		
		if ($hasNewFields) {
			$this->db->execute(
				"INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'guest')",
				[$username, $email, $passwordHash, $first_name, $last_name]
			);
		} else {
			$this->db->execute(
				"INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
				[$username, $email, $passwordHash]
			);
		}

		return true;
	}

	public function logout() {
		session_destroy();
		$_SESSION = [];
	}

	public function isLoggedIn() {
		return isset($_SESSION['user_id']);
	}

	public function getUserId() {
		return $_SESSION['user_id'] ?? null;
	}

	public function getUsername() {
		return $_SESSION['username'] ?? null;
	}

	public function getUserRole() {
		return $_SESSION['role'] ?? null;
	}

	public function getUserData() {
		if (!$this->isLoggedIn()) {
			return null;
		}

		return [
			'id' => $this->getUserId(),
			'username' => $this->getUsername(),
			'email' => $_SESSION['email'] ?? null,
			'first_name' => $_SESSION['first_name'] ?? null,
			'last_name' => $_SESSION['last_name'] ?? null,
			'role' => $this->getUserRole()
		];
	}

	public function isSuperAdmin() {
		return $this->getUserRole() === 'super_admin';
	}

	public function isAdmin() {
		$role = $this->getUserRole();
		return $role === 'super_admin' || $role === 'admin';
	}

	public function canAccessReferences() {
		$role = $this->getUserRole();
		// Если роль не установлена (старая БД), разрешаем доступ (обратная совместимость)
		if ($role === null) {
			return true;
		}
		return in_array($role, ['super_admin', 'admin', 'user']);
	}

	public function canManageUsers() {
		$role = $this->getUserRole();
		// Если роль не установлена (старая БД), запрещаем управление пользователями
		if ($role === null) {
			return false;
		}
		return $this->isAdmin();
	}

	public function canManageSuperAdmin() {
		return $this->isSuperAdmin();
	}

	public function requireAuth() {
		if (!$this->isLoggedIn()) {
			http_response_code(401);
			echo json_encode(['error' => 'Требуется авторизация']);
			exit;
		}
	}

	public function requireRole($allowedRoles) {
		$this->requireAuth();
		$role = $this->getUserRole();
		if (!in_array($role, $allowedRoles)) {
			http_response_code(403);
			echo json_encode(['error' => 'Недостаточно прав доступа']);
			exit;
		}
	}

	/**
	 * Обновляет данные пользователя в сессии из БД
	 * Используется когда данные пользователя были изменены администратором
	 */
	public function refreshUserSession($userId = null) {
		if (!$this->isLoggedIn()) {
			return false;
		}

		$targetUserId = $userId ?? $this->getUserId();
		
		// Проверяем, существуют ли новые поля в БД
		$hasNewFields = false;
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
			$hasNewFields = $stmt->fetch() !== false;
		} catch (Exception $e) {
			$hasNewFields = false;
		}

		// Получаем актуальные данные пользователя из БД
		if ($hasNewFields) {
			$user = $this->db->fetchOne(
				"SELECT id, username, email, first_name, last_name, role FROM users WHERE id = ?",
				[$targetUserId]
			);
		} else {
			$user = $this->db->fetchOne(
				"SELECT id, username, email FROM users WHERE id = ?",
				[$targetUserId]
			);
		}

		if ($user) {
			$_SESSION['username'] = $user['username'];
			$_SESSION['email'] = $user['email'];
			$_SESSION['first_name'] = $user['first_name'] ?? null;
			$_SESSION['last_name'] = $user['last_name'] ?? null;
			$_SESSION['role'] = $user['role'] ?? 'guest';
			return true;
		}

		return false;
	}
}
