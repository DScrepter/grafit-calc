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
	if (typeof UsersPage !== 'undefined') {
		router.register('users', UsersPage.load.bind(UsersPage), { requiresAdmin: true });
	}

	// Инициализируем роутер (он сам проверит авторизацию)
	await router.checkAuth();

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
