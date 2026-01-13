/**
 * Единый роутер для SPA приложения
 */

class Router {
	constructor() {
		this.routes = new Map();
		this.currentPage = null;
		this.currentUser = null;
		this.init();
	}

	init() {
		// Инициализируем только после загрузки DOM
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', () => this.setup());
		} else {
			this.setup();
		}
	}

	setup() {
		// Обработка навигации через History API
		window.addEventListener('popstate', (e) => {
			this.handleRoute();
		});

		// Обработка кликов по ссылкам (только для внутренних ссылок приложения)
		document.addEventListener('click', (e) => {
			const link = e.target.closest('a[href^="/"]');
			if (link && !link.hasAttribute('data-external')) {
				const href = link.getAttribute('href');
				// Перехватываем только ссылки на calculator и reference
				if (href.startsWith('/calculator') || href.startsWith('/reference/')) {
					e.preventDefault();
					this.navigate(href);
				}
				// Для /profile и /login позволяем обычный переход
			}
		});

		// Первичная загрузка - ждем регистрации страниц в app.js
		// app.js зарегистрирует страницы и вызовет handleRoute сам
	}

	register(path, handler, options = {}) {
		this.routes.set(path, { handler, ...options });
	}

	async navigate(path) {
		window.history.pushState({}, '', path);
		await this.handleRoute();
	}

	async handleRoute() {
		const path = window.location.pathname;

		// Если это страница логина или профиля, не обрабатываем через роутер
		if (path === '/login' || path === '/profile') {
			return;
		}

		const searchParams = new URLSearchParams(window.location.search);
		const page = searchParams.get('page') || this.getPageFromPath(path);

		// Проверяем авторизацию
		if (!this.currentUser) {
			await this.checkAuth();
		}

		// Проверяем права доступа
		if (this.requiresAuth(page) && !this.currentUser) {
			window.location.href = '/login';
			return;
		}

		if (this.requiresAccess(page) && this.currentUser?.role === 'guest') {
			window.location.href = '/profile';
			return;
		}

		// Загружаем страницу
		await this.loadPage(page);
	}

	getPageFromPath(path) {
		if (path === '/calculator' || path === '/') {
			return 'calculator';
		}
		if (path.startsWith('/reference/')) {
			return path.replace('/reference/', '');
		}
		return 'calculator';
	}

	requiresAuth(page) {
		return !['login', 'register'].includes(page);
	}

	requiresAccess(page) {
		return ['calculator', 'materials', 'operations', 'units', 'product_types', 'coefficients'].includes(page);
	}

	async checkAuth() {
		try {
			const authData = await API.checkAuth();
			if (authData.logged_in) {
				this.currentUser = authData.user;
				this.updateUserUI();
			}
		} catch (error) {
			console.error('Auth check failed:', error);
		}
	}

	updateUserUI() {
		const usernameEl = document.getElementById('username');
		if (usernameEl && this.currentUser) {
			const displayName = [
				this.currentUser.first_name,
				this.currentUser.last_name
			].filter(Boolean).join(' ') || this.currentUser.username;

			if (!usernameEl.textContent) {
				usernameEl.textContent = displayName;
			}

			usernameEl.classList.add('username-link');
			usernameEl.style.cursor = 'pointer';
			usernameEl.style.textDecoration = 'underline';
			// Для /profile используем обычный переход, не через роутер
			usernameEl.onclick = () => {
				window.location.href = '/profile';
			};
		}

		// Обновляем навигацию
		this.updateNavigation();
	}

	updateNavigation() {
		if (!this.currentUser) return;

		const referencesGroup = document.getElementById('referencesGroup');
		const calculatorsGroup = document.getElementById('calculatorsGroup');
		const adminGroup = document.getElementById('adminGroup');

		if (this.currentUser.role === 'guest') {
			if (referencesGroup) referencesGroup.style.display = 'none';
			if (calculatorsGroup) calculatorsGroup.style.display = 'none';
		} else {
			if (referencesGroup) referencesGroup.style.display = 'block';
			if (calculatorsGroup) calculatorsGroup.style.display = 'block';
		}

		if ((this.currentUser.role === 'super_admin' || this.currentUser.role === 'admin') && adminGroup) {
			adminGroup.style.display = 'block';
		} else if (adminGroup) {
			adminGroup.style.display = 'none';
		}
	}

	async loadPage(pageId) {
		const content = document.getElementById('workspaceContent');
		if (!content) return;

		// Обновляем активный пункт навигации
		document.querySelectorAll('.nav-items li').forEach(li => {
			li.classList.remove('active');
			if (li.dataset.page === pageId) {
				li.classList.add('active');
			}
		});

		try {
			// Проверка прав доступа
			if (this.currentUser?.role === 'guest' && this.requiresAccess(pageId)) {
				content.innerHTML = '<div class="error-message">У вас нет доступа к этой странице. Обратитесь к администратору для получения прав.</div>';
				return;
			}

			// Проверка прав администратора для страницы users
			if (pageId === 'users') {
				if (!this.currentUser || (this.currentUser.role !== 'super_admin' && this.currentUser.role !== 'admin')) {
					content.innerHTML = '<div class="error-message">У вас нет доступа к управлению пользователями.</div>';
					return;
				}
			}

			// Загружаем страницу через зарегистрированный handler
			const route = this.routes.get(pageId);
			if (route && route.handler) {
				await route.handler(content);
			} else {
				// Fallback на старые страницы
				await this.loadLegacyPage(pageId, content);
			}
		} catch (error) {
			console.error('Error loading page:', error);
			content.innerHTML = `<div class="error-message">Ошибка загрузки: ${error.message}</div>`;
		}
	}

	async loadLegacyPage(pageId, content) {
		switch (pageId) {
			case 'calculator':
				if (typeof CalculatorPage !== 'undefined') {
					await CalculatorPage.load(content);
				}
				break;
			case 'materials':
				if (typeof MaterialsPage !== 'undefined') {
					await MaterialsPage.load(content);
				}
				break;
			case 'operations':
				if (typeof OperationsPage !== 'undefined') {
					await OperationsPage.load(content);
				}
				break;
			case 'units':
				if (typeof UnitsPage !== 'undefined') {
					await UnitsPage.load(content);
				}
				break;
			case 'product_types':
				if (typeof ProductTypesPage !== 'undefined') {
					await ProductTypesPage.load(content);
				}
				break;
			case 'coefficients':
				if (typeof CoefficientsPage !== 'undefined') {
					await CoefficientsPage.load(content);
				}
				break;
			case 'users':
				if (typeof UsersPage !== 'undefined') {
					await UsersPage.load(content);
				}
				break;
			default:
				content.innerHTML = '<h2>Страница не найдена</h2>';
		}
	}
}

// Создаем глобальный экземпляр роутера
window.router = new Router();
