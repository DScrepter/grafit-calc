/**
 * Глобальный обработчик ошибок JavaScript
 */

// Обработчик необработанных ошибок
window.addEventListener('error', (event) => {
	if (window.ErrorLogger) {
		ErrorLogger.exception(event.error || new Error(event.message), {
			filename: event.filename,
			lineno: event.lineno,
			colno: event.colno,
		});
	} else {
		console.error('ErrorLogger не загружен. Ошибка:', event.error || event.message);
	}
});

// Обработчик необработанных промисов (rejected promises)
window.addEventListener('unhandledrejection', (event) => {
	if (window.ErrorLogger) {
		ErrorLogger.exception(event.reason || new Error('Unhandled Promise Rejection'), {
			promise: event.promise,
		});
	} else {
		console.error('ErrorLogger не загружен. Unhandled rejection:', event.reason);
	}
});

// Обработчик ошибок в консоли (если доступен)
if (window.console && window.console.error) {
	const originalError = console.error;
	console.error = function(...args) {
		// Вызываем оригинальный метод
		originalError.apply(console, args);
		
		// Логируем на сервер, если ErrorLogger доступен
		if (window.ErrorLogger) {
			ErrorLogger.error('Console error', {
				args: args.map(arg => {
					if (arg instanceof Error) {
						return {
							name: arg.name,
							message: arg.message,
							stack: arg.stack,
						};
					}
					return String(arg);
				}),
			});
		}
	};
}
