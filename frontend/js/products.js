/**
 * Страница списка сохраненных изделий
 */

const ProductsPage = {
	calculations: [],
	currentPage: 1,
	totalPages: 1,

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Сохраненные изделия</h1>
				<div class="search-box mb-20">
					<input type="text" id="searchInput" placeholder="Поиск по названию..." oninput="ProductsPage.filterCalculations()">
				</div>
				<div id="calculationsContainer">
					<div class="loading">Загрузка...</div>
				</div>
				<div id="pagination" class="pagination"></div>
			</div>
		`;

		await this.loadCalculations();
	},

	async loadCalculations(page = 1) {
		this.currentPage = page;
		try {
			const response = await API.getCalculations(page, 50);
			this.calculations = response.calculations || [];
			this.totalPages = response.pages || 1;
			this.renderCalculations();
			this.renderPagination();
		} catch (error) {
			document.getElementById('calculationsContainer').innerHTML = 
				'<div class="error-message">Ошибка загрузки: ' + error.message + '</div>';
		}
	},

	renderCalculations() {
		const container = document.getElementById('calculationsContainer');
		if (this.calculations.length === 0) {
			container.innerHTML = '<div class="empty-state">Нет сохраненных расчетов</div>';
			return;
		}

		let html = '<table class="data-table">';
		html += '<thead><tr>';
		html += '<th>Название</th>';
		html += '<th>Материал</th>';
		html += '<th>Тип изделия</th>';
		html += '<th>Себестоимость</th>';
		html += '<th>Дата создания</th>';
		html += '<th>Действия</th>';
		html += '</tr></thead><tbody>';

		this.calculations.forEach(calc => {
			const createdAt = new Date(calc.created_at).toLocaleString('ru-RU');
			const totalCost = calc.total_cost ? parseFloat(calc.total_cost).toFixed(2) : '-';
			html += `<tr>
				<td>${this.escapeHtml(calc.product_name)}</td>
				<td>${this.escapeHtml(calc.material_name || '-')}</td>
				<td>${this.escapeHtml(calc.product_type_name || '-')}</td>
				<td>${totalCost} руб</td>
				<td>${createdAt}</td>
				<td>
					<div class="action-buttons">
						<button class="btn btn-small btn-secondary" onclick="ProductsPage.view(${calc.id})" title="Просмотр">👁</button>
						<button class="btn btn-small btn-primary" onclick="ProductsPage.edit(${calc.id})" title="Редактировать">✏️</button>
						<button class="btn btn-small btn-secondary" onclick="ProductsPage.export(${calc.id})" title="Экспорт PDF">📄</button>
						<button class="btn btn-small btn-secondary" onclick="ProductsPage.print(${calc.id})" title="Печать">🖨</button>
						<button class="btn btn-small btn-danger" onclick="ProductsPage.delete(${calc.id})" title="Удалить">🗑</button>
					</div>
				</td>
			</tr>`;
		});

		html += '</tbody></table>';
		container.innerHTML = html;
	},

	renderPagination() {
		const container = document.getElementById('pagination');
		if (this.totalPages <= 1) {
			container.innerHTML = '';
			return;
		}

		let html = '<div class="pagination-controls">';
		if (this.currentPage > 1) {
			html += `<button class="btn btn-small" onclick="ProductsPage.loadCalculations(${this.currentPage - 1})">← Назад</button>`;
		}
		html += `<span>Страница ${this.currentPage} из ${this.totalPages}</span>`;
		if (this.currentPage < this.totalPages) {
			html += `<button class="btn btn-small" onclick="ProductsPage.loadCalculations(${this.currentPage + 1})">Вперед →</button>`;
		}
		html += '</div>';
		container.innerHTML = html;
	},

	filterCalculations() {
		const search = document.getElementById('searchInput').value.toLowerCase();
		const rows = document.querySelectorAll('#calculationsContainer tbody tr');
		rows.forEach(row => {
			const text = row.textContent.toLowerCase();
			row.style.display = text.includes(search) ? '' : 'none';
		});
	},

	async view(id) {
		try {
			const calculation = await API.getCalculation(id);
			this.showViewModal(calculation);
		} catch (error) {
			alert('Ошибка загрузки расчета: ' + error.message);
		}
	},

	showViewModal(calculation) {
		const result = calculation.result || {};
		const parameters = calculation.parameters || {};
		const operations = calculation.operations || [];

		let html = '<div class="modal" style="display: flex;">';
		html += '<div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">';
		html += '<div class="modal-header">';
		html += '<h3>Калькуляция: ' + this.escapeHtml(calculation.product_name) + '</h3>';
		html += '<button class="modal-close" onclick="this.closest(\'.modal\').remove()">&times;</button>';
		html += '</div>';
		html += '<div class="modal-body">';

		// Основная информация
		html += '<div class="field-group">';
		html += '<div class="field-group-title">Основная информация</div>';
		html += '<div class="result-row"><span class="result-label">Название:</span><span class="result-value">' + this.escapeHtml(calculation.product_name) + '</span></div>';
		html += '<div class="result-row"><span class="result-label">Материал:</span><span class="result-value">' + this.escapeHtml(calculation.material_name || '-') + '</span></div>';
		html += '<div class="result-row"><span class="result-label">Тип изделия:</span><span class="result-value">' + this.escapeHtml(calculation.product_type_name || '-') + '</span></div>';
		html += '</div>';

		// Параметры
		if (Object.keys(parameters).length > 0) {
			html += '<div class="field-group">';
			html += '<div class="field-group-title">Параметры изделия</div>';
			Object.keys(parameters).forEach(key => {
				html += '<div class="result-row"><span class="result-label">' + this.escapeHtml(key) + ':</span><span class="result-value">' + parameters[key] + '</span></div>';
			});
			html += '</div>';
		}

		// Результаты
		if (Object.keys(result).length > 0) {
			html += '<div class="field-group">';
			html += '<div class="field-group-title">Результаты расчета</div>';
			if (result.workpiece_volume !== undefined) {
				html += '<div class="result-row"><span class="result-label">Объем заготовки:</span><span class="result-value">' + parseFloat(result.workpiece_volume).toFixed(2) + ' мм³</span></div>';
			}
			if (result.product_volume !== undefined) {
				html += '<div class="result-row"><span class="result-label">Объем изделия:</span><span class="result-value">' + parseFloat(result.product_volume).toFixed(2) + ' мм³</span></div>';
			}
			if (result.total_cost_without_packaging !== undefined) {
				html += '<div class="result-row"><span class="result-label">Общая себестоимость:</span><span class="result-value"><strong>' + parseFloat(result.total_cost_without_packaging).toFixed(2) + ' руб</strong></span></div>';
			}
			html += '</div>';
		}

		html += '</div>';
		html += '<div class="modal-footer">';
		html += '<button class="btn btn-primary" onclick="ProductsPage.edit(' + calculation.id + '); this.closest(\'.modal\').remove();">Редактировать</button>';
		html += '<button class="btn btn-secondary" onclick="this.closest(\'.modal\').remove()">Закрыть</button>';
		html += '</div>';
		html += '</div></div>';

		const modal = document.createElement('div');
		modal.innerHTML = html;
		document.body.appendChild(modal.firstElementChild);
	},

	edit(id) {
		// Переходим на калькулятор с параметром edit
		window.router.navigate(`/calculator?edit=${id}`);
	},

	export(id) {
		API.exportCalculation(id);
	},

	print(id) {
		const API_BASE = '/backend/api';
		const printWindow = window.open(`${API_BASE}/export.php?id=${id}`, '_blank');
		printWindow.onload = () => {
			printWindow.print();
		};
	},

	async delete(id) {
		if (!confirm('Вы уверены, что хотите удалить этот расчет?')) {
			return;
		}

		try {
			await API.deleteCalculation(id);
			alert('Расчет успешно удален');
			await this.loadCalculations(this.currentPage);
		} catch (error) {
			alert('Ошибка удаления: ' + error.message);
		}
	},

	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
};
