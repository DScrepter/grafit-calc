<?php
// Проверяем, авторизован ли пользователь
require_once __DIR__ . '/backend/classes/Auth.php';
require_once __DIR__ . '/backend/classes/Assets.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
	// Гостей перенаправляем на страницу профиля, остальных - на калькулятор
	$userRole = $auth->getUserRole();
	if ($userRole === 'guest' || $userRole === null) {
		header('Location: /profile');
	} else {
		header('Location: /calculator');
	}
	exit;
}

// Инициализация Assets
Assets::init('');
Assets::enqueue_login_assets();

// Переменные для шаблонов
$page_title = 'Вход - Калькулятор себестоимости';
$body_class = 'login-page';

// Подключаем header
require __DIR__ . '/templates/header.php';
?>
	<div class="login-container">
		<div class="login-box">
			<h1>Калькулятор себестоимости</h1>
			<form id="loginForm">
				<div class="form-group">
					<label for="username">Имя пользователя или Email</label>
					<input type="text" id="username" name="username" required autofocus>
				</div>
				<div class="form-group">
					<label for="password">Пароль</label>
					<input type="password" id="password" name="password" required>
				</div>
				<button type="submit" class="btn btn-primary">Войти</button>
				<div class="form-footer">
					<button type="button" id="showRegister" class="btn-link">Регистрация</button>
				</div>
			</form>
			<form id="registerForm" style="display: none;">
				<div class="form-group">
					<label for="reg_username">Имя пользователя</label>
					<input type="text" id="reg_username" name="username" required>
				</div>
				<div class="form-group">
					<label for="reg_email">Email</label>
					<input type="email" id="reg_email" name="email" required>
				</div>
				<div class="form-group">
					<label for="reg_password">Пароль</label>
					<input type="password" id="reg_password" name="password" required>
				</div>
				<button type="submit" class="btn btn-primary">Зарегистрироваться</button>
				<div class="form-footer">
					<button type="button" id="showLogin" class="btn-link">Вход</button>
				</div>
			</form>
			<div id="errorMessage" class="error-message" style="display: none;"></div>
		</div>
	</div>
	<?php require __DIR__ . '/templates/footer.php'; ?>
