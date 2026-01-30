/**
 * Калькулятор себестоимости
 */

const CalculatorPage = {
	currentResult: null,
	currentProductType: null,
	currentCalculationId: null,

	async load(container) {
		// Очищаем данные предыдущего расчета
		this.operations = [];
		this.currentResult = null;
		this.currentCalculationId = null;

		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Основной калькулятор</h1>
				<form id="calculatorForm" class="calculator-form">
					<div class="field-group">
						<div class="field-group-title">Основные параметры</div>
						<div class="form-row">
							<div class="form-group">
								<label for="productName">Название изделия</label>
								<input type="text" id="productName" required>
							</div>
							<div class="form-group">
								<label for="materialSelect">Материал</label>
								<select id="materialSelect" required></select>
							</div>
							<div class="form-group">
								<label for="productTypeSelect">Тип изделия</label>
								<select id="productTypeSelect" required></select>
							</div>
							<div class="form-group">
								<label for="quantityInput">Количество (шт)</label>
								<input type="number" id="quantityInput" min="1" value="5" title="≥5 — без надбавки, &lt;5 — надбавка за мелкий заказ 1.5">
							</div>
						</div>
					</div>

					<div class="field-group" id="parametersGroup">
						<div class="field-group-title">Параметры изделия</div>
						<div id="parametersContainer"></div>
					</div>

					<div class="field-group">
						<div class="field-group-title">Операции</div>
						<div class="operations-list" id="operationsList"></div>
						<button type="button" class="btn btn-primary" onclick="CalculatorPage.addOperation()">Добавить операцию</button>
					</div>

					<button type="submit" class="btn btn-primary mt-20">Рассчитать</button>
				</form>

				<div id="results" class="calculator-results" style="display: none;"></div>
			</div>
		`;

		// Проверяем, есть ли ID расчета в URL для редактирования
		const urlParams = new URLSearchParams(window.location.search);
		const calculationId = urlParams.get('edit');
		if (calculationId) {
			await this.loadCalculationForEdit(calculationId);
		} else {
			// Если не режим редактирования, очищаем список операций
			this.renderOperations();
		}

		await this.loadMaterials();
		await this.loadProductTypes();
		this.setupForm();
	},

	async loadMaterials() {
		const materials = await API.getMaterials();
		const select = document.getElementById('materialSelect');
		select.innerHTML = '<option value="">Выберите материал</option>';
		materials.forEach(material => {
			const option = document.createElement('option');
			option.value = material.id;
			option.textContent = `${material.mark} (${parseFloat(material.price).toFixed(2)} руб/кг)`;
			select.appendChild(option);
		});
	},

	async loadProductTypes() {
		const types = await API.getProductTypes();
		const select = document.getElementById('productTypeSelect');
		select.innerHTML = '<option value="">Выберите тип изделия</option>';
		types.forEach(type => {
			const option = document.createElement('option');
			option.value = type.id;
			option.textContent = type.name;
			option.dataset.type = JSON.stringify(type);
			select.appendChild(option);
		});

		select.addEventListener('change', () => {
			this.loadParameters();
		});
	},

	loadParameters() {
		const select = document.getElementById('productTypeSelect');
		const selectedOption = select.options[select.selectedIndex];
		if (!selectedOption || !selectedOption.dataset.type) {
			document.getElementById('parametersContainer').innerHTML = '';
			this.currentProductType = null;
			return;
		}

		const productType = JSON.parse(selectedOption.dataset.type);
		this.currentProductType = productType; // Сохраняем тип изделия для экспорта
		const container = document.getElementById('parametersContainer');
		container.innerHTML = '';

		productType.parameters.forEach(param => {
			const group = document.createElement('div');
			group.className = 'form-group';
			group.innerHTML = `
				<label for="param_${param.name}">${param.label} (${param.unit})${param.required ? ' *' : ''}</label>
				<input type="number" id="param_${param.name}" name="${param.name}" 
					step="0.01" ${param.required ? 'required' : ''} 
					${param.default_value ? `value="${param.default_value}"` : ''}>
			`;
			container.appendChild(group);
		});
	},

	setupForm() {
		document.getElementById('calculatorForm').addEventListener('submit', async (e) => {
			e.preventDefault();
			await this.calculate();
		});
	},

	operations: [],

	async loadCalculationForEdit(calculationId) {
		try {
			const calculation = await API.getCalculation(calculationId);
			if (!calculation) {
				alert('Расчет не найден');
				return;
			}

			// Заполняем форму
			document.getElementById('productName').value = calculation.product_name || '';
			document.getElementById('materialSelect').value = calculation.material_id || '';
			document.getElementById('productTypeSelect').value = calculation.product_type_id || '';
			const qtyInput = document.getElementById('quantityInput');
			if (qtyInput) qtyInput.value = calculation.result?.quantity ?? 5;

			// Загружаем параметры после выбора типа изделия
			if (calculation.product_type_id) {
				await this.loadProductTypes();
				document.getElementById('productTypeSelect').value = calculation.product_type_id;
				this.loadParameters();

				// Заполняем параметры после небольшой задержки
				setTimeout(() => {
					if (calculation.parameters) {
						Object.keys(calculation.parameters).forEach(key => {
							const input = document.getElementById(`param_${key}`);
							if (input) {
								input.value = calculation.parameters[key];
							}
						});
					}
				}, 100);
			}

			// Загружаем операции
			this.operations = calculation.operations || [];
			this.renderOperations();

			// Сохраняем ID для обновления
			this.currentCalculationId = calculation.id;

			// Если есть результат, показываем его
			if (calculation.result) {
				this.showResults(calculation.result);
			}
		} catch (error) {
			alert('Ошибка загрузки расчета: ' + error.message);
		}
	},

	addOperation() {
		// Открываем диалог выбора операции
		OperationsDialog.open().then(operation => {
			if (operation) {
				this.operations.push(operation);
				this.renderOperations();
			}
		});
	},

	removeOperation(index) {
		this.operations.splice(index, 1);
		this.renderOperations();
	},

	renderOperations() {
		const container = document.getElementById('operationsList');
		container.innerHTML = '';

		this.operations.forEach((op, index) => {
			const item = document.createElement('div');
			item.className = 'operation-item';
			item.innerHTML = `
				<div class="operation-info">
					<div class="operation-number">${op.operation_number}</div>
					<div class="operation-description">${op.operation_description}</div>
					<div>Коэф. сложности: ${op.complexity_coefficient}</div>
				</div>
				<div class="operation-cost">${op.total_cost.toFixed(2)} руб</div>
				<button type="button" class="btn btn-danger btn-small" onclick="CalculatorPage.removeOperation(${index})">Удалить</button>
			`;
			container.appendChild(item);
		});
	},

	async calculate() {
		const productName = document.getElementById('productName').value;
		const materialId = parseInt(document.getElementById('materialSelect').value);
		const productTypeId = parseInt(document.getElementById('productTypeSelect').value);
		const quantity = parseInt(document.getElementById('quantityInput').value) || 5;

		// Собираем параметры
		const parameters = {};
		// Используем сохраненный тип изделия или получаем из DOM
		const productType = this.currentProductType || JSON.parse(document.getElementById('productTypeSelect').options[document.getElementById('productTypeSelect').selectedIndex].dataset.type);
		if (!this.currentProductType) {
			this.currentProductType = productType; // Сохраняем если еще не сохранен
		}
		productType.parameters.forEach(param => {
			const value = document.getElementById(`param_${param.name}`).value;
			if (value) {
				parameters[param.name] = parseFloat(value);
			}
		});

		// Подготавливаем операции
		const operations = this.operations.map(op => ({
			operation_id: op.operation_id,
			complexity_coefficient: op.complexity_coefficient
		}));

		try {
			const result = await API.calculate({
				product_name: productName,
				material_id: materialId,
				product_type_id: productTypeId,
				parameters: parameters,
				operations: operations,
				quantity: quantity
			});

			// Сохраняем текущие данные для сохранения
			// Используем полную информацию об операциях из результата расчета
			this.currentResult = {
				product_name: productName,
				material_id: materialId,
				product_type_id: productTypeId,
				parameters: parameters,
				operations: result.operations || operations, // Используем полные операции из результата
				result: result
			};

			this.showResults(result);
		} catch (error) {
			alert('Ошибка расчета: ' + error.message);
		}
	},

	showResults(result) {
		const container = document.getElementById('results');
		container.style.display = 'block';

		let html = '<h3>Результаты расчета</h3>';
		html += '<div class="result-row"><span class="result-label">Изделие:</span><span class="result-value">' + result.product_name + '</span></div>';
		html += '<div class="result-row"><span class="result-label">Материал:</span><span class="result-value">' + result.material_name + '</span></div>';
		html += '<div class="result-row"><span class="result-label">Тип изделия:</span><span class="result-value">' + result.product_type_name + '</span></div>';
		html += '<hr style="margin: 15px 0;">';
		html += '<div class="result-row"><span class="result-label">Объем заготовки:</span><span class="result-value">' + result.workpiece_volume.toFixed(2) + ' мм³</span></div>';
		html += '<div class="result-row"><span class="result-label">Объем изделия:</span><span class="result-value">' + result.product_volume.toFixed(2) + ' мм³</span></div>';
		html += '<div class="result-row"><span class="result-label">Объем отходов:</span><span class="result-value">' + result.waste_volume.toFixed(2) + ' мм³</span></div>';
		html += '<div class="result-row"><span class="result-label">Масса заготовки:</span><span class="result-value">' + result.workpiece_mass.toFixed(4) + ' кг</span></div>';
		html += '<div class="result-row"><span class="result-label">Масса изделия:</span><span class="result-value">' + result.product_mass.toFixed(4) + ' кг</span></div>';
		html += '<div class="result-row"><span class="result-label">Масса отходов:</span><span class="result-value">' + result.waste_mass.toFixed(4) + ' кг</span></div>';
		html += '<hr style="margin: 15px 0;">';
		html += '<div class="result-row"><span class="result-label">Стоимость материала:</span><span class="result-value">' + result.material_cost.toFixed(2) + ' руб</span></div>';
		const salaryDisplay = result.salary_with_quantity_coef ?? result.total_operations_cost ?? 0;
		const salaryLabel = (result.quantity_coefficient === 1.5) ? 'Зарплата (операции, K=1.5 за мелкий заказ):' : 'Зарплата (операции):';
		html += '<div class="result-row"><span class="result-label">' + salaryLabel + '</span><span class="result-value">' + salaryDisplay.toFixed(2) + ' руб</span></div>';

		if (result.coefficients && result.coefficients.length > 0) {
			html += '<div style="margin-top: 10px;"><strong>Коэффициенты (налоги):</strong></div>';
			result.coefficients.forEach(coef => {
				html += `<div class="result-row"><span class="result-label">${coef.name} (${coef.value}%):</span><span class="result-value">${(coef.amount ?? 0).toFixed(2)} руб</span></div>`;
			});
			html += '<div class="result-row"><span class="result-label">Итого коэффициенты:</span><span class="result-value">' + (result.coefficients_cost ?? 0).toFixed(2) + ' руб</span></div>';
		}

		if (result.ohr_cost !== undefined) {
			html += '<div class="result-row"><span class="result-label">ОХР (коэф. массы ' + (result.mass_coefficient ?? '-') + '):</span><span class="result-value">' + result.ohr_cost.toFixed(2) + ' руб</span></div>';
		}

		html += '<hr style="margin: 15px 0;">';
		html += '<div class="result-row"><span class="result-label">Общая себестоимость:</span><span class="result-value">' + (result.total_cost_without_packaging ?? 0).toFixed(2) + ' руб</span></div>';
		if (result.total_cost_with_margin !== undefined) {
			html += '<div class="result-row" style="font-weight: 600;"><span class="result-label">Итого с маржой 40%:</span><span class="result-value">' + result.total_cost_with_margin.toFixed(2) + ' руб</span></div>';
		}

		// Кнопки действий
		html += '<div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">';
		html += '<button type="button" class="btn btn-primary" onclick="CalculatorPage.saveCalculation()">Сохранить</button>';
		const exportId = this.currentCalculationId ? this.currentCalculationId : '';
		html += '<button type="button" class="btn btn-secondary" onclick="CalculatorPage.exportCalculation(' + (exportId ? exportId : '') + ')">Экспорт PDF</button>';
		html += '<button type="button" class="btn btn-secondary" onclick="CalculatorPage.printCalculation(' + (exportId ? exportId : '') + ')">Печать</button>';
		html += '</div>';

		container.innerHTML = html;
	},

	async saveCalculation() {
		if (!this.currentResult) {
			alert('Сначала выполните расчет');
			return;
		}

		const productName = document.getElementById('productName').value.trim();
		if (!productName) {
			alert('Название изделия обязательно для заполнения');
			document.getElementById('productName').focus();
			return;
		}

		try {
			if (this.currentCalculationId) {
				// Обновляем существующий расчет
				await API.updateCalculation({
					id: this.currentCalculationId,
					...this.currentResult
				});
				alert('Расчет успешно обновлен');
			} else {
				// Создаем новый расчет
				const response = await API.saveCalculation(this.currentResult);
				this.currentCalculationId = response.id;
				alert('Расчет успешно сохранен');
				// Обновляем кнопки в результатах
				this.showResults(this.currentResult.result);
			}
		} catch (error) {
			alert('Ошибка сохранения: ' + error.message);
		}
	},

	async exportCalculation(calculationId = null) {
		// Если передан ID, используем серверный экспорт
		if (calculationId) {
			await API.exportCalculation(calculationId);
			return;
		}

		// Иначе используем серверную генерацию для несохраненного расчета
		if (!this.currentResult) {
			alert('Сначала выполните расчет');
			return;
		}

		// Генерируем PDF из несохраненного расчета
		// Преобразуем currentResult в формат данных для API
		// Создаем маппинг name -> label для параметров
		const parameterLabels = {};
		if (this.currentProductType && this.currentProductType.parameters) {
			this.currentProductType.parameters.forEach(param => {
				parameterLabels[param.name] = param.label;
			});
		}

		const data = {
			calculation: {
				product_name: this.currentResult.product_name,
				material_name: this.currentResult.result.material_name,
				product_type_name: this.currentResult.result.product_type_name,
				product_type_id: this.currentProductType ? this.currentProductType.id : null,
				created_at: new Date().toISOString()
			},
			parameters: this.currentResult.parameters,
			operations: this.operations,
			result: this.currentResult.result,
			parameter_labels: parameterLabels
		};
		await API.generatePDFFromData(data, this.currentResult.product_name || 'calculation');
	},

	generatePDF(html, filename) {
		// Используем метод из API
		API.generatePDF(html, filename);
	},

	printCalculation(calculationId = null) {
		// Если передан ID, используем серверный экспорт
		if (calculationId) {
			const API_BASE = window.API_BASE || '/backend/api';
			const printWindow = window.open(`${API_BASE}/export.php?id=${calculationId}`, '_blank');
			printWindow.onload = () => {
				printWindow.print();
			};
			return;
		}

		// Иначе используем клиентскую печать для несохраненного расчета
		if (!this.currentResult) {
			alert('Сначала выполните расчет');
			return;
		}

		const html = this.generateExportHTML(this.currentResult);
		const printWindow = window.open('', '_blank');
		printWindow.document.write(html);
		printWindow.document.close();
		printWindow.onload = () => {
			printWindow.print();
		};
	},

	generateExportHTML(data) {
		const result = data.result;
		const productName = data.product_name || '';
		const materialName = result.material_name || '';
		const productTypeName = result.product_type_name || '';
		const createdAt = new Date().toLocaleString('ru-RU');
		const parameters = data.parameters || {};
		const operations = this.operations || [];

		let html = `<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Калькуляция: ${this.escapeHtml(productName)}</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			color: #333;
		}
		.header {
			border-bottom: 2px solid #333;
			padding-bottom: 10px;
			margin-bottom: 20px;
		}
		.header h1 {
			margin: 0;
			font-size: 24px;
		}
		.info-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		.info-table td {
			padding: 8px;
			border: 1px solid #ddd;
		}
		.info-table td:first-child {
			background-color: #f5f5f5;
			font-weight: bold;
			width: 200px;
		}
		.section {
			margin-top: 30px;
		}
		.section-title {
			font-size: 18px;
			font-weight: bold;
			margin-bottom: 10px;
			border-bottom: 1px solid #333;
			padding-bottom: 5px;
		}
		.results-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}
		.results-table th,
		.results-table td {
			padding: 8px;
			border: 1px solid #ddd;
			text-align: left;
		}
		.results-table th {
			background-color: #f5f5f5;
			font-weight: bold;
		}
		.total {
			font-size: 18px;
			font-weight: bold;
			color: #000;
			margin-top: 20px;
			padding-top: 10px;
			border-top: 2px solid #333;
		}
		@media print {
			body { margin: 0; }
			.no-print { display: none; }
		}
	</style>
</head>
<body>
	<div class="header">
		<h1>Калькуляция себестоимости</h1>
		<p>Дата создания: ${createdAt}</p>
	</div>

	<table class="info-table">
		<tr>
			<td>Название изделия</td>
			<td>${this.escapeHtml(productName)}</td>
		</tr>
		<tr>
			<td>Материал</td>
			<td>${this.escapeHtml(materialName)}</td>
		</tr>
		<tr>
			<td>Тип изделия</td>
			<td>${this.escapeHtml(productTypeName)}</td>
		</tr>
	</table>`;

		// Параметры изделия
		if (Object.keys(parameters).length > 0) {
			html += `<div class="section">
			<div class="section-title">Параметры изделия</div>
			<table class="results-table">
				<thead>
					<tr>
						<th>Параметр</th>
						<th>Значение</th>
					</tr>
				</thead>
				<tbody>`;
			for (const [key, value] of Object.entries(parameters)) {
				html += `<tr>
					<td>${this.escapeHtml(key)}</td>
					<td>${this.escapeHtml(String(value))}</td>
				</tr>`;
			}
			html += `</tbody></table></div>`;
		}

		// Результаты расчета
		if (result) {
			html += `<div class="section">
			<div class="section-title">Результаты расчета</div>
			<table class="results-table">
				<tr>
					<td>Объем заготовки</td>
					<td>${this.formatNumber(result.workpiece_volume || 0, 2)} мм³</td>
				</tr>
				<tr>
					<td>Объем изделия</td>
					<td>${this.formatNumber(result.product_volume || 0, 2)} мм³</td>
				</tr>
				<tr>
					<td>Объем отходов</td>
					<td>${this.formatNumber(result.waste_volume || 0, 2)} мм³</td>
				</tr>
				<tr>
					<td>Масса заготовки</td>
					<td>${this.formatNumber(result.workpiece_mass || 0, 4)} кг</td>
				</tr>
				<tr>
					<td>Масса изделия</td>
					<td>${this.formatNumber(result.product_mass || 0, 4)} кг</td>
				</tr>
				<tr>
					<td>Масса отходов</td>
					<td>${this.formatNumber(result.waste_mass || 0, 4)} кг</td>
				</tr>
				<tr>
					<td>Стоимость материала</td>
					<td>${this.formatNumber(result.material_cost || 0, 2)} руб</td>
				</tr>
				<tr>
					<td>Зарплата (операции)</td>
					<td>${this.formatNumber(result.salary_with_quantity_coef ?? result.total_operations_cost ?? 0, 2)} руб</td>
				</tr>`;

			if (result.coefficients && result.coefficients.length > 0) {
				html += `<tr>
					<td colspan="2"><strong>Коэффициенты (налоги):</strong></td>
				</tr>`;
				result.coefficients.forEach(coef => {
					html += `<tr>
						<td>${this.escapeHtml(coef.name)} (${coef.value}%)</td>
						<td>${this.formatNumber(coef.amount || 0, 2)} руб</td>
					</tr>`;
				});
				html += `<tr>
					<td>Итого коэффициенты</td>
					<td>${this.formatNumber(result.coefficients_cost || 0, 2)} руб</td>
				</tr>`;
			}

			if (result.ohr_cost !== undefined) {
				html += `<tr>
					<td>ОХР (коэф. массы ${result.mass_coefficient ?? ''})</td>
					<td>${this.formatNumber(result.ohr_cost, 2)} руб</td>
				</tr>`;
			}

			html += `</table>
			<div class="total">
				Общая себестоимость: ${this.formatNumber(result.total_cost_without_packaging || 0, 2)} руб
			</div>`;
			if (result.total_cost_with_margin !== undefined) {
				html += `
			<div class="total" style="margin-top: 8px; font-size: 1.1em;">
				Итого с маржой 40%: ${this.formatNumber(result.total_cost_with_margin, 2)} руб
			</div>`;
			}
			html += `</div>`;
		}

		// Операции
		if (operations.length > 0) {
			html += `<div class="section">
			<div class="section-title">Операции</div>
			<table class="results-table">
				<thead>
					<tr>
						<th>Номер</th>
						<th>Описание</th>
						<th>Коэф. сложности</th>
						<th>Стоимость</th>
					</tr>
				</thead>
				<tbody>`;
			operations.forEach(op => {
				html += `<tr>
					<td>${this.escapeHtml(op.operation_number || '')}</td>
					<td>${this.escapeHtml(op.operation_description || '')}</td>
					<td>${this.formatNumber(op.complexity_coefficient || 1, 2)}</td>
					<td>${this.formatNumber(op.total_cost || 0, 2)} руб</td>
				</tr>`;
			});
			html += `</tbody></table></div>`;
		}

		html += `</body></html>`;

		return html;
	},

	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	},

	formatNumber(num, decimals) {
		return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
	}
};

// Диалог выбора операции
const OperationsDialog = {
	selectedOperation: null,
	complexityCoefficient: 1.0,

	async open() {
		return new Promise((resolve) => {
			this.resolve = resolve;
			const dialog = document.getElementById('operationDialog');
			dialog.style.display = 'flex';
			this.loadOperations();
		});
	},

	async loadOperations() {
		const operations = await API.getOperations();
		const tbody = document.querySelector('#operationsTable tbody');
		tbody.innerHTML = '';

		operations.forEach(op => {
			const row = document.createElement('tr');
			row.dataset.operationId = op.id;
			row.innerHTML = `
				<td>${op.number}</td>
				<td>${op.description}</td>
				<td>${op.unit_name || '-'}</td>
				<td>${parseFloat(op.cost).toFixed(2)}</td>
			`;
			row.addEventListener('click', () => {
				document.querySelectorAll('#operationsTable tbody tr').forEach(r => r.classList.remove('selected'));
				row.classList.add('selected');
				this.selectedOperation = op;
			});
			tbody.appendChild(row);
		});

		// Поиск
		document.getElementById('operationSearch').addEventListener('input', (e) => {
			const search = e.target.value.toLowerCase();
			Array.from(tbody.children).forEach(row => {
				const text = row.textContent.toLowerCase();
				row.style.display = text.includes(search) ? '' : 'none';
			});
		});
	},

	close() {
		document.getElementById('operationDialog').style.display = 'none';
		this.selectedOperation = null;
		if (this.resolve) {
			this.resolve(null);
		}
	}
};

function closeOperationDialog() {
	OperationsDialog.close();
}

function confirmOperationSelection() {
	if (!OperationsDialog.selectedOperation) {
		alert('Выберите операцию');
		return;
	}

	const coefficient = parseFloat(document.getElementById('complexityCoefficient').value);
	const operation = OperationsDialog.selectedOperation;

	const result = {
		operation_id: operation.id,
		operation_number: operation.number,
		operation_description: operation.description,
		operation_cost: parseFloat(operation.cost),
		complexity_coefficient: coefficient,
		total_cost: parseFloat(operation.cost) * coefficient
	};

	OperationsDialog.resolve(result);
	OperationsDialog.close();
}
