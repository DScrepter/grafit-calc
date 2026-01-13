-- SQL Dump created by dump-db.php
-- Date: 2026-01-13 08:42:26
-- Database: cost_calculator_web

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- --------------------------------------------------------
-- Table structure for table `calculations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `calculations`;
CREATE TABLE `calculations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `product_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_id` int NOT NULL,
  `product_type_id` int NOT NULL,
  `parameters` json DEFAULT NULL,
  `operations` json DEFAULT NULL,
  `result` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `product_type_id` (`product_type_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `calculations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calculations_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `calculations_ibfk_3` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `coefficients`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `coefficients`;
CREATE TABLE `coefficients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Процент',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `coefficients`

INSERT INTO `coefficients` (`id`, `name`, `value`, `description`, `created_at`, `updated_at`) VALUES ('1', 'Налоги', '40.00', '', '2025-08-13 14:31:09', '2025-08-13 14:31:09');
INSERT INTO `coefficients` (`id`, `name`, `value`, `description`, `created_at`, `updated_at`) VALUES ('2', 'ОХР', '70.00', '', '2025-08-13 14:31:24', '2025-08-13 14:31:24');


-- --------------------------------------------------------
-- Table structure for table `materials`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mark` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `density` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Плотность в г/см³',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Цена в руб/кг',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mark` (`mark`),
  KEY `idx_mark` (`mark`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `materials`

INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('27', 'ГЭ', '1.5600', '205.00', '2025-08-11 18:00:39', '2025-08-11 18:00:39');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('28', 'МПГ-6', '1.6500', '1680.00', '2025-08-11 18:02:05', '2025-08-11 18:02:05');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('29', 'МПГ-7', '1.7000', '1680.00', '2025-08-11 18:16:51', '2025-08-11 18:16:51');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('30', 'МПГ-8', '1.8000', '1980.00', '2025-08-12 02:42:45', '2025-08-12 02:42:45');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('31', 'АГ-1500', '1.7800', '1680.00', '2025-08-12 02:42:45', '2025-08-12 02:42:45');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('32', 'АГ-1500 Б83', '2.4800', '3900.00', '2025-08-12 02:42:45', '2025-08-12 02:42:45');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('33', 'АГ-1500 СО5', '2.4900', '3900.00', '2025-08-12 02:42:45', '2025-08-12 02:42:45');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('34', 'GS-1800', '1.8000', '2500.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('35', 'GS-1900', '1.8200', '2900.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('36', 'МГ', '1.5000', '850.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('37', 'МГ-1', '1.6500', '1680.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('38', '3ОПГ', '1.7600', '1680.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('39', 'АРВ', '1.7300', '1100.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('40', 'ППГ', '1.7000', '850.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('41', 'ЭГ-ФФ', '1.7500', '1500.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('42', 'ЭГ-ФФФ', '1.9500', '1500.00', '2025-08-12 02:42:46', '2025-08-12 02:42:46');
INSERT INTO `materials` (`id`, `mark`, `density`, `price`, `created_at`, `updated_at`) VALUES ('43', 'Тест', '45.0000', '86.00', '2026-01-12 20:35:01', '2026-01-12 20:35:01');


-- --------------------------------------------------------
-- Table structure for table `operations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `operations`;
CREATE TABLE `operations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_id` int DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Стоимость в руб/ед',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  KEY `idx_number` (`number`),
  KEY `idx_unit_id` (`unit_id`),
  CONSTRAINT `operations_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `operations`

INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('1', '2', 'Изготовление электродов ВДП сечением 10-15 мм.', '34', '3.56', '2025-08-12 11:54:53', '2025-08-12 11:54:53');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('2', '3', 'Изготовление электродов ВДП сечением 15 и более мм., до 0,5кг.', '34', '7.80', '2025-08-12 11:54:53', '2025-08-12 11:54:53');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('3', '4', 'Изготовление электродов ВДП сечением 15 и более мм., свыше 0,5кг.', '34', '20.51', '2025-08-12 11:54:53', '2025-08-12 11:54:53');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('4', '5', 'Изготовление фрезерованных кубиков до 0,5кг.', '34', '74.56', '2025-08-12 11:54:53', '2025-08-12 11:54:53');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('5', '6', 'Изготовление блочной продукции в размер, из под пилы весом более 5 кг.', '34', '86.94', '2025-08-12 11:54:53', '2025-08-12 11:54:53');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('6', '7', 'Изготовление блочной продукции весом более 50кг. Из под пилы.', '34', '361.53', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('7', '8', 'Изготовление блочной продукции весом более 50кг. С фрезеровкой граней.', '34', '510.00', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('8', '9', 'Изготовление лотков, больших деталей по чертежам с расточкой и фрезеровкой.', '34', '1386.98', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('9', '10', 'Изготовление сложных тиглей большого диам.+токосьёмное кольцо и длинные нагреватели.', '34', '3580.70', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('10', '11', 'Изготовление стержней диам. До 20мм., длинной более 1000мм.', '34', '1908.70', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('11', '12', 'Распил стержней и пластин', '34', '2.39', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('12', '13', 'Изготовление тонкостенных изделий диам.500 и более мм.+ нагреватели сложной формы.', '34', '4774.28', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('13', '14', 'Изготовление стерней и шайб диам. До 100мм.', '34', '101.96', '2025-08-12 11:54:54', '2025-08-12 11:54:54');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('14', '15', 'Изготовление простых конусов.', '34', '407.14', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('15', '16', 'Проточка заготовки на токарном станке диам. более 100 (стержни + шайбы)', '34', '245.37', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('16', '17', 'Изготовление втулок до Д100х200', '34', '156.53', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('17', '18', 'Изготовление изделий малого диаметра сложной формы.', '34', '235.29', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('18', '19', 'Изготовление простых втулок длинной более 250мм.', '34', '259.74', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('19', '20', 'Изготовление чехла', '34', '134.34', '2025-08-12 11:54:55', '2025-08-12 11:54:55');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('20', '21', 'Фрезеровка блочной продукции.', '34', '82.46', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('21', '22', 'Изготовление изделий диам.40-200мм.с нарезанием резьбы. Сложные изложницы.', '34', '506.64', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('22', '23', 'Плиты и прочее весом более 5,5кг.', '34', '289.84', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('23', '24', 'Изготовление больших фрезерованных плит с скосами, и изделий средней сложности.', '34', '540.88', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('24', '25', 'Изготовление фрезерованных плит и блоков., Тигли, подставки диаметром до 180мм. Не более 5,5кг - Средней сложности.', '34', '219.34', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('25', '26', 'Изготовление изделий повышенной сложности', '34', '880.63', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('26', '27', 'Изготовление труб длинной до 1000мм.', '34', '660.46', '2025-08-12 11:54:56', '2025-08-12 11:54:56');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('27', '28', 'Изготовление труб длинной 1500мм.+ очень сложные', '34', '1073.24', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('28', '29', 'Изготовление нагревателей Ø50*1360мм.', '34', '291.43', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('29', '30', 'Изготовление простой прямоугольной лодочки', '34', '395.20', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('30', '31', 'Изготовление лодочки с доп.фрезеровкой', '34', '428.04', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('31', '32', 'Изготовление цилиндрической лодочки', '34', '495.18', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('32', '33', 'Шлифовка электродов ОСЧ, СК', '34', '15.02', '2025-08-12 11:54:57', '2025-08-12 11:54:57');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('33', '34', 'Изготовление пластин толщиной до 6мм.', '34', '53.87', '2025-08-12 11:54:58', '2025-08-12 11:54:58');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('34', '35', 'Изготовление тигеля на ДИП-500, и фрезерованных блоков ДБУ', '34', '2019.79', '2025-08-12 11:54:58', '2025-08-12 11:54:58');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('35', '36', 'Изготовление втулок и шайб на ДИП-500', '34', '649.35', '2025-08-12 11:54:58', '2025-08-12 11:54:58');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('36', '37', 'Изготовление тигеля на ДИП-300', '34', '770.54', '2025-08-12 11:54:58', '2025-08-12 11:54:58');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('37', '38', 'Изготовление тигеля и колец на 1К62, а так же нарезка НП200+крышка патрона', '34', '119.29', '2025-08-12 11:54:58', '2025-08-12 11:54:58');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('38', '39', 'Изготовление фрезерованных пластин с допуском менее 0,1мм.', '34', '231.00', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('39', '40', 'Маленькие кубики из под пилы', '34', '13.12', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('40', '41', 'Изделия из плотного графита простой формы и стержни диам до 50мм.', '34', '69.18', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('41', '42', 'Изделия блочные средней сложности.', '34', '374.66', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('42', '43', 'Стержни из МПГ диам. 50-150мм. И блоки с допуском +-1мм.', '34', '133.45', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('43', '44', 'Тигли до 20мм.+электроды стыковочные', '34', '62.46', '2025-08-12 11:54:59', '2025-08-12 11:54:59');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('44', '45', 'Стержни из МПГ диам. Более 150мм.', '34', '230.24', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('45', '46', 'Изделия по чертежам диам. до 100мм.', '34', '364.60', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('46', '47', 'Диам. свыше 100мм.', '34', '611.22', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('47', '48', 'Изделия до 50мм с высокой точностью.', '34', '266.61', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('48', '49', 'Изготовление блочной продукции сечением до 50мм.с допуском 0,1мм. +блоки более 10кг.', '34', '259.72', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('49', '50', 'Изделия до 50мм с полировкой.', '34', '828.72', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('50', '51', 'Очень сложные изделия с высокой точностью.', NULL, '2387.14', '2025-08-12 11:55:00', '2025-08-12 11:55:00');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('51', '52', 'Изготовление блочной продукции сечением более 50мм с шлифовкой по чертежам.', '34', '624.56', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('52', '53', 'Диам. до 100мм.', '34', '295.97', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('53', '54', 'Диам. свыше 100мм.', '34', '496.15', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('54', '55', 'Антифрикционные кольца по чертежам.', '34', '1788.48', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('55', '56', 'Распил заготовок', '34', '49.00', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('56', '57', 'Изготовление блочной продукции сечением  50-150мм. с допуском 0,5', '34', '511.83', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('57', '58', 'Изделия с сверлением и стержни.', '34', '313.26', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('58', '59', 'Изготовление лопаток + тонкие пластины.', '34', '455.78', '2025-08-12 11:55:01', '2025-08-12 11:55:01');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('59', '60', 'Изготовление блочной продукции сечением более 50мм с шлифовкой', '34', '836.26', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('60', '61', 'Изделия простой формы.', '34', '116.96', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('61', '62', 'Сложные изделия с высокой точностью.', '34', '3413.59', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('62', '63', 'Диам. до 100мм.', '34', '511.32', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('63', '64', 'Диам. свыше 100мм.', '34', '819.59', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('64', '65', 'Диам. до 100мм. Сложной формы', '34', '2425.88', '2025-08-12 11:55:02', '2025-08-12 11:55:02');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('65', '66', 'Расфасовка в зип пакеты.', '37', '10.71', '2025-08-12 11:55:03', '2025-08-12 11:55:03');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('66', '67', 'Изготовление фасонины из угля.', '34', '81.97', '2025-08-12 11:55:03', '2025-08-12 11:55:03');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('67', '68', 'Изготовление угольных блоков более 30кг.', '34', '1967.33', '2025-08-12 11:55:03', '2025-08-12 11:55:03');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('68', '69', 'Склейка блоков ЭГ-ФФ(большие)', '34', '1147.61', '2025-08-12 11:55:03', '2025-08-12 11:55:03');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('69', '70', 'Ремонт блоков ЭГ-ФФ', '34', '28.69', '2025-08-12 11:55:03', '2025-08-12 11:55:03');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('70', '71', 'Ремонт больших блоков и частей ТО.', '36', '2869.02', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('71', '72', 'Пропитка блоков смолой + полимеризация.', '34', '74.59', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('72', '73', 'Перевозка материала со склада или улицы.', '36', '237.72', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('73', '74', 'Очистка графита в ручную', '36', '1557.47', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('74', '75', 'Просушка материалла.', '36', '819.72', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('75', '76', 'Изготовление ГЛС, пудры ГМЗ на мельнице.', '36', '3967.44', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('76', '77', 'Дробление материала с рассевом на фракции.', '36', '2038.61', '2025-08-12 11:55:04', '2025-08-12 11:55:04');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('77', '78', 'Дробление материала на мини. Дробилке', '37', '28.69', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('78', '79', 'Рассев материала на грохоте.', '36', '1788.48', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('79', '80', 'Затаривание стружки в МКР.', '36', '901.69', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('80', '81', 'Затаривание стружки в бумажные мешки.', '36', '1147.61', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('81', '82', 'Производство графитового порошка фр. Свыше 0,3мм.', '37', '81.38', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('82', '83', 'Пр-во графитового порошка фр. 0,06-0,3', '37', '253.37', '2025-08-12 11:55:05', '2025-08-12 11:55:05');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('83', '84', 'Изготовление прокладок МВ', '34', '295.32', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('84', '85', 'Изготовление блоков МВ-6 + сложные камеры(4-ёх ходов)', '34', '21655.08', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('85', '86', 'Изготовление распред. камеры МВ-6', '34', '8373.29', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('86', '87', 'Изготовление камер больше 1300мм', '34', '124602.69', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('87', '88', 'Склеивание блоков ТО диаметром более 700мм.', '34', '1836.17', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('88', '89', 'Сборка, опрессовка окраска ТО до 4блока.', '34', '7869.31', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('89', '90', 'Сборка, опрессовка окраска ТО более 6 блоков', '34', '9180.86', '2025-08-12 11:55:06', '2025-08-12 11:55:06');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('90', '91', 'Изготовление Графитон МВ-2 и аналог.', '34', '6151.18', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('91', '92', 'Опрессовка блоков', '34', '1233.91', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('92', '93', 'Изготовление блоков более 500кг, царг колонн, монотруб и др. сложные изделия.', '34', '40866.77', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('93', '94', 'Изготовление блока до 500 отверстий и 1Л1', '34', '24903.34', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('94', '95', 'Изготовление распред. камеры 1Л1', '34', '12559.93', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('95', '96', 'Изготовление блоков 500 отв. и более с доп. фрезеровкой', '34', '32482.62', '2025-08-12 11:55:07', '2025-08-12 11:55:07');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('96', '97', 'Строительные работы, монтажные, работы по благоустройству территории, кровли.', NULL, '1.49', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('97', '98', 'Выполнение общецеховых работ не связанных с обработкой графита.', '35', '459.04', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('98', '99', 'Приготовление материала, смешивание+гранулирование', '37', '11.90', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('99', '100', 'Изготовление труб ТО', '38', '279.45', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('100', '101', 'Изготовление плитки АТМ(1000 мм)', '34', '98.37', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('101', '102', 'Изготовление плитки АТМ(195 мм)', '34', '19.67', '2025-08-12 11:55:08', '2025-08-12 11:55:08');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('102', '103', 'Изготовление камер ТГП х20мм', '34', '2819.84', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('103', '104', 'Опрессовка камер ТГП', '34', '181.98', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('104', '105', 'Изготовлено камер ТГПх40мм', '34', '4229.76', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('105', '106', 'Изготовление опорной плиты с щтуцерами', '34', '5262.60', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('106', '107', 'Сборка пластинчатого ТО+опрессовка, окраска', '34', '7836.52', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('107', '108', 'Заточка ленточной пилы', '34', '295.10', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('108', '109', 'Развод ленточной пилы', '34', '327.89', '2025-08-12 11:55:09', '2025-08-12 11:55:09');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('109', '110', 'Заточка сверла.', '34', '76.74', '2025-08-12 11:55:10', '2025-08-12 11:55:10');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('110', '111', 'Обкатка сверла.', '34', '459.04', '2025-08-12 11:55:10', '2025-08-12 11:55:10');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('111', '112', 'Заточка дисковой пилы', '34', '130.45', '2025-08-12 11:55:10', '2025-08-12 11:55:10');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('112', '113', 'Перештабелёвка краном.', '36', '51.36', '2025-08-12 11:55:10', '2025-08-12 11:55:10');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('113', '114', 'Ремонт станков', '35', '295.10', '2025-08-12 11:55:10', '2025-08-12 11:55:10');
INSERT INTO `operations` (`id`, `number`, `description`, `unit_id`, `cost`, `created_at`, `updated_at`) VALUES ('114', 'Тест2', 'какое-то описание', '37', '123.00', '2026-01-12 20:36:48', '2026-01-12 20:36:48');


-- --------------------------------------------------------
-- Table structure for table `product_type_parameters`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `product_type_parameters`;
CREATE TABLE `product_type_parameters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_type_id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '1',
  `default_value` decimal(10,2) DEFAULT NULL,
  `sequence` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_param` (`product_type_id`,`name`),
  KEY `idx_product_type_id` (`product_type_id`),
  KEY `idx_sequence` (`sequence`),
  CONSTRAINT `product_type_parameters_ibfk_1` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `product_type_parameters`

INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('1', '12', 'outer_diameter', 'Внешний диаметр', 'мм', '1', NULL, '0');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('2', '12', 'inner_diameter', 'Внутренний диаметр', 'мм', '1', NULL, '1');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('3', '12', 'height', 'Высота', 'мм', '1', NULL, '2');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('4', '10', 'length', 'Длина', 'мм', '1', NULL, '0');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('5', '10', 'width', 'Ширина', 'мм', '1', NULL, '1');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('6', '10', 'height', 'Высота', 'мм', '1', NULL, '2');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('7', '11', 'diameter', 'Диаметр', 'мм', '1', NULL, '0');
INSERT INTO `product_type_parameters` (`id`, `product_type_id`, `name`, `label`, `unit`, `required`, `default_value`, `sequence`) VALUES ('8', '11', 'height', 'Высота', 'мм', '1', NULL, '1');


-- --------------------------------------------------------
-- Table structure for table `product_types`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `product_types`;
CREATE TABLE `product_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `volume_formula` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `waste_formula` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `product_types`

INSERT INTO `product_types` (`id`, `name`, `description`, `volume_formula`, `waste_formula`, `created_at`, `updated_at`) VALUES ('10', 'Кубоид', 'Прямоугольный параллелепипед', 'length * width * height', '((length*1) * (width*1) * (height*1)) - (length * width * height)', '2025-08-13 12:03:17', '2025-08-13 14:32:09');
INSERT INTO `product_types` (`id`, `name`, `description`, `volume_formula`, `waste_formula`, `created_at`, `updated_at`) VALUES ('11', 'Цилиндр', 'Круглый цилиндр', '3.14159 * (diameter / 2) ** 2 * height', '(3.14159 * ((diameter + 10) / 2) ** 2 * (height + 5)) - (3.14159 * (diameter / 2) ** 2 * height)', '2025-08-13 12:03:17', '2025-08-13 15:19:59');
INSERT INTO `product_types` (`id`, `name`, `description`, `volume_formula`, `waste_formula`, `created_at`, `updated_at`) VALUES ('12', 'Втулка', 'Полый цилиндр', '3.14159 * height * ((outer_diameter / 2) ** 2 - (inner_diameter / 2) ** 2)', '(3.14159 * (height + 5) * (((outer_diameter + 10) / 2) ** 2 - ((inner_diameter + 5) / 2) ** 2)) - (3.14159 * height * ((outer_diameter / 2) ** 2 - (inner_diameter / 2) ** 2))', '2025-08-13 12:03:17', '2025-08-13 12:05:30');


-- --------------------------------------------------------
-- Table structure for table `units`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `units`

INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES ('34', 'шт', '2025-08-12 03:06:01', '2025-08-12 03:06:01');
INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES ('35', 'час', '2025-08-12 03:06:02', '2025-08-12 03:06:02');
INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES ('36', 'тн', '2025-08-12 03:24:54', '2025-08-12 03:24:54');
INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES ('37', 'кг', '2025-08-12 03:25:03', '2025-08-12 03:25:03');
INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES ('38', 'м', '2025-08-12 03:25:15', '2025-08-12 03:25:15');


-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `role` enum('super_admin','admin','user','guest') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guest',
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_first_name` (`first_name`),
  KEY `idx_last_name` (`last_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`, `role`, `first_name`, `last_name`) VALUES ('1', 'screpter', 'dlsiderman@gmail.com', '$2y$10$qKvEKEuCr0SYjK7nFUnZJ.KMCLnvYtFMKvHYIE8n/PshNF2UbpfwO', '2026-01-12 19:48:31', '2026-01-12 21:03:05', 'super_admin', 'Дмитрий', 'Сидерман');
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`, `role`, `first_name`, `last_name`) VALUES ('2', 'dima', 'screpter@mail.ru', '$2y$10$zo2.Z5ioVJXnTv19I1Qunebb9xIpJbg3dcLwaJdDDblGnux3eWPgC', '2026-01-12 21:06:04', '2026-01-12 21:10:03', 'user', NULL, NULL);
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`, `role`, `first_name`, `last_name`) VALUES ('3', 'dimka', 'sidermandl@gmail.com', '$2y$10$H.Pt4NcjU8k0KZO1fBDVO.TiyHpPPUrZW0.KgKh6NJLLy.BtKpbPa', '2026-01-12 21:41:37', '2026-01-12 22:11:48', 'admin', 'Дмитрий', NULL);

