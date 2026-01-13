/**
 * Страница управления материалами
 */

const MaterialsPage = {
	materials: [],
	currentSort: { column: null, direction: 'asc' },

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник материалов</h1>
				<button class="btn btn-primary mb-20" onclick="MaterialsPage.showAddForm()">Добавить материал</button>
				<table class="data-table" id="materialsTable">
					<thead>
						<tr>
							<th class="sortable" data-column="id">ID</th>
							<th class="sortable" data-column="mark">Марка</th>
							<th class="sortable" data-column="density">Плотность (г/см³)</th>
							<th class="sortable" data-column="price">Цена (руб/кг)</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadMaterials();
		this.setupSorting();
	},

	setupSorting() {
		const headers = document.querySelectorAll('#materialsTable th.sortable');
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

		this.materials.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			if (column === 'id' || column === 'density' || column === 'price') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else {
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return this.currentSort.direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return this.currentSort.direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderMaterials();
		this.updateSortIndicators();
	},

	updateSortIndicators() {
		const headers = document.querySelectorAll('#materialsTable th.sortable');
		headers.forEach(header => {
			const column = header.dataset.column;
			header.classList.remove('sorted-asc', 'sorted-desc');
			
			if (this.currentSort.column === column) {
				header.classList.add(this.currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc');
			}
		});
	},

	async loadMaterials() {
		this.materials = await API.getMaterials();
		if (!this.currentSort.column) {
			this.currentSort = { column: 'id', direction: 'asc' };
		}
		
		const column = this.currentSort.column;
		const direction = this.currentSort.direction;
		
		this.materials.sort((a, b) => {
			let aVal = a[column];
			let bVal = b[column];

			if (column === 'id' || column === 'density' || column === 'price') {
				aVal = parseFloat(aVal) || 0;
				bVal = parseFloat(bVal) || 0;
			} else {
				aVal = (aVal || '').toString().toLowerCase();
				bVal = (bVal || '').toString().toLowerCase();
			}

			if (aVal < bVal) return direction === 'asc' ? -1 : 1;
			if (aVal > bVal) return direction === 'asc' ? 1 : -1;
			return 0;
		});

		this.renderMaterials();
		this.updateSortIndicators();
	},

	renderMaterials() {
		const tbody = document.querySelector('#materialsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #materialsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		this.materials.forEach(material => {
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${material.id}</td>
				<td>${material.mark}</td>
				<td>${parseFloat(material.density).toFixed(4)}</td>
				<td>${parseFloat(material.price).toFixed(2)}</td>
				<td>
					<div class="action-buttons">
						<button class="btn btn-small btn-primary" onclick="MaterialsPage.edit(${material.id})" title="Редактировать">✏️</button>
						<button class="btn btn-small btn-danger" onclick="MaterialsPage.delete(${material.id})" title="Удалить">🗑</button>
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
		const material = await API.getMaterial(id);
		this.showForm(material);
	},

	showForm(material = null) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>${material ? 'Редактировать материал' : 'Добавить материал'}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="materialForm">
						<input type="hidden" id="materialId" value="${material ? material.id : ''}">
						<div class="form-group">
							<label for="materialMark">Марка материала *</label>
							<input type="text" id="materialMark" value="${material ? material.mark : ''}" required>
						</div>
						<div class="form-group">
							<label for="materialDensity">Плотность (г/см³) *</label>
							<input type="number" id="materialDensity" step="0.0001" value="${material ? material.density : ''}" required>
						</div>
						<div class="form-group">
							<label for="materialPrice">Цена (руб/кг) *</label>
							<input type="number" id="materialPrice" step="0.01" value="${material ? material.price : ''}" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="MaterialsPage.save()">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	async save() {
		const id = document.getElementById('materialId').value;
		const mark = document.getElementById('materialMark').value;
		const density = parseFloat(document.getElementById('materialDensity').value);
		const price = parseFloat(document.getElementById('materialPrice').value);

		try {
			if (id) {
				await API.updateMaterial({ id: parseInt(id), mark, density, price });
			} else {
				await API.createMaterial({ mark, density, price });
			}
			document.querySelector('.modal').remove();
			await this.loadMaterials();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async delete(id) {
		if (!confirm('Удалить материал?')) return;

		try {
			await API.deleteMaterial(id);
			await this.loadMaterials();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};
