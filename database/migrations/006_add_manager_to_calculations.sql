-- Добавление полей менеджера и примечания в таблицу calculations
-- manager_name: исполнитель расчета (Фамилия Имя или username (email))
-- manager_note: необязательное примечание менеджера

ALTER TABLE calculations
ADD COLUMN manager_name VARCHAR(255) NULL AFTER user_id,
ADD COLUMN manager_note TEXT NULL AFTER manager_name;
