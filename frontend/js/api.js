/**
 * API клиент для работы с бэкендом
 */

// Единый базовый путь к API
const API_BASE = '/backend/api';
window.API_BASE = API_BASE;

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

	// Сохраненные расчеты
	static async getCalculations(page = 1, limit = 50) {
		return this.request(`/calculations.php?page=${page}&limit=${limit}`);
	}

	static async getCalculation(id) {
		return this.request(`/calculations.php?id=${id}`);
	}

	static async saveCalculation(data) {
		return this.request('/calculations.php', {
			method: 'POST',
			body: data,
		});
	}

	static async updateCalculation(data) {
		return this.request('/calculations.php', {
			method: 'PUT',
			body: data,
		});
	}

	static async deleteCalculation(id) {
		return this.request(`/calculations.php?id=${id}`, {
			method: 'DELETE',
		});
	}

	static async exportCalculation(id) {
		try {
			// Запрашиваем PDF напрямую с сервера
			const response = await fetch(`${API_BASE}/export.php?id=${id}&format=pdf`);

			if (!response.ok) {
				// Если сервер вернул JSON (TCPDF не установлен), используем клиентскую генерацию
				const contentType = response.headers.get('content-type');
				if (contentType && contentType.includes('application/json')) {
					const data = await response.json();
					if (data && data.calculation) {
						this.generatePDFFromData(data, data.calculation.product_name || 'calculation');
						return;
					}
				}
				const errorText = await response.text();
				throw new Error('Ошибка получения PDF: ' + errorText);
			}

			// Если сервер вернул PDF, скачиваем его
			const contentType = response.headers.get('content-type');
			if (contentType && contentType.includes('application/pdf')) {
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				const contentDisposition = response.headers.get('content-disposition');
				let filename = 'calculation.pdf';
				if (contentDisposition) {
					// Обрабатываем filename*=UTF-8''...
					const utf8Match = contentDisposition.match(/filename\*=UTF-8''(.+)/i);
					if (utf8Match) {
						filename = decodeURIComponent(utf8Match[1]);
					} else {
						const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
						if (filenameMatch) {
							filename = filenameMatch[1];
						}
					}
				}
				a.download = filename;
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			} else {
				// Если вернулся JSON, используем клиентскую генерацию
				const data = await response.json();
				if (data && data.calculation) {
					this.generatePDFFromData(data, data.calculation.product_name || 'calculation');
				} else {
					throw new Error('Неожиданный формат ответа от сервера');
				}
			}
		} catch (error) {
			console.error('Export error:', error);
			alert('Ошибка экспорта: ' + error.message);
		}
	}

	static generatePDFHTML(data) {
		const calc = data.calculation;
		const params = data.parameters || {};
		const ops = data.operations || [];
		const result = data.result || {};
		const paramLabels = data.parameter_labels || {};

		const productName = this.escapeHtml(calc.product_name || '');
		const materialName = this.escapeHtml(calc.material_name || '');
		const productTypeName = this.escapeHtml(calc.product_type_name || '');
		const createdAt = new Date(calc.created_at).toLocaleString('ru-RU');

		// Используем inline стили для лучшей совместимости с html2canvas
		// Используем шрифты с поддержкой кириллицы
		let html = `<div style="font-family: 'Arial', 'DejaVu Sans', 'Liberation Sans', sans-serif; padding: 20px; color: rgb(51, 51, 51); background-color: rgb(255, 255, 255);">
			<div style="border-bottom: 2px solid rgb(51, 51, 51); padding-bottom: 10px; margin-bottom: 20px;">
				<h1 style="margin: 0px; font-size: 24px; color: rgb(0, 0, 0);">Калькуляция себестоимости</h1>
				<p style="margin: 5px 0px; color: rgb(0, 0, 0);">Дата создания: ${createdAt}</p>
			</div>
			
			<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid rgb(221, 221, 221);">
				<tr>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; width: 200px; color: rgb(0, 0, 0);">Название изделия</td>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${productName}</td>
				</tr>
				<tr>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; color: rgb(0, 0, 0);">Материал</td>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${materialName}</td>
				</tr>
				<tr>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; color: rgb(0, 0, 0);">Тип изделия</td>
					<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${productTypeName}</td>
				</tr>
			</table>`;

		// Параметры
		if (Object.keys(params).length > 0) {
			html += `
			<div style="margin-top: 30px;">
				<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid rgb(51, 51, 51); padding-bottom: 5px; color: rgb(0, 0, 0);">Параметры изделия</div>
				<table style="width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid rgb(221, 221, 221);">
					<thead>
						<tr>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Параметр</th>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Значение</th>
						</tr>
					</thead>
					<tbody>`;
			for (const [key, value] of Object.entries(params)) {
				// Используем лейбл если есть, иначе имя параметра
				const label = paramLabels[key] || key;
				html += `
						<tr>
							<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.escapeHtml(label)}</td>
							<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.escapeHtml(String(value))}</td>
						</tr>`;
			}
			html += `</tbody></table></div>`;
		}

		// Результаты
		if (Object.keys(result).length > 0) {
			html += `
			<div style="margin-top: 30px;">
				<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid rgb(51, 51, 51); padding-bottom: 5px; color: rgb(0, 0, 0);">Результаты расчета</div>
				<table style="width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid rgb(221, 221, 221);">
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Объем заготовки</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.workpiece_volume || 0, 2)} мм³</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Объем изделия</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.product_volume || 0, 2)} мм³</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Объем отходов</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.waste_volume || 0, 2)} мм³</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Масса заготовки</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.workpiece_mass || 0, 4)} кг</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Масса изделия</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.product_mass || 0, 4)} кг</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Масса отходов</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.waste_mass || 0, 4)} кг</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Стоимость материала</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.material_cost || 0, 2)} руб</td>
					</tr>
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Зарплата (операции)</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.total_operations_cost || 0, 2)} руб</td>
					</tr>`;

			if (result.coefficients && result.coefficients.length > 0) {
				html += `
					<tr>
						<td colspan="2" style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0); font-weight: bold;">Коэффициенты:</td>
					</tr>`;
				result.coefficients.forEach(coef => {
					html += `
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.escapeHtml(coef.name)} (${coef.value}%)</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(coef.amount || 0, 2)} руб</td>
					</tr>`;
				});
				html += `
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">Итого коэффициенты</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(result.coefficients_cost || 0, 2)} руб</td>
					</tr>`;
			}

			html += `
				</table>
				<div style="font-size: 18px; font-weight: bold; color: rgb(0, 0, 0); margin-top: 20px; padding-top: 10px; border-top: 2px solid rgb(51, 51, 51);">
					Общая себестоимость: ${this.formatNumber(result.total_cost_without_packaging || 0, 2)} руб
				</div>
			</div>`;
		}

		// Операции
		if (ops.length > 0) {
			html += `
			<div style="margin-top: 30px;">
				<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid rgb(51, 51, 51); padding-bottom: 5px; color: rgb(0, 0, 0);">Операции</div>
				<table style="width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid rgb(221, 221, 221);">
					<thead>
						<tr>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Номер</th>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Описание</th>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Коэф. сложности</th>
							<th style="padding: 8px; border: 1px solid rgb(221, 221, 221); background-color: rgb(245, 245, 245); font-weight: bold; text-align: left; color: rgb(0, 0, 0);">Стоимость</th>
						</tr>
					</thead>
					<tbody>`;
			ops.forEach(op => {
				html += `
					<tr>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.escapeHtml(op.operation_number || '')}</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.escapeHtml(op.operation_description || '')}</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(op.complexity_coefficient || 1, 2)}</td>
						<td style="padding: 8px; border: 1px solid rgb(221, 221, 221); color: rgb(0, 0, 0);">${this.formatNumber(op.total_cost || 0, 2)} руб</td>
					</tr>`;
			});
			html += `</tbody></table></div>`;
		}

		html += `</div>`;
		return html;
	}

	static async generatePDFFromData(data, filename) {
		// Отправляем данные на сервер для генерации PDF
		try {
			const response = await fetch(`${API_BASE}/export.php?format=pdf`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json; charset=utf-8',
				},
				body: JSON.stringify(data),
				credentials: 'include'
			});

			if (!response.ok) {
				const errorText = await response.text();
				throw new Error('Ошибка генерации PDF: ' + errorText);
			}

			// Если сервер вернул PDF, скачиваем его
			const contentType = response.headers.get('content-type');
			if (contentType && contentType.includes('application/pdf')) {
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				const contentDisposition = response.headers.get('content-disposition');
				let pdfFilename = (filename || 'calculation') + '.pdf';
				if (contentDisposition) {
					// Обрабатываем filename*=UTF-8''...
					const utf8Match = contentDisposition.match(/filename\*=UTF-8''(.+)/i);
					if (utf8Match) {
						pdfFilename = decodeURIComponent(utf8Match[1]);
					} else {
						const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
						if (filenameMatch) {
							pdfFilename = filenameMatch[1];
						}
					}
				}
				a.download = pdfFilename;
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			} else {
				// Если вернулся JSON с ошибкой
				const errorData = await response.json();
				throw new Error(errorData.error || 'Неожиданный формат ответа от сервера');
			}
		} catch (error) {
			console.error('Ошибка генерации PDF:', error);
			alert('Ошибка генерации PDF: ' + error.message);
		}
	}

	static generatePDF(html, filename) {
		// Используем html2pdf.js для генерации PDF из HTML с поддержкой кириллицы
		if (typeof html2pdf === 'undefined') {
			alert('Библиотека для генерации PDF не загружена. Пожалуйста, обновите страницу.');
			return;
		}

		// Создаем временный элемент для рендеринга
		const element = document.createElement('div');
		element.innerHTML = html;
		element.style.position = 'absolute';
		element.style.left = '-9999px';
		document.body.appendChild(element);

		// Настройки для html2pdf
		const opt = {
			margin: [10, 10, 10, 10],
			filename: (filename || 'calculation') + '.pdf',
			image: { type: 'jpeg', quality: 0.98 },
			html2canvas: {
				scale: 2,
				useCORS: true,
				letterRendering: true,
				logging: false,
				allowTaint: false,
				foreignObjectRendering: true
			},
			jsPDF: {
				unit: 'mm',
				format: 'a4',
				orientation: 'portrait',
				compress: true
			}
		};

		// Генерируем и скачиваем PDF
		html2pdf()
			.set(opt)
			.from(element)
			.save()
			.then(() => {
				// Удаляем временный элемент
				document.body.removeChild(element);
			})
			.catch((error) => {
				console.error('Ошибка генерации PDF:', error);
				alert('Ошибка генерации PDF: ' + error.message);
				// Удаляем временный элемент даже при ошибке
				if (document.body.contains(element)) {
					document.body.removeChild(element);
				}
			});
	}

	static escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	static formatNumber(num, decimals) {
		return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
	}

	// Обновления базы данных (только для супер-администраторов)
	static async getMigrationsStatus() {
		return this.request('/migrations.php');
	}

	static async applyMigrations() {
		return this.request('/migrations.php', {
			method: 'POST',
			body: {},
		});
	}

	static async applyMigration(migrationName) {
		return this.request('/migrations.php', {
			method: 'POST',
			body: { migration: migrationName },
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
