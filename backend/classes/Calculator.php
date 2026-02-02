<?php
/**
 * Калькулятор себестоимости изделий
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MaterialManager.php';
require_once __DIR__ . '/OperationManager.php';
require_once __DIR__ . '/ProductTypeManager.php';
require_once __DIR__ . '/Logger.php';

class Calculator {
	private $db;
	private $materialManager;
	private $operationManager;
	private $productTypeManager;
	private $logger;

	public function __construct() {
		$this->db = Database::getInstance();
		$this->materialManager = new MaterialManager();
		$this->operationManager = new OperationManager();
		$this->productTypeManager = new ProductTypeManager();
		$this->logger = Logger::getInstance();
	}

	/**
	 * Загружает фиксированные коэффициенты по ключам name.
	 * @return array<string, float> ключ => value
	 */
	private function getFixedCoefficients() {
		$rows = $this->db->fetchAll(
			"SELECT name, value FROM coefficients WHERE name IN ('N', 'Kz_порог', 'Kz', 'K', 'M')"
		);
		$map = [];
		foreach ($rows as $row) {
			$map[$row['name']] = (float)$row['value'];
		}
		return $map;
	}

	public function calculate($productName, $materialId, $productTypeId, $parameters, $operations = [], $quantity = 5, $workpieceMassOverride = null) {
		// Получаем материал
		$material = $this->materialManager->getById($materialId);
		if (!$material) {
			$error = new Exception("Материал не найден");
			$this->logger->exception($error, [
				'material_id' => $materialId,
				'product_name' => $productName
			]);
			throw $error;
		}

		// Получаем тип изделия
		$productType = $this->productTypeManager->getById($productTypeId);
		if (!$productType) {
			$error = new Exception("Тип изделия не найден");
			$this->logger->exception($error, [
				'product_type_id' => $productTypeId,
				'product_name' => $productName
			]);
			throw $error;
		}

		// Вычисляем объемы (нужны для пропорций и для расчёта массы по габаритам)
		$productVolume = $this->productTypeManager->calculateVolume($productTypeId, $parameters);
		$wasteVolume = $this->productTypeManager->calculateWasteVolume($productTypeId, $parameters);
		$workpieceVolume = $productVolume + $wasteVolume;

		$density = (float)$material['density'];
		$conversionFactor = 1000000;

		if ($workpieceMassOverride !== null && $workpieceMassOverride > 0) {
			// Ручная масса заготовки (кг)
			$workpieceMass = (float)$workpieceMassOverride;
			$volumeRatio = $workpieceVolume > 0 ? ($productVolume / $workpieceVolume) : 1.0;
			$productMass = $workpieceMass * $volumeRatio;
			$wasteMass = $workpieceMass - $productMass;
		} else {
			// Масса по габаритам: Масса (кг) = Объем (мм³) × Плотность (г/см³) / 1_000_000
			$workpieceMass = ($workpieceVolume * $density) / $conversionFactor;
			$productMass = ($productVolume * $density) / $conversionFactor;
			$wasteMass = ($wasteVolume * $density) / $conversionFactor;
		}

		// Вычисляем стоимость материала
		$pricePerKg = (float)$material['price'];
		$materialCost = $workpieceMass * $pricePerKg;

		// Обрабатываем операции
		$calculationOperations = [];
		$totalOperationsCost = 0.0;

		foreach ($operations as $opData) {
			$operationId = $opData['operation_id'];
			$complexityCoefficient = isset($opData['complexity_coefficient']) ? (float)$opData['complexity_coefficient'] : 1.0;

			$operation = $this->operationManager->getById($operationId);
			if ($operation) {
				$operationCost = (float)$operation['cost'];
				$totalCost = $operationCost * $complexityCoefficient;

				$calculationOperations[] = [
					'operation_id' => $operationId,
					'operation_number' => $operation['number'],
					'operation_description' => $operation['description'],
					'operation_cost' => $operationCost,
					'complexity_coefficient' => $complexityCoefficient,
					'total_cost' => $totalCost
				];

				$totalOperationsCost += $totalCost;
			}
		}

		$quantity = (int)$quantity;
		$coef = $this->getFixedCoefficients();
		$kzThreshold = isset($coef['Kz_порог']) ? (float)$coef['Kz_порог'] : 5.0;
		$kz = isset($coef['Kz']) ? (float)$coef['Kz'] : 1.5;

		// ЗП с учётом малого заказа: если quantity < порог → ЗП = сумма операций × Kz
		$quantityCoefficient = ($quantity < $kzThreshold) ? $kz : 1.0;
		$salaryWithCoeff = $totalOperationsCost * $quantityCoefficient;

		// Налог N (%): одна запись
		$nPercent = isset($coef['N']) ? (float)$coef['N'] : 30.0;
		$coefficientsCost = $salaryWithCoeff * ($nPercent / 100.0);
		$calculationCoefficients = [
			['name' => 'Налоги на зарплату', 'value' => $nPercent, 'amount' => $coefficientsCost]
		];

		// ОХР = ((Масса изделия × K) + (Сумма стоимости операций + Сумма стоимости операций × K)) / 2
		$kOhr = isset($coef['K']) ? (float)$coef['K'] : 12.0;
		$ohrCost = (($productMass * $kOhr) + ($totalOperationsCost + $coefficientsCost)) / 2.0;

		// Себестоимость = Материалы + ЗП + Налог + ОХР
		$totalCostWithoutPackaging = $materialCost + $salaryWithCoeff + $coefficientsCost + $ohrCost;

		// Стоимость изделия = Себестоимость × (1 + M/100), M в процентах
		$mPercent = isset($coef['M']) ? (float)$coef['M'] : 40.0;
		$totalCostWithMargin = $totalCostWithoutPackaging * (1.0 + $mPercent / 100.0);

		return [
			'product_name' => $productName,
			'material_name' => $material['mark'],
			'product_type_name' => $productType['name'],
			'parameters' => $parameters,
			'quantity' => $quantity,
			'quantity_coefficient' => $quantityCoefficient,
			'workpiece_volume' => $workpieceVolume,
			'product_volume' => $productVolume,
			'waste_volume' => $wasteVolume,
			'workpiece_mass' => $workpieceMass,
			'product_mass' => $productMass,
			'waste_mass' => $wasteMass,
			'material_price_per_kg' => $pricePerKg,
			'material_cost' => $materialCost,
			'operations' => $calculationOperations,
			'total_operations_cost' => $totalOperationsCost,
			'salary_with_quantity_coef' => $salaryWithCoeff,
			'coefficients' => $calculationCoefficients,
			'coefficients_cost' => $coefficientsCost,
			'ohr_coefficient' => $kOhr,
			'ohr_cost' => $ohrCost,
			'margin_percent' => $mPercent,
			'total_cost_without_packaging' => $totalCostWithoutPackaging,
			'total_cost_with_margin' => $totalCostWithMargin
		];
	}
}
