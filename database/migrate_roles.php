<?php
/**
 * Миграция для добавления полей role, first_name, last_name в таблицу users
 * 
 * Использование:
 * php migrate_roles.php
 */

require_once __DIR__ . '/../backend/config/database.php';

try {
	$db = Database::getInstance();
	$conn = $db->getConnection();
	
	echo "Начинаем миграцию ролей и полей пользователей...\n";
	
	// Начинаем транзакцию
	$conn->beginTransaction();
	
	try {
		// Проверяем, существует ли уже поле role
		$checkRole = $conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
		if (!$checkRole) {
			echo "Добавляем поле role...\n";
			$conn->exec("ALTER TABLE users ADD COLUMN role ENUM('super_admin', 'admin', 'user', 'guest') NOT NULL DEFAULT 'guest'");
			// Проверяем, существует ли индекс
			$indexExists = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_role'")->fetch();
			if (!$indexExists) {
				$conn->exec("CREATE INDEX idx_role ON users(role)");
			}
			echo "Поле role добавлено\n";
		} else {
			echo "Поле role уже существует, пропускаем\n";
		}
		
		// Проверяем, существует ли уже поле first_name
		$checkFirstName = $conn->query("SHOW COLUMNS FROM users LIKE 'first_name'")->fetch();
		if (!$checkFirstName) {
			echo "Добавляем поле first_name...\n";
			$conn->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NULL");
			// Проверяем, существует ли индекс
			$indexExists = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_first_name'")->fetch();
			if (!$indexExists) {
				$conn->exec("CREATE INDEX idx_first_name ON users(first_name)");
			}
			echo "Поле first_name добавлено\n";
		} else {
			echo "Поле first_name уже существует, пропускаем\n";
		}
		
		// Проверяем, существует ли уже поле last_name
		$checkLastName = $conn->query("SHOW COLUMNS FROM users LIKE 'last_name'")->fetch();
		if (!$checkLastName) {
			echo "Добавляем поле last_name...\n";
			$conn->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NULL");
			// Проверяем, существует ли индекс
			$indexExists = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_last_name'")->fetch();
			if (!$indexExists) {
				$conn->exec("CREATE INDEX idx_last_name ON users(last_name)");
			}
			echo "Поле last_name добавлено\n";
		} else {
			echo "Поле last_name уже существует, пропускаем\n";
		}
		
		// Назначаем первого пользователя супер-администратором
		echo "\nНазначаем роли существующим пользователям...\n";
		$firstUser = $db->fetchOne("SELECT id FROM users ORDER BY id ASC LIMIT 1");
		
		if ($firstUser) {
			$firstUserId = $firstUser['id'];
			$existingSuperAdmin = $db->fetchOne("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1");
			
			if (!$existingSuperAdmin) {
				echo "Назначаем пользователя с ID {$firstUserId} супер-администратором\n";
				$conn->exec("UPDATE users SET role = 'super_admin' WHERE id = {$firstUserId}");
			} else {
				echo "Супер-администратор уже существует (ID: {$existingSuperAdmin['id']}), пропускаем\n";
			}
			
			// Остальным пользователям назначаем роль guest, если у них нет роли
			$conn->exec("UPDATE users SET role = 'guest' WHERE role IS NULL OR role = ''");
			echo "Остальным пользователям назначена роль guest\n";
		} else {
			echo "Пользователи не найдены, пропускаем назначение ролей\n";
		}
		
		// Коммитим транзакцию
		$conn->commit();
		echo "\nМиграция успешно завершена!\n";
		
	} catch (Exception $e) {
		$conn->rollBack();
		throw $e;
	}
	
} catch (Exception $e) {
	echo "\nОшибка миграции: " . $e->getMessage() . "\n";
	echo "Изменения отменены.\n";
	exit(1);
}
