/**
 * Страница управления операциями и вспомогательные функции
 */

const OperationsPage = {
	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник операций</h1>
				<button class="btn btn-primary mb-20" onclick="OperationsPage.showAddForm()">Добавить операцию</button>
				<table class="data-table" id="operationsTable">
					<thead>
						<tr>
							<th>Номер</th>
							<th>Описание</th>
							<th>Единица измерения</th>
							<th>Стоимость (руб/ед)</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadOperations();
	},

	async loadOperations() {
		const operations = await API.getOperations();
		const tbody = document.querySelector('#operationsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #operationsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		operations.forEach(operation => {
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${operation.number}</td>
				<td>${operation.description}</td>
				<td>${operation.unit_name || '-'}</td>
				<td>${parseFloat(operation.cost).toFixed(2)}</td>
				<td>
					<div class="action-buttons">
						<button class="btn btn-small btn-primary" onclick="OperationsPage.edit(${operation.id})" title="Редактировать">✏️</button>
						<button class="btn btn-small btn-danger" onclick="OperationsPage.delete(${operation.id})" title="Удалить">🗑</button>
					</div>
				</td>
			`;
			tbody.appendChild(row);
		});
	},

	async showAddForm() {
		const units = await API.getUnits();
		this.showForm(null, units);
	},

	async edit(id) {
		const operation = await API.getOperation(id);
		const units = await API.getUnits();
		this.showForm(operation, units);
	},

	showForm(operation = null, units = []) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>${operation ? 'Редактировать операцию' : 'Добавить операцию'}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="operationForm">
						<input type="hidden" id="operationId" value="${operation ? operation.id : ''}">
						<div class="form-group">
							<label for="operationNumber">Номер операции *</label>
							<input type="text" id="operationNumber" value="${operation ? operation.number : ''}" required>
						</div>
						<div class="form-group">
							<label for="operationDescription">Описание *</label>
							<input type="text" id="operationDescription" value="${operation ? operation.description : ''}" required>
						</div>
						<div class="form-group">
							<label for="operationUnit">Единица измерения</label>
							<select id="operationUnit">
								<option value="">Не выбрано</option>
								${units.map(u => `<option value="${u.id}" ${operation && operation.unit_id == u.id ? 'selected' : ''}>${u.name}</option>`).join('')}
							</select>
						</div>
						<div class="form-group">
							<label for="operationCost">Стоимость (руб/ед) *</label>
							<input type="number" id="operationCost" step="0.01" value="${operation ? operation.cost : ''}" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="OperationsPage.save()">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	async save() {
		const id = document.getElementById('operationId').value;
		const number = document.getElementById('operationNumber').value;
		const description = document.getElementById('operationDescription').value;
		const unitId = document.getElementById('operationUnit').value || null;
		const cost = parseFloat(document.getElementById('operationCost').value);

		try {
			if (id) {
				await API.updateOperation({ id: parseInt(id), number, description, unit_id: unitId ? parseInt(unitId) : null, cost });
			} else {
				await API.createOperation({ number, description, unit_id: unitId ? parseInt(unitId) : null, cost });
			}
			document.querySelector('.modal').remove();
			await this.loadOperations();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async delete(id) {
		if (!confirm('Удалить операцию?')) return;

		try {
			await API.deleteOperation(id);
			await this.loadOperations();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};

// Добавляем методы в API
API.getOperation = async function(id) {
	return this.request(`/operations.php?id=${id}`);
};

API.createOperation = async function(data) {
	return this.request('/operations.php', {
		method: 'POST',
		body: data,
	});
};

API.updateOperation = async function(data) {
	return this.request('/operations.php', {
		method: 'PUT',
		body: data,
	});
};

API.deleteOperation = async function(id) {
	return this.request(`/operations.php?id=${id}`, {
		method: 'DELETE',
	});
};
