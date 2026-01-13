/**
 * Страница управления коэффициентами
 */

const CoefficientsPage = {
	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник коэффициентов</h1>
				<button class="btn btn-primary mb-20" onclick="CoefficientsPage.showAddForm()">Добавить коэффициент</button>
				<table class="data-table" id="coefficientsTable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Название</th>
							<th>Значение (%)</th>
							<th>Описание</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		`;

		await this.loadCoefficients();
	},

	async loadCoefficients() {
		const coefficients = await API.getCoefficients();
		const tbody = document.querySelector('#coefficientsTable tbody');
		if (!tbody) {
			console.error('Не найден элемент #coefficientsTable tbody');
			return;
		}
		tbody.innerHTML = '';

		coefficients.forEach(coef => {
			const row = document.createElement('tr');
			row.innerHTML = `
				<td>${coef.id}</td>
				<td>${coef.name}</td>
				<td>${parseFloat(coef.value).toFixed(2)}</td>
				<td>${coef.description || '-'}</td>
				<td>
					<div class="action-buttons">
						<button class="btn btn-small btn-primary" onclick="CoefficientsPage.edit(${coef.id})" title="Редактировать">✏️</button>
						<button class="btn btn-small btn-danger" onclick="CoefficientsPage.delete(${coef.id})" title="Удалить">🗑</button>
					</div>
				</td>
			`;
			tbody.appendChild(row);
		});
	},

	async showAddForm() {
		this.showForm();
	},

	async edit(id) {
		const coefficient = await API.getCoefficient(id);
		this.showForm(coefficient);
	},

	showForm(coefficient = null) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>${coefficient ? 'Редактировать коэффициент' : 'Добавить коэффициент'}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="coefficientForm">
						<input type="hidden" id="coefficientId" value="${coefficient ? coefficient.id : ''}">
						<div class="form-group">
							<label for="coefficientName">Название *</label>
							<input type="text" id="coefficientName" value="${coefficient ? coefficient.name : ''}" required>
						</div>
						<div class="form-group">
							<label for="coefficientValue">Значение (%) *</label>
							<input type="number" id="coefficientValue" step="0.01" value="${coefficient ? coefficient.value : ''}" required>
						</div>
						<div class="form-group">
							<label for="coefficientDescription">Описание</label>
							<input type="text" id="coefficientDescription" value="${coefficient ? (coefficient.description || '') : ''}">
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="CoefficientsPage.save()">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	async save() {
		const id = document.getElementById('coefficientId').value;
		const name = document.getElementById('coefficientName').value;
		const value = parseFloat(document.getElementById('coefficientValue').value);
		const description = document.getElementById('coefficientDescription').value || null;

		try {
			if (id) {
				await API.updateCoefficient({ id: parseInt(id), name, value, description });
			} else {
				await API.createCoefficient({ name, value, description });
			}
			document.querySelector('.modal').remove();
			await this.loadCoefficients();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async delete(id) {
		if (!confirm('Удалить коэффициент?')) return;

		try {
			await API.deleteCoefficient(id);
			await this.loadCoefficients();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};

// Добавляем методы в API
API.getCoefficient = async function(id) {
	return this.request(`/coefficients.php?id=${id}`);
};

API.createCoefficient = async function(data) {
	return this.request('/coefficients.php', {
		method: 'POST',
		body: data,
	});
};

API.updateCoefficient = async function(data) {
	return this.request('/coefficients.php', {
		method: 'PUT',
		body: data,
	});
};

API.deleteCoefficient = async function(id) {
	return this.request(`/coefficients.php?id=${id}`, {
		method: 'DELETE',
	});
};
