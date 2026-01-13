/**
 * Страница управления единицами измерения
 */

const UnitsPage = {
	units: [],
	currentSort: { column: null, direction: 'asc' },

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник единиц измерения</h1>
				<button class="btn btn-primary mb-20" onclick="UnitsPage.showAddForm()">Добавить единицу</button>
				<table class="data-table" id="unitsTable">
					<thead>
						<tr>
							<th class="sortable" data-column="id">ID</th>
							<th class="sortable" data-column="name">Название</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadUnits();
		this.setupSorting();
	},

	setupSorting() {
		const headers = document.querySelectorAll('#unitsTable th.sortable');
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

		this.units.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			if (column === 'id') {
				aVal = parseInt(aVal) || 0;
				bVal = parseInt(bVal) || 0;
			} else {
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return this.currentSort.direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return this.currentSort.direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderUnits();
		this.updateSortIndicators();
	},

	updateSortIndicators() {
		const headers = document.querySelectorAll('#unitsTable th.sortable');
		headers.forEach(header => {
			const column = header.dataset.column;
			header.classList.remove('sorted-asc', 'sorted-desc');
			
			if (this.currentSort.column === column) {
				header.classList.add(this.currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
			}
		});
	},

	async loadUnits() {
		this.units = await API.getUnits();
		if (!this.currentSort.column) {
			this.currentSort = { column: 'id', direction: 'asc' };
		}
		
		const column = this.currentSort.column;
		const direction = this.currentSort.direction;
		
		this.units.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			if (column === 'id') {
				aVal = parseInt(aVal) || 0;
				bVal = parseInt(bVal) || 0;
			} else {
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderUnits();
		this.updateSortIndicators();
	},

	renderUnits() {
		const tbody = document.querySelector('#unitsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #unitsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		this.units.forEach(unit => {
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${unit.id}</td>
				<td>${unit.name}</td>
				<td>
					<div class="action-buttons">
						<button class="btn btn-small btn-primary" onclick="UnitsPage.edit(${unit.id})" title="Редактировать">✏️</button>
						<button class="btn btn-small btn-danger" onclick="UnitsPage.delete(${unit.id})" title="Удалить">🗑</button>
					</div>
				</td>
			`;
			tbody.appendChild(row);
		});
	},

	showAddForm() {
		this.showForm();
	},

	async edit(id) {
		const unit = await API.getUnit(id);
		this.showForm(unit);
	},

	showForm(unit = null) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>${unit ? 'Редактировать единицу' : 'Добавить единицу'}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="unitForm">
						<input type="hidden" id="unitId" value="${unit ? unit.id : ''}">
						<div class="form-group">
							<label for="unitName">Название *</label>
							<input type="text" id="unitName" value="${unit ? unit.name : ''}" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="UnitsPage.save()">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	async save() {
		const id = document.getElementById('unitId').value;
		const name = document.getElementById('unitName').value;

		try {
			if (id) {
				await API.updateUnit({ id: parseInt(id), name });
			} else {
				await API.createUnit({ name });
			}
			document.querySelector('.modal').remove();
			await this.loadUnits();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async delete(id) {
		if (!confirm('Удалить единицу измерения?')) return;

		try {
			await API.deleteUnit(id);
			await this.loadUnits();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};

// Добавляем методы в API
API.getUnit = async function(id) {
	return this.request(`/units.php?id=${id}`);
};

API.createUnit = async function(data) {
	return this.request('/units.php', {
		method: 'POST',
		body: data,
	});
};

API.updateUnit = async function(data) {
	return this.request('/units.php', {
		method: 'PUT',
		body: data,
	});
};

API.deleteUnit = async function(id) {
	return this.request(`/units.php?id=${id}`, {
		method: 'DELETE',
	});
};
