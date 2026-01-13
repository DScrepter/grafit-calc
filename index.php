<?php
require_once __DIR__ . '/backend/classes/Assets.php';

// Инициализация Assets
Assets::init('');
Assets::enqueue_home_assets();

// Переменные для шаблонов
$page_title = 'Калькулятор себестоимости изделий графитового производства';

// Подключаем header
require __DIR__ . '/templates/header.php';
?>
	<style>
		.home-page {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			padding: 20px;
		}
		.home-container {
			background: white;
			border-radius: 10px;
			padding: 40px;
			max-width: 800px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
		}
		.home-container h1 {
			color: #333;
			margin-bottom: 20px;
			font-size: 2.5em;
		}
		.home-container p {
			color: #666;
			line-height: 1.6;
			margin-bottom: 15px;
			font-size: 1.1em;
		}
		.home-container .features {
			margin: 30px 0;
		}
		.home-container .features h2 {
			color: #333;
			margin-bottom: 15px;
			font-size: 1.5em;
		}
		.home-container .features ul {
			list-style: none;
			padding: 0;
		}
		.home-container .features li {
			padding: 10px 0;
			padding-left: 30px;
			position: relative;
			color: #555;
		}
		.home-container .features li:before {
			content: "✓";
			position: absolute;
			left: 0;
			color: #667eea;
			font-weight: bold;
			font-size: 1.2em;
		}
		.home-container .btn-container {
			text-align: center;
			margin-top: 40px;
		}
		.home-container .btn-primary {
			display: inline-block;
			padding: 15px 40px;
			font-size: 1.2em;
			text-decoration: none;
		}
	</style>
	<div class="home-page">
		<div class="home-container">
			<h1>Калькулятор себестоимости</h1>
			<p>Веб-приложение для расчета себестоимости изделий графитового производства.</p>
			
			<div class="features">
				<h2>Возможности системы:</h2>
				<ul>
					<li>Расчет себестоимости изделий с учетом материалов и операций</li>
					<li>Управление справочниками: материалы, операции, единицы измерения</li>
					<li>Работа с типами изделий и коэффициентами</li>
					<li>Удобный интерфейс для ввода данных и просмотра результатов</li>
				</ul>
			</div>

			<div class="btn-container">
				<a href="/login" class="btn btn-primary">Войти в систему</a>
			</div>
		</div>
	</div>
	<?php require __DIR__ . '/templates/footer.php'; ?>
