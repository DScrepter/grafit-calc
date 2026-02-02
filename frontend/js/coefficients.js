/**
 * Страница управления коэффициентами (фиксированный набор: N, Kz_порог, Kz, K, M)
 */

const FIXED_COEFFICIENT_NAMES = ['N', 'Kz_порог', 'Kz', 'K', 'M'];
const DISPLAY_ORDER = ['N', 'Kz_порог', 'Kz', 'K', 'M'];

const CoefficientsPage = {
	coefficients: [],

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Справочник коэффициентов</h1>
				<table class="data-table" id="coefficientsTable">
					<thead>
						<tr>
							<th>Название</th>
							<th>Значение</th>
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
		const all = await API.getCoefficients();
		this.coefficients = all.filter(c => FIXED_COEFFICIENT_NAMES.includes(c.name));
		this.coefficients.sort((a, b) => DISPLAY_ORDER.indexOf(a.name) - DISPLAY_ORDER.indexOf(b.name));
		this.renderCoefficients();
	},

	getCoefficientByName(name) {
		return this.coefficients.find(c => c.name === name) || null;
	},

	renderCoefficients() {
		const tbody = document.querySelector('#coefficientsTable tbody');
		if (!tbody) return;
		tbody.innerHTML = '';

		// N
		const coefN = this.getCoefficientByName('N');
		if (coefN) {
			tbody.appendChild(this.buildRow('Налоги на зарплату (N)', parseFloat(coefN.value).toFixed(2) + ' %', coefN.description, () => this.showFormSingle(coefN, 'Значение (%)')));
		}

		// Kz_порог + Kz — одна строка
		const coefKzPorog = this.getCoefficientByName('Kz_порог');
		const coefKz = this.getCoefficientByName('Kz');
		if (coefKzPorog && coefKz) {
			const valueText = 'Порог: ' + parseFloat(coefKzPorog.value) + ' шт, Kz: ' + parseFloat(coefKz.value).toFixed(2);
			tbody.appendChild(this.buildRow('Повышающий коэффициент (порог / Kz)', valueText, coefKz.description || coefKzPorog.description, () => this.showFormKz(coefKzPorog, coefKz)));
		}

		// K
		const coefK = this.getCoefficientByName('K');
		if (coefK) {
			tbody.appendChild(this.buildRow('Коэффициент для расчёта ОХР (K)', parseFloat(coefK.value).toFixed(2), coefK.description, () => this.showFormSingle(coefK, 'Значение')));
		}

		// M
		const coefM = this.getCoefficientByName('M');
		if (coefM) {
			tbody.appendChild(this.buildRow('Маржинальность (M)', parseFloat(coefM.value).toFixed(2) + ' %', coefM.description, () => this.showFormSingle(coefM, 'Значение (%)')));
		}
	},

	buildRow(label, valueText, description, onEdit) {
		const row = document.createElement('tr');
		row.innerHTML = `
			<td>${label}</td>
			<td>${valueText}</td>
			<td>${description || '-'}</td>
			<td>
				<div class="action-buttons">
					<button class="btn btn-small btn-primary" title="Редактировать">✏️</button>
				</div>
			</td>
		`;
		row.querySelector('button').addEventListener('click', onEdit);
		return row;
	},

	showFormSingle(coefficient, valueLabel) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>Редактировать: ${coefficient.name}</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="coefficientForm">
						<input type="hidden" id="coefficientId" value="${coefficient.id}">
						<div class="form-group">
							<label for="coefficientValue">${valueLabel} *</label>
							<input type="number" id="coefficientValue" step="0.01" value="${parseFloat(coefficient.value)}" required>
						</div>
						<div class="form-group">
							<label for="coefficientDescription">Описание</label>
							<input type="text" id="coefficientDescription" value="${coefficient.description || ''}">
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="CoefficientsPage.saveSingle(this.closest('.modal'))">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	showFormKz(coefKzPorog, coefKz) {
		const form = document.createElement('div');
		form.className = 'modal';
		form.innerHTML = `
			<div class="modal-content">
				<div class="modal-header">
					<h3>Редактировать: Повышающий коэффициент (порог / Kz)</h3>
					<button class="modal-close" onclick="this.closest('.modal').remove()">&times;</button>
				</div>
				<div class="modal-body">
					<form id="coefficientFormKz">
						<input type="hidden" id="kzPorogId" value="${coefKzPorog.id}">
						<input type="hidden" id="kzId" value="${coefKz.id}">
						<div class="form-group">
							<label for="kzPorogValue">Порог малого количества (шт) *</label>
							<input type="number" id="kzPorogValue" step="1" min="1" value="${parseFloat(coefKzPorog.value)}" required>
						</div>
						<div class="form-group">
							<label for="kzValue">Коэффициент Kz *</label>
							<input type="number" id="kzValue" step="0.01" min="0.01" value="${parseFloat(coefKz.value)}" required>
						</div>
						<div class="form-group">
							<label for="coefficientDescriptionKz">Описание</label>
							<input type="text" id="coefficientDescriptionKz" value="${coefKz.description || ''}">
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button class="btn btn-primary" onclick="CoefficientsPage.saveKz(this.closest('.modal'))">Сохранить</button>
					<button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Отмена</button>
				</div>
			</div>
		`;
		document.body.appendChild(form);
	},

	async saveSingle(modal) {
		const id = parseInt(document.getElementById('coefficientId').value);
		const value = parseFloat(document.getElementById('coefficientValue').value);
		const description = document.getElementById('coefficientDescription').value || null;
		const coef = this.coefficients.find(c => c.id === id);
		const name = coef ? coef.name : '';

		try {
			await API.updateCoefficient({ id, name, value, description });
			modal.remove();
			await this.loadCoefficients();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	},

	async saveKz(modal) {
		const idPorog = parseInt(document.getElementById('kzPorogId').value);
		const idKz = parseInt(document.getElementById('kzId').value);
		const valuePorog = parseFloat(document.getElementById('kzPorogValue').value);
		const valueKz = parseFloat(document.getElementById('kzValue').value);
		const description = document.getElementById('coefficientDescriptionKz').value || null;

		try {
			await API.updateCoefficient({ id: idPorog, name: 'Kz_порог', value: valuePorog, description });
			await API.updateCoefficient({ id: idKz, name: 'Kz', value: valueKz, description });
			modal.remove();
			await this.loadCoefficients();
		} catch (error) {
			alert('Ошибка: ' + error.message);
		}
	}
};

// Добавляем методы в API
API.getCoefficient = async function (id) {
	return this.request(`/coefficients.php?id=${id}`);
};

API.createCoefficient = async function (data) {
	return this.request('/coefficients.php', {
		method: 'POST',
		body: data,
	});
};

API.updateCoefficient = async function (data) {
	return this.request('/coefficients.php', {
		method: 'PUT',
		body: data,
	});
};

API.deleteCoefficient = async function (id) {
	return this.request(`/coefficients.php?id=${id}`, {
		method: 'DELETE',
	});
};
