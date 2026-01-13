<?php
/**
 * Шаблон header
 * 
 * Использование:
 * $page_title - заголовок страницы
 * require_once __DIR__ . '/backend/classes/Assets.php';
 * Assets::init(''); // или '../' для reference/
 */
if (!isset($page_title)) {
	$page_title = 'Калькулятор себестоимости';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($page_title); ?></title>
	<?php Assets::wp_head(); ?>
</head>
<body<?php echo isset($body_class) ? ' class="' . htmlspecialchars($body_class) . '"' : ''; ?>>
