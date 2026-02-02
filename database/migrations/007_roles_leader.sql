-- Миграция: Добавление роли Руководитель (leader)
-- Роль Пользователь отображается как Менеджер в UI; в БД остаётся 'user'

ALTER TABLE users
MODIFY COLUMN role ENUM('super_admin', 'admin', 'support', 'user', 'leader', 'guest') NOT NULL DEFAULT 'guest';
