/**
 * Страница управления обновлениями базы данных
 * Доступна только для супер-администраторов
 */

const MigrationsPage = {
	status: null,

	async load(container) {
		container.innerHTML = `
			<div class="page-content">
				<h1 class="page-title">Обновления базы данных</h1>
				<div class="info-box mb-20">
					<p><strong>Важная информация о безопасности:</strong></p>
					<ul style="margin: 10px 0 0 20px; padding: 0;">
						<li>Обновления применяются в <strong>транзакциях</strong> - при ошибке все изменения автоматически откатываются</li>
						<li>Операции типа <code>ADD COLUMN</code> безопасны и не затрагивают существующие данные</li>
						<li>Операции типа <code>DROP TABLE</code>, <code>DELETE</code> могут удалить данные - перед применением рекомендуется сделать резервную копию</li>
						<li>Каждое обновление применяется только один раз - повторное применение невозможно</li>
					</ul>
				</div>
				<div id="migrationsStatus" class="loading">Загрузка статуса обновлений...</div>
				<div id="migrationsActions" style="margin-top: 20px;"></div>
			</div>
		`;

		await this.loadStatus();
	},

	async loadStatus() {
		try {
			this.status = await API.getMigrationsStatus();
			this.renderStatus();
		} catch (error) {
			document.getElementById('migrationsStatus').innerHTML = 
				'<div class="error-message">Ошибка загрузки: ' + error.message + '</div>';
		}
	},

	renderStatus() {
		const container = document.getElementById('migrationsStatus');
		const actionsContainer = document.getElementById('migrationsActions');

		let html = '<div class="migrations-status">';
		
		// Общая информация
		html += '<div class="status-summary">';
		html += `<div class="status-item"><strong>Всего обновлений:</strong> ${this.status.total_available}</div>`;
		html += `<div class="status-item"><strong>Применено:</strong> ${this.status.total_applied}</div>`;
		html += `<div class="status-item"><strong>Ожидают применения:</strong> <span class="pending-count">${this.status.total_pending}</span></div>`;
		html += '</div>';

		// Ожидающие обновления
		if (this.status.pending.length > 0) {
			html += '<div class="pending-migrations">';
			html += '<h3>Ожидающие обновления:</h3>';
			html += '<ul class="migrations-list">';
			
			const pendingInfo = this.status.pending_info || [];
			this.status.pending.forEach((migration, index) => {
				const info = pendingInfo[index] || { is_dangerous: false, description: '' };
				const dangerClass = info.is_dangerous ? 'dangerous' : '';
				const dangerBadge = info.is_dangerous ? '<span class="danger-badge">⚠ Опасная операция</span>' : '<span class="safe-badge">✓ Безопасная</span>';
				
				html += `<li class="${dangerClass}">
					<div class="migration-details">
						<span class="migration-name">${this.escapeHtml(migration)}</span>
						${info.description ? `<div class="migration-description">${this.escapeHtml(info.description)}</div>` : ''}
						${dangerBadge}
					</div>
					<button class="btn btn-small btn-primary" onclick="MigrationsPage.applySingle('${this.escapeHtml(migration)}', ${info.is_dangerous})">Применить</button>
				</li>`;
			});
			html += '</ul>';
			html += '</div>';
		} else {
			html += '<div class="success-message">Все обновления применены. База данных актуальна.</div>';
		}

		// Примененные обновления
		if (this.status.applied.length > 0) {
			html += '<div class="applied-migrations">';
			html += '<h3>Примененные обновления:</h3>';
			html += '<ul class="migrations-list">';
			this.status.applied.forEach(migration => {
				html += `<li class="applied">
					<span class="migration-name">${this.escapeHtml(migration)}</span>
					<span class="status-badge">✓ Применена</span>
				</li>`;
			});
			html += '</ul>';
			html += '</div>';
		}

		html += '</div>';
		container.innerHTML = html;

		// Кнопки действий
		if (this.status.pending.length > 0) {
			actionsContainer.innerHTML = `
				<button class="btn btn-primary" onclick="MigrationsPage.applyAll()">
					Применить все ожидающие обновления (${this.status.pending.length})
				</button>
				<button class="btn btn-secondary" onclick="MigrationsPage.loadStatus()">
					Обновить статус
				</button>
			`;
		} else {
			actionsContainer.innerHTML = `
				<button class="btn btn-secondary" onclick="MigrationsPage.loadStatus()">
					Обновить статус
				</button>
			`;
		}
	},

	async applyAll() {
		const pendingInfo = this.status.pending_info || [];
		const hasDangerous = pendingInfo.some(info => info.is_dangerous);
		
		let confirmMsg = `Вы уверены, что хотите применить все ${this.status.pending.length} ожидающие обновления?`;
		if (hasDangerous) {
			confirmMsg += '\n\n⚠ ВНИМАНИЕ: Среди обновлений есть операции, которые могут изменить или удалить данные!\n\nРекомендуется сделать резервную копию базы данных перед применением.';
		}
		confirmMsg += '\n\nВсе обновления применяются в транзакциях - при ошибке изменения будут откачены.';
		
		if (!confirm(confirmMsg)) {
			return;
		}

		const actionsContainer = document.getElementById('migrationsActions');
		actionsContainer.innerHTML = '<div class="loading">Применение обновлений...</div>';

		try {
			const result = await API.applyMigrations();
			if (result.success) {
				alert(`Успешно применено обновлений: ${result.applied.length}`);
				await this.loadStatus();
			} else {
				let errorMsg = 'Ошибки при применении обновлений:\n';
				result.errors.forEach(err => {
					errorMsg += `- ${err.migration}: ${err.error}\n`;
				});
				alert(errorMsg);
				await this.loadStatus();
			}
		} catch (error) {
			alert('Ошибка применения обновлений: ' + error.message);
			await this.loadStatus();
		}
	},

	async applySingle(migrationName, isDangerous = false) {
		let confirmMsg = `Применить обновление "${migrationName}"?`;
		if (isDangerous) {
			confirmMsg += '\n\n⚠ ВНИМАНИЕ: Это обновление содержит операции, которые могут изменить или удалить данные!\n\nРекомендуется сделать резервную копию базы данных перед применением.';
		}
		confirmMsg += '\n\nОбновление применяется в транзакции - при ошибке изменения будут откачены.';
		
		if (!confirm(confirmMsg)) {
			return;
		}

		try {
			const result = await API.applyMigration(migrationName);
			if (result.success) {
				alert(result.message);
				await this.loadStatus();
			} else {
				alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
			}
		} catch (error) {
			alert('Ошибка применения обновления: ' + error.message);
		}
	},

	escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
};
