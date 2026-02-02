-- Марки материалов как строка в operations, удаление таблицы связки
ALTER TABLE operations ADD COLUMN material_marks VARCHAR(500) NULL DEFAULT NULL COMMENT 'Марки материалов через запятую';
DROP TABLE IF EXISTS operation_materials;
