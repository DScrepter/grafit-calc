/**
 * Страница управления типами изделий
 */

const ProductTypesPage = {
	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник типов изделий</h1>
				<button class="btn btn-primary mb-20" onclick="ProductTypesPage.showAddForm()">Добавить тип изделия</button>
				<table class="data-table" id="productTypesTable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Название</th>
							<th>Описание</th>
							<th>Параметры</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadProductTypes();
	},

	async loadProductTypes() {
		const types = await API.getProductTypes();
		const tbody = document.querySelector('#productTypesTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #productTypesTable tbody');
			return;
		}
		tbody.innerHTML = '';

		types.forEach(type => {
			const row = document.createElement('tr');
			const params = type.parameters.map(p => `${p.label} (${p.unit})`).join(', ');
			row.innerHTML = `
				<td>${type.id}</td>
				<td>${type.name}</td>
				<td>${type.description || '-'}</td>
				<td>${params || '-'}</td>
				<td>
					<button class="btn btn-small btn-secondary" onclick="ProductTypesPage.edit(${type.id})">Редактировать</button>
					<button class="btn btn-small btn-danger" onclick="ProductTypesPage.delete(${type.id})">Удалить</button>
				</td>
			`;
			tbody.appendChild(row);
		});
	},

	showAddForm() {
		this.showForm();
	},

	async edit(id) {
		const type = await API.getProductType(id);
		this.showForm(type);
	},

	showForm(type = null) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content" style="max-width: 800px;">
				<div class="modal-header">
					<h3>${type ? 'Редактировать тип изделия' : 'Добавить тип изделия'}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="productTypeForm">
						<input type="hidden" id="productTypeId" value="${type ? type.id : ''}">
						<div class="form-group">
							<label for="productTypeName">Название *</label>
							<input type="text" id="productTypeName" value="${type ? type.name : ''}" required>
						</div>
						<div class="form-group">
							<label for="productTypeDescription">Описание</label>
							<textarea id="productTypeDescription" rows="3">${type ? (type.description || '') : ''}</textarea>
						</div>
						<div class="form-group">
							<label for="productTypeVolumeFormula">Формула объема *</label>
							<input type="text" id="productTypeVolumeFormula" value="${type ? type.volume_formula : ''}" placeholder="например: length * width * height" required>
							<small>Используйте имена параметров из списка ниже</small>
						</div>
						<div class="form-group">
							<label for="productTypeWasteFormula">Формула отходов *</label>
							<input type="text" id="productTypeWasteFormula" value="${type ? type.waste_formula : ''}" placeholder="например: volume * 0.1" required>
							<small>Используйте имена параметров из списка ниже</small>
						</div>
						<div class="form-group">
							<label>Параметры</label>
							<div id="parametersList"></div>
							<button type="button" class="btn btn-small btn-secondary" onclick="ProductTypesPage.addParameter()">Добавить параметр</button>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="ProductTypesPage.save()">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);

		// Загружаем параметры если редактируем
		if (type && type.parameters) {
			type.parameters.forEach((param, index) => {
				this.addParameter(param, index);
			});
		}
	},

	addParameter(param = null, index = null) {
		const list = document.getElementById('parametersList');
		if (!list) return;

		const paramIndex = index !== null ? index : list.children.length;
		const paramDiv = document.createElement('div');
		paramDiv.className = 'parameter-item';
		paramDiv.style.marginBottom = '10px';
		paramDiv.style.padding = '10px';
		paramDiv.style.border = '1px solid #ddd';
		paramDiv.style.borderRadius = '4px';
		paramDiv.innerHTML = `
			<div style="display: grid; grid-template-columns: 1fr 1fr 1fr 100px 80px 60px; gap: 10px; align-items: end;">
				<div class="form-group" style="margin: 0;">
					<label style="font-size: 12px;">Имя (name) *</label>
					<input type="text" class="param-name" value="${param ? param.name : ''}" placeholder="length" required>
				</div>
				<div class="form-group" style="margin: 0;">
					<label style="font-size: 12px;">Метка (label) *</label>
					<input type="text" class="param-label" value="${param ? param.label : ''}" placeholder="Длина" required>
				</div>
				<div class="form-group" style="margin: 0;">
					<label style="font-size: 12px;">Единица *</label>
					<input type="text" class="param-unit" value="${param ? param.unit : ''}" placeholder="мм" required>
				</div>
				<div class="form-group" style="margin: 0;">
					<label style="font-size: 12px;">По умолчанию</label>
					<input type="number" class="param-default" value="${param ? (param.default_value || '') : ''}" step="0.01">
				</div>
				<div class="form-group" style="margin: 0;">
					<label style="font-size: 12px;">Обязательный</label>
					<input type="checkbox" class="param-required" ${param && param.required ? 'checked' : ''}>
				</div>
				<div>
					<button type="button" class="btn btn-small btn-danger" onclick="this.closest('.parameter-item').remove()">×</button>
				</div>
			</div>
		`;
		list.appendChild(paramDiv);
	},

	async save() {
		const id = document.getElementById('productTypeId').value;
		const name = document.getElementById('productTypeName').value;
		const description = document.getElementById('productTypeDescription').value;
		const volumeFormula = document.getElementById('productTypeVolumeFormula').value;
		const wasteFormula = document.getElementById('productTypeWasteFormula').value;

		// Собираем параметры
		const parameters = [];
		const paramItems = document.querySelectorAll('.parameter-item');
		paramItems.forEach((item, index) => {
			const name = item.querySelector('.param-name').value;
			const label = item.querySelector('.param-label').value;
			const unit = item.querySelector('.param-unit').value;
			const defaultValue = item.querySelector('.param-default').value;
			const required = item.querySelector('.param-required').checked;

			if (name && label && unit) {
				parameters.push({
					name: name.trim(),
					label: label.trim(),
					unit: unit.trim(),
					required: required,
					default_value: defaultValue ? parseFloat(defaultValue) : null,
					sequence: index
				});
			}
		});

		try {
			const data = {
				name,
				description,
				volume_formula: volumeFormula,
				waste_formula: wasteFormula,
				parameters
			};

			if (id) {
				data.id = parseInt(id);
				await API.updateProductType(data);
			} else {
				await API.createProductType(data);
			}
			document.querySelector('.modal').remove();
			await this.loadProductTypes();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async delete(id) {
		if (!confirm('Удалить тип изделия?')) return;

		try {
			await API.deleteProductType(id);
			await this.loadProductTypes();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};
