/**
 * Иконка чата с уведомлениями
 */

class ChatIcon {
	constructor() {
		this.unreadCount = 0;
		this.isSupport = false;
		this.currentUser = null;
		this.pollingInterval = null;
		this.iconElement = null;
		this.init();
	}

	async init() {
		// Проверяем роль пользователя
		try {
			const authData = await API.checkAuth();
			if (authData.logged_in && authData.user) {
				this.currentUser = authData.user;
				this.isSupport = authData.user.role === 'support' || 
				                authData.user.role === 'super_admin' || 
				                authData.user.role === 'admin';
			} else {
				// Пользователь не авторизован - не показываем иконку и не запускаем polling
				return;
			}
		} catch (error) {
			// Ошибка проверки авторизации - не показываем иконку
			return;
		}

		this.render();
		this.updateUnreadCount();
		this.startPolling();
	}

	/**
	 * Рендерит иконку чата
	 */
	render() {
		// Удаляем старую иконку, если есть
		if (this.iconElement) {
			this.iconElement.remove();
		}

		this.iconElement = document.createElement('div');
		this.iconElement.className = 'chat-icon';
		this.iconElement.innerHTML = `
			<div class="chat-icon-button" onclick="window.chatIcon.openChat()">
				💬
				<span class="chat-icon-badge" id="chatIconBadge" style="display: none;">0</span>
			</div>
		`;

		document.body.appendChild(this.iconElement);
	}

	/**
	 * Обновляет количество непрочитанных сообщений
	 */
	async updateUnreadCount() {
		try {
			// Проверяем, что метод существует
			if (typeof API.getSupportUnreadCount !== 'function') {
				console.warn('API.getSupportUnreadCount не доступен');
				return;
			}
			
			const data = await API.getSupportUnreadCount();
			this.unreadCount = data.count || 0;
			this.updateBadge();
		} catch (error) {
			// При 401 (не авторизован) - останавливаем polling и скрываем иконку
			if (error.message && (
				error.message.includes('401') || 
				error.message.includes('Требуется авторизация') ||
				error.message.includes('Unauthorized')
			)) {
				this.stopPolling();
				if (this.iconElement) {
					this.iconElement.remove();
					this.iconElement = null;
				}
				return;
			}
			
			// Игнорируем ошибки таймаута и 503 (временная недоступность сервера)
			if (error.message && 
			    !error.message.includes('timeout') && 
			    !error.message.includes('503') &&
			    !error.message.includes('Service Unavailable')) {
				console.error('Ошибка получения количества непрочитанных сообщений:', error);
			}
		}
	}

	/**
	 * Обновляет бейдж с количеством непрочитанных
	 */
	updateBadge() {
		const badge = document.getElementById('chatIconBadge');
		if (!badge) return;

		if (this.unreadCount > 0) {
			badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
			badge.style.display = 'flex';
		} else {
			badge.style.display = 'none';
		}
	}

	/**
	 * Открывает чат
	 */
	openChat() {
		if (window.supportChat) {
			window.supportChat.open();
		}
	}

	/**
	 * Запускает polling для обновления счетчика
	 */
	startPolling() {
		this.stopPolling();
		this.pollingInterval = setInterval(() => {
			this.updateUnreadCount();
		}, 10000); // Каждые 10 секунд
	}

	/**
	 * Останавливает polling
	 */
	stopPolling() {
		if (this.pollingInterval) {
			clearInterval(this.pollingInterval);
			this.pollingInterval = null;
		}
	}

	/**
	 * Получает количество непрочитанных сообщений от конкретного пользователя (для поддержки)
	 */
	async getUnreadCountFromUser(userId) {
		try {
			const data = await API.getSupportUnreadCount(userId);
			return data.count || 0;
		} catch (error) {
			console.error('Ошибка получения количества непрочитанных сообщений:', error);
			return 0;
		}
	}
}

// Создаем глобальный экземпляр
window.chatIcon = new ChatIcon();
