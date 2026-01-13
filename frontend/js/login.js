/**
 * Логика страницы входа
 */

document.addEventListener('DOMContentLoaded', () => {
	const loginForm = document.getElementById('loginForm');
	const registerForm = document.getElementById('registerForm');
	const showRegister = document.getElementById('showRegister');
	const showLogin = document.getElementById('showLogin');
	const errorMessage = document.getElementById('errorMessage');

	// Если элементов формы нет (не страница логина), выходим
	if (!loginForm || !registerForm) {
		return;
	}

	// Обработчики кнопок "Показать пароль"
	const togglePasswordButtons = document.querySelectorAll('.toggle-password');
	togglePasswordButtons.forEach(button => {
		button.addEventListener('click', () => {
			const targetId = button.getAttribute('data-target');
			const passwordInput = document.getElementById(targetId);
			if (passwordInput) {
				const isPassword = passwordInput.type === 'password';
				passwordInput.type = isPassword ? 'text' : 'password';
				// Обновляем иконку (можно добавить класс для стилизации)
				const svg = button.querySelector('svg');
				if (svg) {
					if (isPassword) {
						// Показываем крестик (скрыть пароль)
						svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
					} else {
						// Показываем глаз (показать пароль)
						svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
					}
				}
			}
		});
	});

	// Переключение между формами
	if (showRegister) {
		showRegister.addEventListener('click', () => {
			loginForm.style.display = 'none';
			registerForm.style.display = 'block';
			if (errorMessage) errorMessage.style.display = 'none';
		});
	}

	if (showLogin) {
		showLogin.addEventListener('click', () => {
			registerForm.style.display = 'none';
			loginForm.style.display = 'block';
			if (errorMessage) errorMessage.style.display = 'none';
		});
	}

	// Обработка входа
	loginForm.addEventListener('submit', async (e) => {
		e.preventDefault();
		if (errorMessage) errorMessage.style.display = 'none';

		const username = document.getElementById('username');
		const password = document.getElementById('password');

		if (!username || !password) {
			return;
		}

		try {
			const response = await API.login(username.value, password.value);

			// Проверяем роль пользователя и перенаправляем соответственно
			// Используем полную перезагрузку страницы, чтобы сессия точно установилась
			if (response.user && response.user.role === 'guest') {
				window.location.href = '/profile';
			} else {
				window.location.href = '/calculator';
			}
		} catch (error) {
			if (errorMessage) {
				errorMessage.textContent = error.message;
				errorMessage.style.display = 'block';
			}
		}
	});

	// Обработка регистрации
	registerForm.addEventListener('submit', async (e) => {
		e.preventDefault();
		if (errorMessage) errorMessage.style.display = 'none';

		const usernameEl = document.getElementById('reg_username');
		const emailEl = document.getElementById('reg_email');
		const passwordEl = document.getElementById('reg_password');
		const passwordConfirmEl = document.getElementById('reg_password_confirm');
		const first_nameEl = document.getElementById('reg_first_name');
		const last_nameEl = document.getElementById('reg_last_name');

		if (!usernameEl || !emailEl || !passwordEl || !passwordConfirmEl) {
			if (errorMessage) {
				errorMessage.textContent = 'Ошибка: не найдены обязательные поля формы';
				errorMessage.style.display = 'block';
				errorMessage.style.backgroundColor = '#fee';
				errorMessage.style.color = '#c33';
			}
			return;
		}

		const username = usernameEl.value.trim();
		const email = emailEl.value.trim();
		const password = passwordEl.value;
		const passwordConfirm = passwordConfirmEl.value;
		const first_name = first_nameEl ? first_nameEl.value.trim() || null : null;
		const last_name = last_nameEl ? last_nameEl.value.trim() || null : null;

		// Валидация имени пользователя (только латиница)
		if (!/^[a-zA-Z0-9_]+$/.test(username)) {
			if (errorMessage) {
				errorMessage.textContent = 'Имя пользователя может содержать только латинские буквы, цифры и подчеркивание';
				errorMessage.style.display = 'block';
				errorMessage.style.backgroundColor = '#fee';
				errorMessage.style.color = '#c33';
			}
			return;
		}

		// Валидация совпадения паролей
		if (password !== passwordConfirm) {
			if (errorMessage) {
				errorMessage.textContent = 'Пароли не совпадают';
				errorMessage.style.display = 'block';
				errorMessage.style.backgroundColor = '#fee';
				errorMessage.style.color = '#c33';
			}
			return;
		}

		try {
			await API.register(username, email, password, first_name, last_name);

			// Автоматический вход после успешной регистрации
			try {
				const loginResponse = await API.login(username, password);
				// Используем полную перезагрузку страницы, чтобы сессия точно установилась
				if (loginResponse.user && loginResponse.user.role === 'guest') {
					window.location.href = '/profile';
				} else {
					window.location.href = '/calculator';
				}
			} catch (loginError) {
				// Если автоматический вход не удался, показываем сообщение
				if (errorMessage) {
					errorMessage.textContent = 'Регистрация успешна, но не удалось выполнить автоматический вход. Пожалуйста, войдите вручную.';
					errorMessage.style.display = 'block';
					errorMessage.style.backgroundColor = '#fee';
					errorMessage.style.color = '#c33';
				}
			}
		} catch (error) {
			if (errorMessage) {
				errorMessage.textContent = error.message;
				errorMessage.style.display = 'block';
				errorMessage.style.backgroundColor = '#fee';
				errorMessage.style.color = '#c33';
			}
		}
	});
});
