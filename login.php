<?php
// Проверяем, авторизован ли пользователь
require_once __DIR__ . '/backend/classes/Auth.php';

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
					<div class="password-input-wrapper">
						<input type="password" id="password" name="password" required>
						<button type="button" class="toggle-password" data-target="password" aria-label="Показать пароль">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
						</button>
					</div>
				</div>
				<button type="submit" class="btn btn-primary">Войти</button>
				<div class="form-footer">
					<button type="button" id="showRegister" class="btn-link">Регистрация</button>
				</div>
			</form>
			<form id="registerForm" style="display: none;">
				<div class="form-group">
					<label for="reg_username">Имя пользователя</label>
					<input type="text" id="reg_username" name="username" pattern="[a-zA-Z0-9_]+" title="Только латинские буквы, цифры и подчеркивание" required>
				</div>
				<div class="form-group">
					<label for="reg_email">Email</label>
					<input type="email" id="reg_email" name="email" required>
				</div>
				<div class="form-group">
					<label for="reg_password">Пароль</label>
					<div class="password-input-wrapper">
						<input type="password" id="reg_password" name="password" required>
						<button type="button" class="toggle-password" data-target="reg_password" aria-label="Показать пароль">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
						</button>
					</div>
				</div>
				<div class="form-group">
					<label for="reg_password_confirm">Подтверждение пароля</label>
					<div class="password-input-wrapper">
						<input type="password" id="reg_password_confirm" name="password_confirm" required>
						<button type="button" class="toggle-password" data-target="reg_password_confirm" aria-label="Показать пароль">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
								<circle cx="12" cy="12" r="3"></circle>
							</svg>
						</button>
					</div>
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
