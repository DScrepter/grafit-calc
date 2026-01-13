<?php
// Страница профиля пользователя (для гостей)
require_once __DIR__ . '/backend/classes/Auth.php';
require_once __DIR__ . '/backend/classes/Assets.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
	header('Location: /login');
	exit;
}

$userData = $auth->getUserData();
$userRole = $auth->getUserRole();
$roleNames = [
	'super_admin' => 'Супер-администратор',
	'admin' => 'Администратор',
	'user' => 'Пользователь',
	'guest' => 'Гость'
];
$roleName = $roleNames[$userRole] ?? $userRole;

// Инициализация Assets
Assets::init('');
Assets::enqueue_profile_assets();

// Переменные для шаблонов
$page_title = 'Профиль - Калькулятор себестоимости';
$sidebar_type = 'profile';
// Формируем имя для отображения: если есть имя и фамилия - используем их, иначе username
$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
$username = $fullName ?: $userData['username'];

// Подключаем header
require __DIR__ . '/templates/header.php';
?>
	<div class="app-container">
		<?php require __DIR__ . '/templates/sidebar.php'; ?>

		<!-- Рабочая область -->
		<main class="workspace">
			<div class="page-header">
				<h2>Мой профиль</h2>
				<button class="btn btn-primary" id="toggleEditBtn">Редактировать</button>
			</div>
			<div class="profile-container">
				<!-- Режим просмотра -->
				<div class="profile-card" id="profileView">
					<div class="profile-info">
						<h3>Информация о пользователе</h3>
						<div class="info-row">
							<label>Имя пользователя:</label>
							<span id="viewUsername"><?php echo htmlspecialchars($userData['username']); ?></span>
						</div>
						<div class="info-row">
							<label>Email:</label>
							<span id="viewEmail"><?php echo htmlspecialchars($userData['email']); ?></span>
						</div>
						<div class="info-row">
							<label>Имя:</label>
							<span id="viewFirstName"><?php echo htmlspecialchars($userData['first_name'] ?? '-'); ?></span>
						</div>
						<div class="info-row">
							<label>Фамилия:</label>
							<span id="viewLastName"><?php echo htmlspecialchars($userData['last_name'] ?? '-'); ?></span>
						</div>
						<div class="info-row">
							<label>Роль:</label>
							<span><?php echo htmlspecialchars($roleName); ?></span>
						</div>
					</div>
					<?php if ($userRole === 'guest'): ?>
					<div class="access-message">
						<div class="alert alert-info">
							<h4>Ограниченный доступ</h4>
							<p>Ваш аккаунт имеет статус "Гость". Для получения доступа к справочникам и калькулятору обратитесь к администратору системы.</p>
						</div>
					</div>
					<?php else: ?>
					<div class="access-message">
						<div class="alert alert-success">
							<h4>Полный доступ</h4>
							<p>У вас есть доступ ко всем функциям системы. Вы можете использовать <a href="/calculator">калькулятор</a> и работать со <a href="/reference/materials">справочниками</a>.</p>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<!-- Режим редактирования -->
				<div class="profile-card" id="profileEdit" style="display: none;">
					<form id="profileForm">
						<div class="form-group">
							<label for="editUsername">Имя пользователя *</label>
							<input type="text" id="editUsername" name="username" required>
						</div>
						<div class="form-group">
							<label for="editEmail">Email *</label>
							<input type="email" id="editEmail" name="email" required>
						</div>
						<div class="form-group">
							<label for="editFirstName">Имя</label>
							<input type="text" id="editFirstName" name="first_name">
						</div>
						<div class="form-group">
							<label for="editLastName">Фамилия</label>
							<input type="text" id="editLastName" name="last_name">
						</div>
						<div class="form-group">
							<label for="editCurrentPassword">Текущий пароль (для смены пароля)</label>
							<input type="password" id="editCurrentPassword" name="current_password" placeholder="Оставьте пустым, если не меняете пароль">
						</div>
						<div class="form-group">
							<label for="editPassword">Новый пароль</label>
							<input type="password" id="editPassword" name="password" placeholder="Оставьте пустым, если не меняете пароль">
						</div>
						<div class="form-actions">
							<button type="submit" class="btn btn-primary">Сохранить</button>
							<button type="button" class="btn btn-secondary" id="cancelEditBtn">Отмена</button>
						</div>
					</form>
				</div>
			</div>
		</main>
	</div>

	<script>
		let currentUserData = <?php echo json_encode($userData, JSON_UNESCAPED_UNICODE); ?>;
		const isGuest = <?php echo $userRole === 'guest' ? 'true' : 'false'; ?>;

		document.addEventListener('DOMContentLoaded', async () => {
			const logoutBtn = document.getElementById('logoutBtn');
			if (logoutBtn) {
				logoutBtn.addEventListener('click', async () => {
					try {
						await API.logout();
						window.location.href = '/login';
					} catch (error) {
						console.error('Logout error:', error);
					}
				});
			}

			// Для гостя автоматически показываем форму редактирования
			if (isGuest) {
				showEditForm();
				// Скрываем кнопку редактирования для гостей, так как форма открыта автоматически
				const toggleBtn = document.getElementById('toggleEditBtn');
				if (toggleBtn) toggleBtn.style.display = 'none';
			}

			// Обработчик клика на имя пользователя
			const usernameEl = document.getElementById('username');
			if (usernameEl) {
				usernameEl.addEventListener('click', () => {
					showEditForm();
				});
			}

			// Кнопка редактирования
			const toggleEditBtn = document.getElementById('toggleEditBtn');
			if (toggleEditBtn) {
				toggleEditBtn.addEventListener('click', () => {
					showEditForm();
				});
			}

			// Кнопка отмены
			const cancelEditBtn = document.getElementById('cancelEditBtn');
			if (cancelEditBtn) {
				cancelEditBtn.addEventListener('click', () => {
					hideEditForm();
				});
			}

			// Заполняем форму данными
			loadUserDataToForm();

			// Обработка отправки формы
			const profileForm = document.getElementById('profileForm');
			if (profileForm) {
				profileForm.addEventListener('submit', async (e) => {
					e.preventDefault();
					await saveProfile();
				});
			}

			// Периодически проверяем обновления роли (каждые 5 секунд)
			setInterval(async () => {
				try {
					const authData = await API.checkAuth();
					if (authData.logged_in && authData.user) {
						const currentRole = '<?php echo $userRole; ?>';
						const newRole = authData.user.role;
						
						// Если роль изменилась, перезагружаем страницу
						if (currentRole !== newRole) {
							window.location.reload();
						}
					}
				} catch (error) {
					console.error('Error checking auth:', error);
				}
			}, 5000);
		});

		function showEditForm() {
			document.getElementById('profileView').style.display = 'none';
			document.getElementById('profileEdit').style.display = 'block';
			const toggleBtn = document.getElementById('toggleEditBtn');
			if (toggleBtn) toggleBtn.style.display = 'none';
			loadUserDataToForm();
		}

		function hideEditForm() {
			document.getElementById('profileView').style.display = 'block';
			document.getElementById('profileEdit').style.display = 'none';
			const toggleBtn = document.getElementById('toggleEditBtn');
			// Кнопка всегда видна в режиме просмотра (кроме гостей, у которых форма открыта автоматически)
			if (toggleBtn) {
				if (isGuest) {
					toggleBtn.style.display = 'none';
				} else {
					toggleBtn.style.display = 'inline-block';
				}
			}
		}

		function loadUserDataToForm() {
			document.getElementById('editUsername').value = currentUserData.username || '';
			document.getElementById('editEmail').value = currentUserData.email || '';
			document.getElementById('editFirstName').value = currentUserData.first_name || '';
			document.getElementById('editLastName').value = currentUserData.last_name || '';
			document.getElementById('editCurrentPassword').value = '';
			document.getElementById('editPassword').value = '';
		}

		async function saveProfile() {
			const firstNameValue = document.getElementById('editFirstName').value.trim();
			const lastNameValue = document.getElementById('editLastName').value.trim();
			
			const formData = {
				username: document.getElementById('editUsername').value,
				email: document.getElementById('editEmail').value,
				// Всегда отправляем first_name и last_name, даже если пустые (для удаления)
				first_name: firstNameValue === '' ? null : firstNameValue,
				last_name: lastNameValue === '' ? null : lastNameValue
			};

			const currentPassword = document.getElementById('editCurrentPassword').value;
			const newPassword = document.getElementById('editPassword').value;

			if (newPassword) {
				if (!currentPassword) {
					alert('Для смены пароля необходимо указать текущий пароль');
					return;
				}
				formData.password = newPassword;
				formData.current_password = currentPassword;
			}

			try {
				const updatedUser = await API.updateProfile(formData);
				currentUserData = updatedUser;
				
				// Обновляем отображение
				document.getElementById('viewUsername').textContent = updatedUser.username;
				document.getElementById('viewEmail').textContent = updatedUser.email;
				document.getElementById('viewFirstName').textContent = updatedUser.first_name || '-';
				document.getElementById('viewLastName').textContent = updatedUser.last_name || '-';
				
				// Обновляем имя в сайдбаре
				const usernameEl = document.getElementById('username');
				if (usernameEl) {
					const displayName = [updatedUser.first_name, updatedUser.last_name].filter(Boolean).join(' ') || updatedUser.username;
					usernameEl.textContent = displayName;
				}

				hideEditForm();
				alert('Профиль успешно обновлен');
			} catch (error) {
				alert('Ошибка сохранения: ' + error.message);
			}
		}
	</script>
	<style>
		.profile-container {
			padding: 20px;
		}
		.profile-card {
			background: white;
			border-radius: 8px;
			padding: 30px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		.profile-info h3 {
			margin-top: 0;
			margin-bottom: 20px;
			color: #333;
		}
		.info-row {
			display: flex;
			padding: 12px 0;
			border-bottom: 1px solid #eee;
		}
		.info-row:last-child {
			border-bottom: none;
		}
		.info-row label {
			font-weight: bold;
			width: 200px;
			color: #666;
		}
		.info-row span {
			color: #333;
		}
		.access-message {
			margin-top: 30px;
		}
		.alert {
			padding: 20px;
			border-radius: 6px;
			border-left: 4px solid;
		}
		.alert-info {
			background: #e3f2fd;
			border-color: #2196f3;
		}
		.alert-success {
			background: #e8f5e9;
			border-color: #4caf50;
		}
		.alert h4 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		.alert p {
			margin: 0;
			line-height: 1.6;
		}
		.alert a {
			color: #2196f3;
			text-decoration: none;
		}
		.alert a:hover {
			text-decoration: underline;
		}
		.form-group {
			margin-bottom: 20px;
		}
		.form-group label {
			display: block;
			margin-bottom: 5px;
			font-weight: bold;
			color: #333;
		}
		.form-group input {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
		}
		.form-actions {
			margin-top: 20px;
			display: flex;
			gap: 10px;
		}
		.username-link:hover {
			color: #2196f3;
		}
	</style>
	<?php require __DIR__ . '/templates/footer.php'; ?>
