/**
 * Страница управления пользователями
 */

const UsersPage = {
	users: [],
	currentSort: { column: null, direction: 'asc' },

	async load(container) {
		container.innerHTML = `
			<div class="page-header">
				<h2>Управление пользователями</h2>
				<button class="btn btn-primary" id="refreshUsersBtn">Обновить</button>
			</div>
			<div class="table-container">
				<table class="data-table" id="usersTable">
					<thead>
						<tr>
							<th class="sortable" data-column="id">ID</th>
							<th class="sortable" data-column="first_name">Имя</th>
							<th class="sortable" data-column="last_name">Фамилия</th>
							<th class="sortable" data-column="username">Имя пользователя</th>
							<th class="sortable" data-column="email">Email</th>
							<th class="sortable" data-column="role">Роль</th>
							<th class="sortable" data-column="created_at">Дата регистрации</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody id="usersTableBody">
						<tr>
							<td colspan="8" class="text-center">Загрузка...</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="userModal" class="modal" style="display: none;">
				<div class="modal-content">
					<div class="modal-header">
						<h3 id="userModalTitle">Редактирование пользователя</h3>
						<button class="modal-close" onclick="UsersPage.closeModal()">&times;</button>
					</div>
					<div class="modal-body">
						<form id="userForm">
							<input type="hidden" id="userId" name="id">
							<div class="form-group">
								<label for="userUsername">Имя пользователя *</label>
								<input type="text" id="userUsername" name="username" required>
							</div>
							<div class="form-group">
								<label for="userEmail">Email *</label>
								<input type="email" id="userEmail" name="email" required>
							</div>
							<div class="form-group">
								<label for="userFirstName">Имя</label>
								<input type="text" id="userFirstName" name="first_name">
							</div>
							<div class="form-group">
								<label for="userLastName">Фамилия</label>
								<input type="text" id="userLastName" name="last_name">
							</div>
							<div class="form-group">
								<label for="userRole">Роль *</label>
								<select id="userRole" name="role" required>
									<option value="guest">Гость</option>
									<option value="user">Пользователь</option>
									<option value="admin">Администратор</option>
									<option value="super_admin" id="superAdminOption" style="display: none;">Супер-администратор</option>
								</select>
							</div>
							<div id="superAdminWarning" class="warning-message" style="display: none;">
								<strong>Внимание!</strong> При передаче роли супер-администратора вы потеряете эту роль и станете администратором.
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary" onclick="UsersPage.saveUser()">Сохранить</button>
						<button class="btn btn-secondary" onclick="UsersPage.closeModal()">Отмена</button>
					</div>
				</div>
			</div>
		`;

		await this.loadUsers();
		this.setupEventListeners();
		this.setupSorting();
	},

	setupSorting() {
		const headers = document.querySelectorAll('#usersTable th.sortable');
		headers.forEach(header => {
			header.style.cursor = 'pointer';
			header.addEventListener('click', () => {
				const column = header.dataset.column;
				this.sortBy(column);
			});
		});
	},

	sortBy(column) {
		if (this.currentSort.column === column) {
			this.currentSort.direction = this.currentSort.direction === 'asc' ? 'desc' : 'asc';
		} else {
			this.currentSort.column = column;
			this.currentSort.direction = 'asc';
		}

		this.users.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			if (column === 'id') {
				aVal = parseInt(aVal) || 0;
				bVal = parseInt(bVal) || 0;
			} else if (column === 'created_at') {
				aVal = new Date(aVal).getTime() || 0;
				bVal = new Date(bVal).getTime() || 0;
			} else {
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return this.currentSort.direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return this.currentSort.direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderUsers();
		this.updateSortIndicators();
	},

	updateSortIndicators() {
		const headers = document.querySelectorAll('#usersTable th.sortable');
		headers.forEach(header => {
			const column = header.dataset.column;
			header.classList.remove('sorted-asc', 'sorted-desc');
			
			if (this.currentSort.column === column) {
				header.classList.add(this.currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
			}
		});
	},

	async loadUsers() {
		const tbody = document.getElementById('usersTableBody');
		try {
			this.users = await API.getUsers();
			
			if (this.users.length === 0) {
				tbody.innerHTML = '<tr><td colspan="8" class="text-center">Пользователи не найдены</td></tr>';
				return;
			}

			if (!this.currentSort.column) {
				this.currentSort = { column: 'id', direction: 'asc' };
			}
			
			const column = this.currentSort.column;
			const direction = this.currentSort.direction;
			
			this.users.sort((a, b) => {
				let aVal = a[column];
				let bVal = b[column];

				if (column === 'id') {
					aVal = parseInt(aVal) || 0;
					bVal = parseInt(bVal) || 0;
				} else if (column === 'created_at') {
					aVal = new Date(aVal).getTime() || 0;
					bVal = new Date(bVal).getTime() || 0;
				} else {
					aVal = (aVal || '').toString().toLowerCase();
					bVal = (bVal || '').toString().toLowerCase();
				}

				if (aVal < bVal) return direction === 'asc' ? -1 : 1;
				if (aVal > bVal) return direction === 'asc' ? 1 : -1;
				return 0;
			});

			this.renderUsers();
			this.updateSortIndicators();
		} catch (error) {
			tbody.innerHTML = `<tr><td colspan="8" class="text-center error-message">Ошибка загрузки: ${error.message}</td></tr>`;
		}
	},

	renderUsers() {
		const tbody = document.getElementById('usersTableBody');
		if (!tbody) return;

		tbody.innerHTML = this.users.map(user => {
			const roleNames = {
				'super_admin': 'Супер-администратор',
				'admin': 'Администратор',
				'user': 'Пользователь',
				'guest': 'Гость'
			};
			const roleName = roleNames[user.role] || user.role;
			const createdDate = new Date(user.created_at).toLocaleDateString('ru-RU');

			return `
				<tr>
					<td>${user.id}</td>
					<td>${user.first_name || '-'}</td>
					<td>${user.last_name || '-'}</td>
					<td>${user.username}</td>
					<td>${user.email}</td>
					<td>${roleName}</td>
					<td>${createdDate}</td>
					<td>
						<div class="action-buttons">
							<button class="btn btn-small btn-primary" onclick="UsersPage.editUser(${user.id})" title="Редактировать">✏️</button>
							${user.role !== 'super_admin' ? `<button class="btn btn-small btn-danger" onclick="UsersPage.deleteUser(${user.id}, '${user.username}')" title="Удалить">🗑</button>` : ''}
						</div>
					</td>
				</tr>
			`;
		}).join('');
	},

	setupEventListeners() {
		const refreshBtn = document.getElementById('refreshUsersBtn');
		if (refreshBtn) {
			refreshBtn.addEventListener('click', () => this.loadUsers());
		}
	},

	async editUser(userId) {
		try {
			const user = await API.getUser(userId);
			const modal = document.getElementById('userModal');
			const form = document.getElementById('userForm');
			const superAdminOption = document.getElementById('superAdminOption');
			const warning = document.getElementById('superAdminWarning');
			
			// Получаем текущего пользователя из роутера или через API
			let currentUser = window.router?.currentUser || null;
			if (!currentUser) {
				try {
					const authData = await API.checkAuth();
					if (authData.logged_in && authData.user) {
						currentUser = authData.user;
						// Сохраняем в роутер для будущих использований
						if (window.router) {
							window.router.currentUser = currentUser;
						}
					}
				} catch (error) {
					console.error('Error getting current user:', error);
				}
			}
			
			const isSuperAdmin = currentUser && currentUser.role === 'super_admin';
			const isEditingSuperAdmin = user.role === 'super_admin';

			// Заполняем форму
			document.getElementById('userId').value = user.id;
			document.getElementById('userUsername').value = user.username;
			document.getElementById('userEmail').value = user.email;
			document.getElementById('userFirstName').value = user.first_name || '';
			document.getElementById('userLastName').value = user.last_name || '';
			document.getElementById('userRole').value = user.role;

			// Показываем опцию супер-админа только для супер-админа
			if (superAdminOption) {
				superAdminOption.style.display = isSuperAdmin ? 'block' : 'none';
			}

			// Показываем предупреждение при передаче роли супер-админа
			if (warning) {
				const roleSelect = document.getElementById('userRole');
				const checkWarning = () => {
					if (isSuperAdmin && roleSelect.value === 'super_admin' && user.id !== currentUser.id && !isEditingSuperAdmin) {
						warning.style.display = 'block';
					} else {
						warning.style.display = 'none';
					}
				};
				roleSelect.addEventListener('change', checkWarning);
				checkWarning();
			}

			// Блокируем редактирование супер-админа для обычных админов
			if (!isSuperAdmin && isEditingSuperAdmin) {
				form.querySelectorAll('input, select').forEach(el => el.disabled = true);
			} else {
				form.querySelectorAll('input, select').forEach(el => el.disabled = false);
			}

			// Перемещаем модальное окно в body для правильного позиционирования
			if (modal.parentElement !== document.body) {
				document.body.appendChild(modal);
			}
			modal.style.display = 'flex';
		} catch (error) {
			alert('Ошибка загрузки пользователя: ' + error.message);
		}
	},

	async saveUser() {
		const form = document.getElementById('userForm');
		const formData = {
			id: parseInt(document.getElementById('userId').value),
			username: document.getElementById('userUsername').value,
			email: document.getElementById('userEmail').value,
			first_name: document.getElementById('userFirstName').value.trim() || null,
			last_name: document.getElementById('userLastName').value.trim() || null,
			role: document.getElementById('userRole').value
		};

		// Получаем текущего пользователя из роутера или через API
		let currentUser = window.router?.currentUser || null;
		if (!currentUser) {
			try {
				const authData = await API.checkAuth();
				if (authData.logged_in && authData.user) {
					currentUser = authData.user;
					// Сохраняем в роутер для будущих использований
					if (window.router) {
						window.router.currentUser = currentUser;
					}
				}
			} catch (error) {
				console.error('Error getting current user:', error);
			}
		}

		// Проверка на передачу роли супер-админа
		if (currentUser && currentUser.role === 'super_admin' && formData.role === 'super_admin' && formData.id !== currentUser.id) {
			if (!confirm('Вы уверены, что хотите передать роль супер-администратора? Вы потеряете эту роль и станете администратором.')) {
				return;
			}
		}

		try {
			await API.updateUser(formData);
			this.closeModal();
			await this.loadUsers();
			
			// Если изменили свою роль, перезагружаем страницу
			if (currentUser && formData.id === currentUser.id && formData.role !== currentUser.role) {
				window.location.reload();
			}
		} catch (error) {
			alert('Ошибка сохранения: ' + error.message);
		}
	},

	async deleteUser(userId, username) {
		if (!confirm(`Вы уверены, что хотите удалить пользователя "${username}"?`)) {
			return;
		}

		try {
			await API.deleteUser(userId);
			await this.loadUsers();
		} catch (error) {
			alert('Ошибка удаления: ' + error.message);
		}
	},

	closeModal() {
		const modal = document.getElementById('userModal');
		if (modal) {
			modal.style.display = 'none';
		}
		const form = document.getElementById('userForm');
		if (form) {
			form.reset();
		}
	}
};

// Экспортируем для использования в других модулях
window.UsersPage = UsersPage;
