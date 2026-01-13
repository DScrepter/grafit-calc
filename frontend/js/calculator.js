/**
 * Калькулятор себестоимости
 */

const CalculatorPage = {
	async load(container) {
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
			return;
		}

		const productType = JSON.parse(selectedOption.dataset.type);
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

		// Собираем параметры
		const parameters = {};
		const productType = JSON.parse(document.getElementById('productTypeSelect').options[document.getElementById('productTypeSelect').selectedIndex].dataset.type);
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
				operations: operations
			});

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
		html += '<div class="result-row"><span class="result-label">Зарплата (операции):</span><span class="result-value">' + result.total_operations_cost.toFixed(2) + ' руб</span></div>';

		if (result.coefficients && result.coefficients.length > 0) {
			html += '<div style="margin-top: 10px;"><strong>Коэффициенты:</strong></div>';
			result.coefficients.forEach(coef => {
				html += `<div class="result-row"><span class="result-label">${coef.name} (${coef.value}%):</span><span class="result-value">${coef.amount.toFixed(2)} руб</span></div>`;
			});
			html += '<div class="result-row"><span class="result-label">Итого коэффициенты:</span><span class="result-value">' + result.coefficients_cost.toFixed(2) + ' руб</span></div>';
		}

		html += '<hr style="margin: 15px 0;">';
		html += '<div class="result-row"><span class="result-label">Общая себестоимость:</span><span class="result-value">' + result.total_cost_without_packaging.toFixed(2) + ' руб</span></div>';

		container.innerHTML = html;
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
