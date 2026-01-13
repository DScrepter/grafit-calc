/**
 * Главный файл приложения - инициализация роутера
 */

document.addEventListener('DOMContentLoaded', async () => {
	const router = window.router;
	if (!router) {
		console.error('Router not initialized');
		return;
	}

	// Регистрируем страницы в роутере с правильным контекстом
	if (typeof CalculatorPage !== 'undefined') {
		router.register('calculator', CalculatorPage.load.bind(CalculatorPage));
	}
	if (typeof MaterialsPage !== 'undefined') {
		router.register('materials', MaterialsPage.load.bind(MaterialsPage));
	}
	if (typeof OperationsPage !== 'undefined') {
		router.register('operations', OperationsPage.load.bind(OperationsPage));
	}
	if (typeof UnitsPage !== 'undefined') {
		router.register('units', UnitsPage.load.bind(UnitsPage));
	}
	if (typeof ProductTypesPage !== 'undefined') {
		router.register('product_types', ProductTypesPage.load.bind(ProductTypesPage));
	}
	if (typeof CoefficientsPage !== 'undefined') {
		router.register('coefficients', CoefficientsPage.load.bind(CoefficientsPage));
	}
	if (typeof ProductsPage !== 'undefined') {
		router.register('products', ProductsPage.load.bind(ProductsPage));
	}
	if (typeof MigrationsPage !== 'undefined') {
		router.register('migrations', MigrationsPage.load.bind(MigrationsPage), { requiresSuperAdmin: true });
	}
	if (typeof UsersPage !== 'undefined') {
		router.register('users', UsersPage.load.bind(UsersPage), { requiresAdmin: true });
	}

	// Если это не SPA страница (нет workspaceContent), не инициализируем роутер
	const workspaceContent = document.getElementById('workspaceContent');
	if (!workspaceContent) {
		return;
	}

	// Инициализируем роутер (он сам проверит авторизацию)
	await router.checkAuth();

	// Инициализация иконки чата и обновление уведомлений в навигации
	if (typeof ChatIcon !== 'undefined') {
		// ChatIcon уже инициализирован
		let badgeUpdateInterval = null;
		
		// Функция для безопасного обновления бейджа
		const safeUpdateBadge = () => {
			updateUsersNavBadge().catch(error => {
				// Игнорируем ошибки, чтобы не блокировать работу
				console.error('Ошибка обновления бейджа:', error);
			});
		};
		
		safeUpdateBadge();
		badgeUpdateInterval = setInterval(safeUpdateBadge, 10000); // Обновляем каждые 10 секунд
	}

	async function updateUsersNavBadge() {
		const badge = document.getElementById('usersNavBadge');
		const link = document.getElementById('usersNavLink');
		if (!badge || !link) return;

		try {
			const authData = await API.checkAuth();
			if (!authData.logged_in || !authData.user) {
				badge.style.display = 'none';
				return;
			}

			const isSupport = authData.user.role === 'support' || 
			                authData.user.role === 'super_admin' || 
			                authData.user.role === 'admin';

			if (!isSupport) {
				badge.style.display = 'none';
				return;
			}

			// Проверяем, что метод существует
			if (typeof API.getSupportChats !== 'function') {
				return;
			}

			// Получаем список чатов и считаем общее количество непрочитанных
			const chats = await API.getSupportChats();
			let totalUnread = 0;
			for (const chat of chats) {
				if (chat.unread_count) {
					totalUnread += parseInt(chat.unread_count) || 0;
				}
			}

			if (totalUnread > 0) {
				badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
				badge.style.display = 'inline-block';
			} else {
				badge.style.display = 'none';
			}
		} catch (error) {
			// Игнорируем ошибки 503 и проблемы с форматом ответа
			if (error.message && 
			    !error.message.includes('503') &&
			    !error.message.includes('Service Unavailable') &&
			    !error.message.includes('неверный формат ответа')) {
				console.error('Ошибка обновления уведомления в навигации:', error);
			}
		}
	}

	// Обработка выхода
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

	// Загружаем начальную страницу после регистрации всех страниц
	await router.handleRoute();
});

// Экспортируем для обратной совместимости
window.loadPage = (pageId) => router.navigate(`/calculator?page=${pageId}`);
