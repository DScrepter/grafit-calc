/**
 * API клиент для работы с бэкендом
 */

// Единый базовый путь к API
const API_BASE = '/backend/api';

// Класс для логирования ошибок
class ErrorLogger {
	static async log(level, message, context = {}) {
		try {
			const logData = {
				level,
				message,
				context: {
					...context,
					url: window.location.href,
					path: window.location.pathname,
					userAgent: navigator.userAgent,
				}
			};

			// Отправляем на сервер асинхронно, не блокируя выполнение
			fetch(`${API_BASE}/log.php`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(logData),
			}).catch(err => {
				// Если не удалось отправить лог, просто выводим в консоль
				console.error('Не удалось отправить лог на сервер:', err);
			});
		} catch (error) {
			console.error('Ошибка при логировании:', error);
		}
	}

	static error(message, context = {}) {
		this.log('ERROR', message, context);
	}

	static warning(message, context = {}) {
		this.log('WARNING', message, context);
	}

	static info(message, context = {}) {
		this.log('INFO', message, context);
	}

	static exception(error, context = {}) {
		const message = error.message || String(error);
		const errorContext = {
			...context,
			errorName: error.name,
			errorStack: error.stack,
		};
		this.log('EXCEPTION', message, errorContext);
	}
}

class API {
	static async request(endpoint, options = {}) {
		const url = `${API_BASE}${endpoint}`;
		const defaultOptions = {
			headers: {
				'Content-Type': 'application/json',
			},
		};

		const config = { ...defaultOptions, ...options };

		if (config.body && typeof config.body === 'object') {
			config.body = JSON.stringify(config.body);
		}

		try {
			const response = await fetch(url, config);
			
			// Проверяем, что ответ является JSON
			const contentType = response.headers.get('content-type');
			let data;
			
			if (contentType && contentType.includes('application/json')) {
				data = await response.json();
			} else {
				// Если ответ не JSON, читаем как текст
				const text = await response.text();
				ErrorLogger.error('API вернул не-JSON ответ', {
					url,
					status: response.status,
					contentType,
					responseText: text.substring(0, 500), // Первые 500 символов
				});
				throw new Error(`Сервер вернул неверный формат ответа (ожидался JSON, получен ${contentType || 'неизвестный'})`);
			}

			if (!response.ok) {
				const errorMessage = data.error || `HTTP ${response.status}: ${response.statusText}`;
				ErrorLogger.error('API запрос завершился с ошибкой', {
					url,
					method: config.method || 'GET',
					status: response.status,
					error: errorMessage,
				});
				throw new Error(errorMessage);
			}

			return data;
		} catch (error) {
			// Логируем все ошибки
			if (error instanceof TypeError && error.message.includes('fetch')) {
				ErrorLogger.error('Ошибка сети при запросе к API', {
					url,
					method: config.method || 'GET',
					error: error.message,
				});
			} else if (error instanceof SyntaxError) {
				ErrorLogger.error('Ошибка парсинга JSON ответа', {
					url,
					method: config.method || 'GET',
					error: error.message,
				});
			} else {
				ErrorLogger.exception(error, {
					url,
					method: config.method || 'GET',
				});
			}
			
			console.error('API Error:', error);
			throw error;
		}
	}

	// Авторизация
	static async login(username, password) {
		return this.request('/auth.php', {
			method: 'POST',
			body: { action: 'login', username, password },
		});
	}

	static async register(username, email, password, first_name = null, last_name = null) {
		return this.request('/auth.php', {
			method: 'POST',
			body: { action: 'register', username, email, password, first_name, last_name },
		});
	}

	static async logout() {
		return this.request('/auth.php', {
			method: 'POST',
			body: { action: 'logout' },
		});
	}

	static async checkAuth() {
		return this.request('/auth.php', {
			method: 'GET',
		});
	}

	// Материалы
	static async getMaterials() {
		return this.request('/materials.php');
	}

	static async getMaterial(id) {
		return this.request(`/materials.php?id=${id}`);
	}

	static async createMaterial(data) {
		return this.request('/materials.php', {
			method: 'POST',
			body: data,
		});
	}

	static async updateMaterial(data) {
		return this.request('/materials.php', {
			method: 'PUT',
			body: data,
		});
	}

	static async deleteMaterial(id) {
		return this.request(`/materials.php?id=${id}`, {
			method: 'DELETE',
		});
	}

	// Единицы измерения
	static async getUnits() {
		return this.request('/units.php');
	}

	// Операции
	static async getOperations() {
		return this.request('/operations.php');
	}

	static async getOperation(id) {
		return this.request(`/operations.php?id=${id}`);
	}

	// Типы изделий
	static async getProductTypes() {
		return this.request('/product_types.php');
	}

	static async getProductType(id) {
		return this.request(`/product_types.php?id=${id}`);
	}

	static async createProductType(data) {
		return this.request('/product_types.php', {
			method: 'POST',
			body: data,
		});
	}

	static async updateProductType(data) {
		return this.request('/product_types.php', {
			method: 'PUT',
			body: data,
		});
	}

	static async deleteProductType(id) {
		return this.request(`/product_types.php?id=${id}`, {
			method: 'DELETE',
		});
	}

	// Коэффициенты
	static async getCoefficients() {
		return this.request('/coefficients.php');
	}

	// Расчет
	static async calculate(data) {
		return this.request('/calculate.php', {
			method: 'POST',
			body: data,
		});
	}

	// Пользователи
	static async getUsers() {
		return this.request('/users.php');
	}

	static async getUser(id) {
		return this.request(`/users.php?id=${id}`);
	}

	static async updateUser(data) {
		return this.request('/users.php', {
			method: 'PUT',
			body: data,
		});
	}

	static async deleteUser(id) {
		return this.request(`/users.php?id=${id}`, {
			method: 'DELETE',
		});
	}

	// Профиль текущего пользователя
	static async getProfile() {
		return this.request('/profile.php');
	}

	static async updateProfile(data) {
		return this.request('/profile.php', {
			method: 'PUT',
			body: data,
		});
	}
}

// Экспортируем ErrorLogger для использования в других модулях
window.ErrorLogger = ErrorLogger;
