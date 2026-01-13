-- Схема базы данных для веб-приложения калькулятора себестоимости
-- MySQL / MariaDB

CREATE DATABASE IF NOT EXISTS cost_calculator_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cost_calculator_web;

-- Удаляем таблицы в правильном порядке (сначала зависимые)
DROP TABLE IF EXISTS calculations;
DROP TABLE IF EXISTS product_type_parameters;
DROP TABLE IF EXISTS operations;
DROP TABLE IF EXISTS product_types;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS coefficients;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS users;

-- Таблица пользователей для авторизации
CREATE TABLE users (
	id INT NOT NULL AUTO_INCREMENT,
	username VARCHAR(50) NOT NULL UNIQUE,
	email VARCHAR(100) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	first_name VARCHAR(100) NULL,
	last_name VARCHAR(100) NULL,
	role ENUM('super_admin', 'admin', 'user', 'guest') NOT NULL DEFAULT 'guest',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_username (username),
	INDEX idx_email (email),
	INDEX idx_first_name (first_name),
	INDEX idx_last_name (last_name),
	INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник единиц измерения
CREATE TABLE units (
	id INT NOT NULL AUTO_INCREMENT,
	name VARCHAR(50) NOT NULL UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник материалов
CREATE TABLE materials (
	id INT NOT NULL AUTO_INCREMENT,
	mark VARCHAR(100) NOT NULL UNIQUE,
	density DECIMAL(10, 4) NOT NULL DEFAULT 0 COMMENT 'Плотность в г/см³',
	price DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Цена в руб/кг',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_mark (mark)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник операций
CREATE TABLE operations (
	id INT NOT NULL AUTO_INCREMENT,
	number VARCHAR(50) NOT NULL UNIQUE,
	description TEXT NOT NULL,
	unit_id INT DEFAULT NULL,
	cost DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Стоимость в руб/ед',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE SET NULL,
	INDEX idx_number (number),
	INDEX idx_unit_id (unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник типов изделий
CREATE TABLE product_types (
	id INT NOT NULL AUTO_INCREMENT,
	name VARCHAR(100) NOT NULL UNIQUE,
	description TEXT,
	volume_formula TEXT NOT NULL,
	waste_formula TEXT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Параметры типов изделий
CREATE TABLE product_type_parameters (
	id INT NOT NULL AUTO_INCREMENT,
	product_type_id INT NOT NULL,
	name VARCHAR(50) NOT NULL,
	label VARCHAR(100) NOT NULL,
	unit VARCHAR(20) NOT NULL,
	required BOOLEAN NOT NULL DEFAULT TRUE,
	default_value DECIMAL(10, 2) DEFAULT NULL,
	sequence INT NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	FOREIGN KEY (product_type_id) REFERENCES product_types (id) ON DELETE CASCADE,
	UNIQUE KEY unique_param (product_type_id, name),
	INDEX idx_product_type_id (product_type_id),
	INDEX idx_sequence (sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник коэффициентов
CREATE TABLE coefficients (
	id INT NOT NULL AUTO_INCREMENT,
	name VARCHAR(100) NOT NULL UNIQUE,
	value DECIMAL(5, 2) NOT NULL DEFAULT 0 COMMENT 'Процент',
	description TEXT,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сохраненные расчеты (опционально)
CREATE TABLE calculations (
	id INT NOT NULL AUTO_INCREMENT,
	user_id INT DEFAULT NULL,
	product_name VARCHAR(200) NOT NULL,
	material_id INT NOT NULL,
	product_type_id INT NOT NULL,
	parameters JSON DEFAULT NULL,
	operations JSON DEFAULT NULL,
	result JSON DEFAULT NULL,
	created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
	FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE RESTRICT,
	FOREIGN KEY (product_type_id) REFERENCES product_types (id) ON DELETE RESTRICT,
	INDEX idx_user_id (user_id),
	INDEX idx_created_at (created_at),
	INDEX material_id (material_id),
	INDEX product_type_id (product_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
