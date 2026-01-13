/**
 * Виджет чата техподдержки
 */

class SupportChat {
	constructor() {
		this.chatId = null;
		this.userId = null; // ID пользователя, с которым чат (для поддержки)
		this.isSupport = false;
		this.currentUser = null;
		this.messages = [];
		this.pollingInterval = null;
		this.isOpen = false;
		this.container = null;
		this.isSending = false; // Флаг для предотвращения двойной отправки
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
			}
		} catch (error) {
			console.error('Ошибка проверки авторизации:', error);
		}
	}

	/**
	 * Открывает чат
	 */
	async open(userId = null) {
		this.userId = userId;
		this.isOpen = true;
		
		if (this.isSupport && userId) {
			// Для поддержки - открываем чат с конкретным пользователем
			const chat = await API.getMyChat();
			// Получаем чат пользователя
			const chats = await API.getSupportChats();
			const userChat = chats.find(c => c.user_id == userId);
			if (userChat) {
				this.chatId = userChat.id;
			} else {
				// Создаем чат для пользователя
				await API.sendSupportMessage(null, '', userId);
				const chats2 = await API.getSupportChats();
				const userChat2 = chats2.find(c => c.user_id == userId);
				this.chatId = userChat2 ? userChat2.id : null;
			}
		} else {
			// Для обычного пользователя - получаем его чат
			const chat = await API.getMyChat();
			this.chatId = chat ? chat.id : null;
		}

		this.render();
		await this.loadMessages(true); // Первая загрузка
		this.startPolling();
	}

	/**
	 * Закрывает чат
	 */
	close() {
		this.isOpen = false;
		this.stopPolling();
		if (this.container) {
			this.container.remove();
			this.container = null;
		}
	}

	/**
	 * Рендерит виджет чата
	 */
	render() {
		// Удаляем старый контейнер, если есть
		if (this.container) {
			this.container.remove();
		}

		const chatTitle = this.isSupport && this.userId 
			? 'Чат с пользователем'
			: 'Чат с техподдержкой';

		this.container = document.createElement('div');
		this.container.className = 'support-chat-widget';
		this.container.innerHTML = `
			<div class="support-chat-header">
				<div class="support-chat-title">${chatTitle}</div>
				<button class="support-chat-close" onclick="window.supportChat.close()">&times;</button>
			</div>
			<div class="support-chat-messages" id="supportChatMessages">
				<div class="support-chat-loading">Загрузка сообщений...</div>
			</div>
			<div class="support-chat-input-area">
				<div class="support-chat-file-area" id="supportChatFileArea">
					<div class="support-chat-file-dropzone" id="supportChatFileDropzone">
						<input type="file" id="supportChatFileInput" multiple style="display: none;">
						<button class="support-chat-file-btn" onclick="document.getElementById('supportChatFileInput').click()">
							📎 Прикрепить файл
						</button>
						<span class="support-chat-file-hint">или перетащите файл сюда</span>
					</div>
					<div class="support-chat-files-list" id="supportChatFilesList"></div>
				</div>
				<div class="support-chat-input-wrapper">
					<textarea 
						id="supportChatInput" 
						class="support-chat-input" 
						placeholder="Введите сообщение..."
						rows="2"
					></textarea>
					<button class="support-chat-send-btn" id="supportChatSendBtn">Отправить</button>
				</div>
			</div>
		`;

		document.body.appendChild(this.container);

		// Небольшая задержка для гарантии, что DOM обновлен
		setTimeout(() => {
			// Обработчики событий
			const input = document.getElementById('supportChatInput');
			const sendBtn = document.getElementById('supportChatSendBtn');
			
			if (input) {
				// Добавляем обработчик Enter
				input.addEventListener('keydown', (e) => {
					if (e.key === 'Enter' && !e.shiftKey && !this.isSending) {
						e.preventDefault();
						this.sendMessage();
					}
				});
			}
			
			if (sendBtn) {
				// Добавляем обработчик клика
				sendBtn.addEventListener('click', (e) => {
					e.preventDefault();
					e.stopPropagation();
					if (!this.isSending) {
						this.sendMessage();
					}
				});
			}

			// Обработка загрузки файлов
			this.setupFileUpload();
		}, 100);
	}

	/**
	 * Настраивает загрузку файлов
	 */
	setupFileUpload() {
		const fileInput = document.getElementById('supportChatFileInput');
		const dropzone = document.getElementById('supportChatFileDropzone');
		const filesList = document.getElementById('supportChatFilesList');

		if (!fileInput || !dropzone) return;

		this.selectedFiles = [];

		// Обработка выбора файлов
		fileInput.addEventListener('change', (e) => {
			this.handleFiles(Array.from(e.target.files));
		});

		// Drag & Drop
		dropzone.addEventListener('dragover', (e) => {
			e.preventDefault();
			dropzone.classList.add('dragover');
		});

		dropzone.addEventListener('dragleave', () => {
			dropzone.classList.remove('dragover');
		});

		dropzone.addEventListener('drop', (e) => {
			e.preventDefault();
			dropzone.classList.remove('dragover');
			this.handleFiles(Array.from(e.dataTransfer.files));
		});

		this.updateFilesList();
	}

	/**
	 * Обрабатывает выбранные файлы
	 */
	handleFiles(files) {
		const maxSize = 10 * 1024 * 1024; // 10MB
		const allowedTypes = [
			'image/jpeg', 'image/png', 'image/gif', 'image/webp',
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'text/plain'
		];

		for (const file of files) {
			if (file.size > maxSize) {
				alert(`Файл "${file.name}" слишком большой (максимум 10MB)`);
				continue;
			}

			if (!allowedTypes.includes(file.type)) {
				alert(`Файл "${file.name}" имеет недопустимый тип`);
				continue;
			}

			this.selectedFiles.push(file);
		}

		this.updateFilesList();
	}

	/**
	 * Обновляет список выбранных файлов
	 */
	updateFilesList() {
		const filesList = document.getElementById('supportChatFilesList');
		if (!filesList) return;

		if (this.selectedFiles.length === 0) {
			filesList.innerHTML = '';
			return;
		}

		filesList.innerHTML = this.selectedFiles.map((file, index) => `
			<div class="support-chat-file-item">
				<span class="support-chat-file-name">${this.escapeHtml(file.name)}</span>
				<button class="support-chat-file-remove" onclick="window.supportChat.removeFile(${index})">&times;</button>
			</div>
		`).join('');
	}

	/**
	 * Удаляет файл из списка
	 */
	removeFile(index) {
		this.selectedFiles.splice(index, 1);
		this.updateFilesList();
	}

	/**
	 * Загружает сообщения
	 */
	async loadMessages(isInitial = false) {
		if (!this.chatId) return;

		try {
			// Всегда получаем все сообщения при начальной загрузке
			// Для обновлений используется polling
			this.messages = await API.getChatMessages(this.chatId, 0);
			this.renderMessages();
			this.markAsRead();
		} catch (error) {
			// Игнорируем ошибки 503 (временная недоступность сервера)
			if (error.message && error.message.includes('503')) {
				return;
			}
			
			console.error('Ошибка загрузки сообщений:', error);
			const messagesContainer = document.getElementById('supportChatMessages');
			if (messagesContainer && isInitial) {
				messagesContainer.innerHTML = `<div class="support-chat-error">Ошибка загрузки сообщений: ${error.message}</div>`;
			}
		}
	}

	/**
	 * Рендерит сообщения
	 */
	renderMessages() {
		const container = document.getElementById('supportChatMessages');
		if (!container) return;

		if (this.messages.length === 0) {
			container.innerHTML = '<div class="support-chat-empty">Пока нет сообщений</div>';
			return;
		}

		container.innerHTML = this.messages.map(msg => {
			const isMyMessage = msg.sender_id == this.currentUser.id;
			const senderName = this.getSenderName(msg);
			const time = new Date(msg.created_at).toLocaleTimeString('ru-RU', { 
				hour: '2-digit', 
				minute: '2-digit' 
			});

			let attachmentsHtml = '';
			if (msg.attachments && msg.attachments.length > 0) {
				attachmentsHtml = msg.attachments.map(att => {
					const isImage = att.mime_type && att.mime_type.startsWith('image/');
					const url = API.getSupportAttachmentUrl(att.id);
					
					if (isImage) {
						return `<div class="support-chat-attachment">
							<a href="${url}" target="_blank">
								<img src="${url}" alt="${this.escapeHtml(att.filename)}" class="support-chat-attachment-image">
							</a>
							<div class="support-chat-attachment-name">${this.escapeHtml(att.filename)}</div>
						</div>`;
					} else {
						return `<div class="support-chat-attachment">
							<a href="${url}" target="_blank" class="support-chat-attachment-link">
								📎 ${this.escapeHtml(att.filename)}
							</a>
						</div>`;
					}
				}).join('');
			}

			return `
				<div class="support-chat-message ${isMyMessage ? 'my-message' : ''}">
					<div class="support-chat-message-header">
						<span class="support-chat-message-sender">${this.escapeHtml(senderName)}</span>
						<span class="support-chat-message-time">${time}</span>
					</div>
					<div class="support-chat-message-text">${this.formatMessage(msg.message)}</div>
					${attachmentsHtml}
				</div>
			`;
		}).join('');

		// Прокручиваем вниз
		container.scrollTop = container.scrollHeight;
	}

	/**
	 * Получает имя отправителя
	 */
	getSenderName(message) {
		if (message.sender_id == this.currentUser.id) {
			return 'Вы';
		}
		
		const fullName = [message.first_name, message.last_name].filter(Boolean).join(' ');
		return fullName || message.username || 'Пользователь';
	}

	/**
	 * Форматирует сообщение (защита от XSS и переносы строк)
	 */
	formatMessage(text) {
		if (!text) return '';
		return this.escapeHtml(text).replace(/\n/g, '<br>');
	}

	/**
	 * Экранирует HTML
	 */
	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Отправляет сообщение
	 */
	async sendMessage() {
		// Защита от двойной отправки
		if (this.isSending) {
			return;
		}

		const input = document.getElementById('supportChatInput');
		const message = input ? input.value.trim() : '';

		if (!message && (!this.selectedFiles || this.selectedFiles.length === 0)) {
			return;
		}

		if (!this.chatId && !this.isSupport) {
			// Создаем чат при первом сообщении
			try {
				const chat = await API.getMyChat();
				this.chatId = chat ? chat.id : null;
			} catch (error) {
				console.error('Ошибка создания чата:', error);
				alert('Ошибка создания чата: ' + error.message);
				return;
			}
		}

		if (!this.chatId) {
			alert('Ошибка: чат не создан');
			return;
		}

		this.isSending = true;
		
		// Блокируем кнопку отправки
		const sendBtn = document.querySelector('.support-chat-send-btn');
		if (sendBtn) {
			sendBtn.disabled = true;
			sendBtn.textContent = 'Отправка...';
		}

		try {
			let messageText = message;
			// Если есть файлы, отправляем их
			if (this.selectedFiles && this.selectedFiles.length > 0) {
				for (const file of this.selectedFiles) {
					await API.uploadSupportFile(this.chatId, file, messageText, this.userId);
					messageText = ''; // Сообщение отправляется только с первым файлом
				}
				this.selectedFiles = [];
				this.updateFilesList();
			} else if (messageText) {
				await API.sendSupportMessage(this.chatId, messageText, this.userId);
			}

			if (input) {
				input.value = '';
			}

			// Перезагружаем сообщения (полная загрузка после отправки)
			// Небольшая задержка, чтобы сервер успел обработать сообщение
			setTimeout(async () => {
				await this.loadMessages(true);
			}, 500);
		} catch (error) {
			console.error('Ошибка отправки сообщения:', error);
			alert('Ошибка отправки сообщения: ' + error.message);
		} finally {
			this.isSending = false;
			
			// Разблокируем кнопку отправки
			if (sendBtn) {
				sendBtn.disabled = false;
				sendBtn.textContent = 'Отправить';
			}
		}
	}

	/**
	 * Помечает сообщения как прочитанные
	 */
	async markAsRead() {
		if (!this.chatId) return;

		try {
			await API.markSupportMessagesRead(this.chatId, this.userId);
		} catch (error) {
			console.error('Ошибка пометки сообщений как прочитанных:', error);
		}
	}

	/**
	 * Запускает polling для проверки новых сообщений
	 */
	startPolling() {
		this.stopPolling();
		
		let isPolling = false;
		
		const poll = async () => {
			// Защита от параллельных запросов
			if (isPolling || !this.isOpen || !this.chatId || this.isSending) {
				return;
			}
			
			isPolling = true;
			
			try {
				// Используем обычный запрос с проверкой только новых сообщений
				const lastMessageId = this.messages.length > 0 
					? Math.max(...this.messages.map(m => m.id))
					: 0;
				
				const newMessages = await API.getChatMessages(this.chatId, lastMessageId);
				
				if (newMessages && newMessages.length > 0) {
					// Проверяем, что сообщения действительно новые (защита от дублей)
					const existingIds = new Set(this.messages.map(m => m.id));
					const uniqueNewMessages = newMessages.filter(m => !existingIds.has(m.id));
					
					if (uniqueNewMessages.length > 0) {
						// Добавляем только уникальные новые сообщения
						this.messages = [...this.messages, ...uniqueNewMessages];
						this.renderMessages();
						this.markAsRead();
						
						// Прокручиваем вниз при новых сообщениях
						const container = document.getElementById('supportChatMessages');
						if (container) {
							container.scrollTop = container.scrollHeight;
						}
					}
				}
			} catch (error) {
				// Игнорируем ошибки 503 и таймауты
				if (error.message && 
				    !error.message.includes('503') &&
				    !error.message.includes('timeout') &&
				    !error.message.includes('Service Unavailable')) {
					console.error('Ошибка polling:', error);
				}
			} finally {
				isPolling = false;
			}
		};
		
		// Запускаем polling каждые 5 секунд
		this.pollingInterval = setInterval(() => {
			if (this.isOpen && this.chatId && !this.isSending) {
				poll();
			}
		}, 5000);
		
		// Первый запрос сразу
		poll();
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
}

// Создаем глобальный экземпляр
window.supportChat = new SupportChat();
