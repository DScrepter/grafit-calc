<?php
/**
 * Класс для управления стилями и скриптами (аналог WordPress enqueue)
 */
class Assets {
	private static $styles = [];
	private static $scripts = [];
	private static $base_path = '';
	
	/**
	 * Инициализация - определяем базовый путь
	 * @param string $base_path Базовый путь к assets (устаревший параметр, оставлен для совместимости)
	 */
	public static function init($base_path = '') {
		// Всегда используем корневой путь, так как теперь все страницы через SPA
		self::$base_path = '';
	}
	
	/**
	 * Подключить стиль
	 */
	public static function enqueue_style($handle, $src, $deps = [], $version = '1.0') {
		self::$styles[$handle] = [
			'src' => $src,
			'deps' => $deps,
			'version' => $version
		];
	}
	
	/**
	 * Подключить скрипт
	 */
	public static function enqueue_script($handle, $src, $deps = [], $version = '1.0', $in_footer = true) {
		self::$scripts[$handle] = [
			'src' => $src,
			'deps' => $deps,
			'version' => $version,
			'in_footer' => $in_footer
		];
	}
	
	/**
	 * Получить базовый путь к assets
	 */
	public static function get_base_path() {
		return self::$base_path;
	}
	
	/**
	 * Вывести стили в head
	 */
	public static function wp_head() {
		foreach (self::$styles as $handle => $style) {
			$src = self::$base_path . $style['src'];
			$ver = $style['version'] ? '?v=' . $style['version'] : '';
			echo '<link rel="stylesheet" href="' . htmlspecialchars($src) . $ver . '">' . "\n\t";
		}
	}
	
	/**
	 * Вывести скрипты (в head или footer)
	 */
	public static function wp_footer($in_footer = true) {
		foreach (self::$scripts as $handle => $script) {
			if ($script['in_footer'] === $in_footer) {
				$src = self::$base_path . $script['src'];
				$ver = $script['version'] ? '?v=' . $script['version'] : '';
				echo '<script src="' . htmlspecialchars($src) . $ver . '"></script>' . "\n\t";
			}
		}
	}
	
	/**
	 * Вывести скрипты в head (если in_footer = false)
	 */
	public static function wp_head_scripts() {
		self::wp_footer(false);
	}
	
	/**
	 * Очистить все стили и скрипты (для переключения между страницами)
	 */
	public static function reset() {
		self::$styles = [];
		self::$scripts = [];
	}
	
	/**
	 * Подключить общие стили и скрипты (используются везде)
	 */
	public static function enqueue_common_assets() {
		self::enqueue_style('main-style', 'frontend/css/style.css');
		self::enqueue_script('api', 'frontend/js/api.js', [], '1.0', true);
		self::enqueue_script('error-handler', 'frontend/js/error-handler.js', [], '1.0', true);
	}
	
	/**
	 * Подключить скрипты и стили для калькулятора (теперь используется для всех страниц SPA)
	 */
	public static function enqueue_calculator_assets() {
		self::enqueue_common_assets();
		self::enqueue_script('router', 'frontend/js/router.js', ['api'], '1.0', true);
		self::enqueue_script('calculator', 'frontend/js/calculator.js', [], '1.0', true);
		self::enqueue_script('materials', 'frontend/js/materials.js', [], '1.0', true);
		self::enqueue_script('operations', 'frontend/js/operations.js', [], '1.0', true);
		self::enqueue_script('units', 'frontend/js/units.js', [], '1.0', true);
		self::enqueue_script('product-types', 'frontend/js/product_types.js', [], '1.0', true);
		self::enqueue_script('coefficients', 'frontend/js/coefficients.js', [], '1.0', true);
		self::enqueue_script('users', 'frontend/js/users.js', [], '1.0', true);
		self::enqueue_script('app', 'frontend/js/app.js', ['router'], '1.0', true);
	}
	
	/**
	 * Подключить скрипты и стили для reference страниц
	 * @param string $page Название страницы (materials, operations, units, product_types, coefficients)
	 * @deprecated Используйте enqueue_calculator_assets() - теперь все страницы через SPA
	 */
	public static function enqueue_reference_assets($page) {
		// Все reference страницы теперь используют те же assets что и calculator
		self::enqueue_calculator_assets();
	}
	
	/**
	 * Подключить скрипты и стили для страницы профиля
	 */
	public static function enqueue_profile_assets() {
		self::enqueue_common_assets();
	}
	
	/**
	 * Подключить скрипты и стили для страницы входа
	 */
	public static function enqueue_login_assets() {
		self::enqueue_common_assets();
		self::enqueue_script('login', 'frontend/js/login.js', [], '1.0', true);
	}
	
	/**
	 * Подключить скрипты и стили для главной страницы
	 */
	public static function enqueue_home_assets() {
		self::enqueue_common_assets();
	}
}
