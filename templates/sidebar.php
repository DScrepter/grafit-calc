<?php
/**
 * Шаблон sidebar
 * 
 * Ожидаемые переменные:
 * $auth - объект Auth
 * $username - имя пользователя (или $userData для profile)
 * $sidebar_type - тип sidebar: 'calculator', 'reference', 'profile'
 * $active_page - активная страница (для reference)
 */
?>
	<!-- Навигация -->
	<nav class="sidebar">
		<div class="sidebar-header">
			<h2>Навигация</h2>
		</div>
		<ul class="nav-tree" id="navTree">
			<?php if ($auth->canAccessReferences()): ?>
				<li class="nav-group" id="referencesGroup">
					<span class="nav-group-title">Справочники</span>
					<ul class="nav-items">
						<li data-page="materials"><a href="/calculator?page=materials">Материалы</a></li>
						<li data-page="units"><a href="/calculator?page=units">Единицы измерения</a></li>
						<li data-page="operations"><a href="/calculator?page=operations">Операции</a></li>
						<li data-page="product_types"><a href="/calculator?page=product_types">Типы изделий</a></li>
						<li data-page="coefficients"><a href="/calculator?page=coefficients">Коэффициенты</a></li>
					</ul>
				</li>
				<li class="nav-group" id="calculatorsGroup">
					<span class="nav-group-title">Калькуляторы</span>
					<ul class="nav-items">
						<li data-page="calculator" <?php echo ($sidebar_type === 'calculator') ? 'class="active"' : ''; ?>><a href="/calculator">Основной калькулятор</a></li>
						<li data-page="products"><a href="/calculator?page=products">Изделия</a></li>
					</ul>
				</li>
			<?php endif; ?>
			<?php if ($auth->canManageUsers()): ?>
				<li class="nav-group" id="adminGroup">
					<span class="nav-group-title">Администрирование</span>
					<ul class="nav-items">
						<li data-page="users"><a href="/calculator?page=users" id="usersNavLink">Пользователи<span class="nav-badge" id="usersNavBadge" style="display: none;">0</span></a></li>
						<?php if ($auth->isSuperAdmin()): ?>
							<li data-page="migrations"><a href="/calculator?page=migrations">Обновления БД</a></li>
						<?php endif; ?>
					</ul>
				</li>
			<?php endif; ?>
			<li class="nav-group">
				<span class="nav-group-title">Профиль</span>
				<ul class="nav-items">
					<li <?php echo ($sidebar_type === 'profile') ? 'class="active"' : ''; ?>><a href="/profile">Мой профиль</a></li>
				</ul>
			</li>
		</ul>
			<div class="sidebar-footer">
				<div class="user-info">
					<?php 
					$displayName = '';
					// Пытаемся получить userData для формирования полного имени
					if (isset($userData)) {
						// Формируем имя: если есть имя и фамилия - используем их, иначе username
						$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
						$displayName = htmlspecialchars($fullName ?: $userData['username']);
					} elseif (isset($username)) {
						$displayName = htmlspecialchars($username);
					} else {
						// Если нет ни userData, ни username, получаем из Auth
						$userData = $auth->getUserData();
						if ($userData) {
							$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
							$displayName = htmlspecialchars($fullName ?: $userData['username']);
						} else {
							$displayName = htmlspecialchars($auth->getUsername() ?? 'Пользователь');
						}
					}
					?>
					<span id="username" class="username-link" style="cursor: pointer; text-decoration: underline;"><?php echo $displayName; ?></span>
					<button id="logoutBtn" class="btn btn-small">Выход</button>
				</div>
			</div>
		</nav>
