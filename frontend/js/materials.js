/**
 * Страница управления материалами
 */

const MaterialsPage = {
	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник материалов</h1>
				<button class="btn btn-primary mb-20" onclick="MaterialsPage.showAddForm()">Добавить материал</button>
				<table class="data-table" id="materialsTable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Марка</th>
							<th>Плотность (г/см³)</th>
							<th>Цена (руб/кг)</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadMaterials();
	},

	async loadMaterials() {
		const materials = await API.getMaterials();
		const tbody = document.querySelector('#materialsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #materialsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		materials.forEach(material => {
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${material.id}</td>
				<td>${material.mark}</td>
				<td>${parseFloat(material.density).toFixed(4)}</td>
				<td>${parseFloat(material.price).toFixed(2)}</td>
				<td>
					<button class="btn btn-small btn-secondary" onclick="MaterialsPage.edit(${material.id})">Редактировать</button>
					<button class="btn btn-small btn-danger" onclick="MaterialsPage.delete(${material.id})">Удалить</button>
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
