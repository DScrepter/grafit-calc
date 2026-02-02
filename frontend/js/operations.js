/**
 * Страница управления операциями и вспомогательные функции
 */

const OperationsPage = {
	operations: [],
	currentSort: { column: null, direction: 'asc' },

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник операций</h1>
				<button class="btn btn-primary mb-20" onclick="OperationsPage.showAddForm()">Добавить операцию</button>
				<table class="data-table" id="operationsTable">
					<thead>
						<tr>
							<th class="sortable" data-column="number">Номер</th>
							<th class="sortable" data-column="description">Описание</th>
							<th class="sortable" data-column="unit_name">Единица измерения</th>
							<th class="sortable" data-column="material_marks">Марки материалов</th>
							<th class="sortable" data-column="cost">Стоимость (руб/ед)</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadOperations();
		this.setupSorting();
	},

	setupSorting() {
		const headers = document.querySelectorAll('#operationsTable th.sortable');
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

		this.operations.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			// Для числовых столбцов
			if (column === 'number') {
				aVal = parseInt(aVal) || 0;
				bVal = parseInt(bVal) || 0;
			} else if (column === 'cost') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else if (column === 'material_marks') {
				aVal = (a.material_marks || '').toString().toLowerCase();
				bVal = (b.material_marks || '').toString().toLowerCase();
			} else {
				// Для текстовых столбцов
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return this.currentSort.direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return this.currentSort.direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderOperations();
		this.updateSortIndicators();
	},

	updateSortIndicators() {
		const headers = document.querySelectorAll('#operationsTable th.sortable');
		headers.forEach(header => {
			const column = header.dataset.column;
			header.classList.remove('sorted-asc', 'sorted-desc');

			if (this.currentSort.column === column) {
				header.classList.add(this.currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
			}
		});
	},

	async loadOperations() {
		this.operations = await API.getOperations();
		// Если сортировка не установлена, сортируем по номеру численно при первой загрузке
		if (!this.currentSort.column) {
			this.currentSort = { column: 'number', direction: 'asc' };
		}
		// Применяем текущую сортировку (без переключения направления)
		const column = this.currentSort.column;
		const direction = this.currentSort.direction;

		this.operations.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			// Для числовых столбцов
			if (column === 'number') {
				aVal = parseInt(aVal) || 0;
				bVal = parseInt(bVal) || 0;
			} else if (column === 'cost') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else if (column === 'material_marks') {
				aVal = (a.material_marks || '').toString().toLowerCase();
				bVal = (b.material_marks || '').toString().toLowerCase();
			} else {
				// Для текстовых столбцов
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderOperations();
		this.updateSortIndicators();
	},

	renderOperations() {
		const tbody = document.querySelector('#operationsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #operationsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		this.operations.forEach(operation => {
			const materialMarks = operation.material_marks || '-';
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${operation.number}</td>
				<td>${operation.description}</td>
				<td>${operation.unit_name || '-'}</td>
				<td>${materialMarks}</td>
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
		const [operation, units] = await Promise.all([
			API.getOperation(id),
			API.getUnits()
		]);
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
							<label for="operationMaterialMarks">Марки материалов</label>
							<input type="text" id="operationMaterialMarks" value="${operation ? (operation.material_marks || '') : ''}">
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
		const materialMarks = (document.getElementById('operationMaterialMarks')?.value || '').trim();

		try {
			const payload = { number, description, unit_id: unitId ? parseInt(unitId) : null, cost, material_marks: materialMarks };
			if (id) {
				payload.id = parseInt(id);
				await API.updateOperation(payload);
			} else {
				await API.createOperation(payload);
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
API.getOperation = async function (id) {
	return this.request(`/operations.php?id=${id}`);
};

API.createOperation = async function (data) {
	return this.request('/operations.php', {
		method: 'POST',
		body: data,
	});
};

API.updateOperation = async function (data) {
	return this.request('/operations.php', {
		method: 'PUT',
		body: data,
	});
};

API.deleteOperation = async function (id) {
	return this.request(`/operations.php?id=${id}`, {
		method: 'DELETE',
	});
};
