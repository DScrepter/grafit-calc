<?php
/**
 * Менеджер для работы с типами изделий
 */

require_once __DIR__ . '/../config/database.php';

class ProductTypeManager {
	private $db;

	public function __construct() {
		$this->db = Database::getInstance();
	}

	public function getAll() {
		$types = $this->db->fetchAll(
			"SELECT id, name, description, volume_formula, waste_formula, created_at, updated_at
			FROM product_types ORDER BY name"
		);

		// Загружаем параметры для каждого типа
		foreach ($types as &$type) {
			$type['parameters'] = $this->getParameters($type['id']);
		}

		return $types;
	}

	public function getById($id) {
		$type = $this->db->fetchOne(
			"SELECT id, name, description, volume_formula, waste_formula, created_at, updated_at
			FROM product_types WHERE id = ?",
			[$id]
		);

		if ($type) {
			$type['parameters'] = $this->getParameters($id);
		}

		return $type;
	}

	private function getParameters($productTypeId) {
		return $this->db->fetchAll(
			"SELECT id, name, label, unit, required, default_value, sequence
			FROM product_type_parameters
			WHERE product_type_id = ?
			ORDER BY sequence",
			[$productTypeId]
		);
	}

	public function calculateVolume($productTypeId, $parameters) {
		$type = $this->getById($productTypeId);
		if (!$type) {
			throw new Exception("Тип изделия не найден");
		}

		// Проверяем обязательные параметры
		foreach ($type['parameters'] as $param) {
			if ($param['required'] && !isset($parameters[$param['name']])) {
				throw new Exception("Отсутствует обязательный параметр: " . $param['label']);
			}
		}

		// Вычисляем объем по формуле
		$formula = $type['volume_formula'];
		
		// Создаем безопасное окружение для eval
		$safeParams = [];
		foreach ($parameters as $key => $value) {
			$safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
			$safeParams[$safeKey] = (float)$value;
		}
		
		try {
			// Добавляем $ к переменным в формуле
			$formulaWithVars = $formula;
			foreach ($safeParams as $varName => $varValue) {
				// Заменяем имя переменной на $имя_переменной в формуле
				$formulaWithVars = preg_replace('/\b' . preg_quote($varName, '/') . '\b/', '$' . $varName, $formulaWithVars);
			}
			
			// Создаем код с определением переменных
			$varDefinitions = [];
			foreach ($safeParams as $varName => $varValue) {
				$varDefinitions[] = "\${$varName} = " . (float)$varValue . ";";
			}
			$evalCode = implode("\n", $varDefinitions) . "\nreturn " . $formulaWithVars . ";";
			
			// Используем eval для вычисления
			$volume = @eval($evalCode);
			if ($volume === false || $volume === null) {
				throw new Exception("Не удалось вычислить формулу");
			}
			return (float)$volume;
		} catch (ParseError $e) {
			throw new Exception("Ошибка синтаксиса формулы: " . $e->getMessage());
		} catch (Error $e) {
			throw new Exception("Ошибка вычисления формулы: " . $e->getMessage());
		} catch (Exception $e) {
			throw new Exception("Ошибка вычисления объема: " . $e->getMessage());
		}
	}

	public function calculateWasteVolume($productTypeId, $parameters) {
		$type = $this->getById($productTypeId);
		if (!$type) {
			throw new Exception("Тип изделия не найден");
		}

		$formula = $type['waste_formula'];
		
		// Создаем безопасное окружение для eval
		$safeParams = [];
		foreach ($parameters as $key => $value) {
			$safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
			$safeParams[$safeKey] = (float)$value;
		}
		
		try {
			// Добавляем $ к переменным в формуле
			$formulaWithVars = $formula;
			foreach ($safeParams as $varName => $varValue) {
				// Заменяем имя переменной на $имя_переменной в формуле
				$formulaWithVars = preg_replace('/\b' . preg_quote($varName, '/') . '\b/', '$' . $varName, $formulaWithVars);
			}
			
			// Создаем код с определением переменных
			$varDefinitions = [];
			foreach ($safeParams as $varName => $varValue) {
				$varDefinitions[] = "\${$varName} = " . (float)$varValue . ";";
			}
			$evalCode = implode("\n", $varDefinitions) . "\nreturn " . $formulaWithVars . ";";
			
			// Используем eval для вычисления
			$wasteVolume = @eval($evalCode);
			if ($wasteVolume === false || $wasteVolume === null) {
				throw new Exception("Не удалось вычислить формулу");
			}
			return (float)$wasteVolume;
		} catch (ParseError $e) {
			throw new Exception("Ошибка синтаксиса формулы: " . $e->getMessage());
		} catch (Error $e) {
			throw new Exception("Ошибка вычисления формулы: " . $e->getMessage() . " (проверьте, что все переменные в формуле определены)");
		} catch (Exception $e) {
			throw new Exception("Ошибка вычисления объема отходов: " . $e->getMessage());
		}
	}

	public function create($name, $description, $volumeFormula, $wasteFormula, $parameters = []) {
		$this->db->beginTransaction();
		try {
			// Создаем тип изделия
			$this->db->execute(
				"INSERT INTO product_types (name, description, volume_formula, waste_formula) VALUES (?, ?, ?, ?)",
				[$name, $description, $volumeFormula, $wasteFormula]
			);
			$productTypeId = $this->db->lastInsertId();

			// Добавляем параметры
			foreach ($parameters as $index => $param) {
				$this->db->execute(
					"INSERT INTO product_type_parameters (product_type_id, name, label, unit, required, default_value, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)",
					[
						$productTypeId,
						$param['name'],
						$param['label'],
						$param['unit'],
						$param['required'] ? 1 : 0,
						$param['default_value'] ?? null,
						$param['sequence'] ?? $index
					]
				);
			}

			$this->db->commit();
			return $productTypeId;
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	public function update($id, $name, $description, $volumeFormula, $wasteFormula, $parameters = []) {
		$this->db->beginTransaction();
		try {
			// Обновляем тип изделия
			$result = $this->db->execute(
				"UPDATE product_types SET name = ?, description = ?, volume_formula = ?, waste_formula = ? WHERE id = ?",
				[$name, $description, $volumeFormula, $wasteFormula, $id]
			);

			if (!$result) {
				$this->db->rollBack();
				return false;
			}

			// Удаляем старые параметры
			$this->db->execute(
				"DELETE FROM product_type_parameters WHERE product_type_id = ?",
				[$id]
			);

			// Добавляем новые параметры
			foreach ($parameters as $index => $param) {
				$this->db->execute(
					"INSERT INTO product_type_parameters (product_type_id, name, label, unit, required, default_value, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)",
					[
						$id,
						$param['name'],
						$param['label'],
						$param['unit'],
						$param['required'] ? 1 : 0,
						$param['default_value'] ?? null,
						$param['sequence'] ?? $index
					]
				);
			}

			$this->db->commit();
			return true;
		} catch (Exception $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	public function delete($id) {
		// Каскадное удаление параметров настроено в БД
		return $this->db->execute("DELETE FROM product_types WHERE id = ?", [$id]);
	}
}
