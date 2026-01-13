-- Обновление: Добавление поля updated_at в таблицу calculations
-- Дата создания: 2026-01-13

ALTER TABLE calculations 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;
