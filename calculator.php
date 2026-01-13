<?php
// Проверка авторизации
require_once __DIR__ . '/backend/classes/Auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
	header('Location: /login');
	exit;
}

// Гости не имеют доступа к калькулятору - перенаправляем на профиль
if (!$auth->canAccessReferences()) {
	header('Location: /profile');
	exit;
}

$username = $auth->getUsername();
$userRole = $auth->getUserRole();
$userData = $auth->getUserData();

// Переменные для шаблонов
$page_title = 'Калькулятор себестоимости изделий графитового производства';
$sidebar_type = 'calculator';

// Подключаем header
require __DIR__ . '/templates/header.php';
?>
	<div class="app-container">
		<?php require __DIR__ . '/templates/sidebar.php'; ?>

		<!-- Рабочая область -->
		<main class="workspace">
			<div id="workspaceContent">
				<!-- Контент будет загружаться динамически -->
			</div>
		</main>
	</div>

	<!-- Модальное окно выбора операции -->
	<div id="operationDialog" class="modal" style="display: none;">
		<div class="modal-content">
			<div class="modal-header">
				<div class="modal-header-left">
					<h3>Выбор операции</h3>
					<div class="complexity-coefficient-sticky">
						<label for="complexityCoefficient">Коэффициент сложности:</label>
						<input type="number" id="complexityCoefficient" min="0.1" max="10" step="0.01" value="1.0">
					</div>
				</div>
				<button class="modal-close" onclick="closeOperationDialog()">&times;</button>
			</div>
			<div class="modal-body">
				<div class="search-box">
					<input type="text" id="operationSearch" placeholder="Поиск по номеру или описанию...">
				</div>
				<table class="data-table" id="operationsTable">
					<thead>
						<tr>
							<th data-sort="number">Номер</th>
							<th data-sort="description">Описание</th>
							<th data-sort="unit">Единица</th>
							<th data-sort="cost">Стоимость (руб/ед)</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div class="modal-footer">
				<button class="btn btn-primary" onclick="confirmOperationSelection()">ОК</button>
				<button class="btn btn-secondary" onclick="closeOperationDialog()">Отмена</button>
			</div>
		</div>
	</div>

	<?php require __DIR__ . '/templates/footer.php'; ?>
