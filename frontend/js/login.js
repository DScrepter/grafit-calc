/**
 * Логика страницы входа
 */

document.addEventListener('DOMContentLoaded', () => {
	const loginForm = document.getElementById('loginForm');
	const registerForm = document.getElementById('registerForm');
	const showRegister = document.getElementById('showRegister');
	const showLogin = document.getElementById('showLogin');
	const errorMessage = document.getElementById('errorMessage');

	// Переключение между формами
	showRegister.addEventListener('click', () => {
		loginForm.style.display = 'none';
		registerForm.style.display = 'block';
		errorMessage.style.display = 'none';
	});

	showLogin.addEventListener('click', () => {
		registerForm.style.display = 'none';
		loginForm.style.display = 'block';
		errorMessage.style.display = 'none';
	});

	// Обработка входа
	loginForm.addEventListener('submit', async (e) => {
		e.preventDefault();
		errorMessage.style.display = 'none';

		const username = document.getElementById('username').value;
		const password = document.getElementById('password').value;

		try {
			const response = await API.login(username, password);
			// Проверяем роль пользователя и перенаправляем соответственно
			if (response.user && response.user.role === 'guest') {
				window.location.href = '/profile';
			} else {
				window.location.href = '/calculator';
			}
		} catch (error) {
			errorMessage.textContent = error.message;
			errorMessage.style.display = 'block';
		}
	});

	// Обработка регистрации
	registerForm.addEventListener('submit', async (e) => {
		e.preventDefault();
		errorMessage.style.display = 'none';

		const usernameEl = document.getElementById('reg_username');
		const emailEl = document.getElementById('reg_email');
		const passwordEl = document.getElementById('reg_password');
		const first_nameEl = document.getElementById('reg_first_name');
		const last_nameEl = document.getElementById('reg_last_name');

		if (!usernameEl || !emailEl || !passwordEl) {
			errorMessage.textContent = 'Ошибка: не найдены обязательные поля формы';
			errorMessage.style.display = 'block';
			errorMessage.style.backgroundColor = '#fee';
			errorMessage.style.color = '#c33';
			return;
		}

		const username = usernameEl.value;
		const email = emailEl.value;
		const password = passwordEl.value;
		const first_name = first_nameEl ? first_nameEl.value.trim() || null : null;
		const last_name = last_nameEl ? last_nameEl.value.trim() || null : null;

		try {
			await API.register(username, email, password, first_name, last_name);
			errorMessage.textContent = 'Регистрация успешна! Теперь вы можете войти.';
			errorMessage.style.display = 'block';
			errorMessage.style.backgroundColor = '#efe';
			errorMessage.style.color = '#3c3';
			setTimeout(() => {
				registerForm.style.display = 'none';
				loginForm.style.display = 'block';
			}, 2000);
		} catch (error) {
			errorMessage.textContent = error.message;
			errorMessage.style.display = 'block';
			errorMessage.style.backgroundColor = '#fee';
			errorMessage.style.color = '#c33';
		}
	});
});
