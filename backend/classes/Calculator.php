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

	public function calculate($productName, $materialId, $productTypeId, $parameters, $operations = []) {
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

		// Вычисляем объемы
		$productVolume = $this->productTypeManager->calculateVolume($productTypeId, $parameters);
		$wasteVolume = $this->productTypeManager->calculateWasteVolume($productTypeId, $parameters);
		$workpieceVolume = $productVolume + $wasteVolume;

		// Вычисляем массы
		// Плотность в г/см³, объем в мм³
		// Масса (кг) = Объем (мм³) × Плотность (г/см³) / 1_000_000
		$density = (float)$material['density'];
		$conversionFactor = 1000000;
		
		$workpieceMass = ($workpieceVolume * $density) / $conversionFactor;
		$productMass = ($productVolume * $density) / $conversionFactor;
		$wasteMass = ($wasteVolume * $density) / $conversionFactor;

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

		// Вычисляем коэффициенты
		$coefficients = $this->db->fetchAll("SELECT * FROM coefficients ORDER BY name");
		$calculationCoefficients = [];
		$coefficientsCost = 0.0;

		foreach ($coefficients as $coefficient) {
			$coefficientAmount = $totalOperationsCost * ((float)$coefficient['value'] / 100.0);
			$coefficientsCost += $coefficientAmount;

			$calculationCoefficients[] = [
				'name' => $coefficient['name'],
				'value' => (float)$coefficient['value'],
				'amount' => $coefficientAmount
			];
		}

		// Вычисляем общую себестоимость без упаковки
		$totalCostWithoutPackaging = $materialCost + $totalOperationsCost + $coefficientsCost;

		return [
			'product_name' => $productName,
			'material_name' => $material['mark'],
			'product_type_name' => $productType['name'],
			'parameters' => $parameters,
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
			'coefficients' => $calculationCoefficients,
			'coefficients_cost' => $coefficientsCost,
			'total_cost_without_packaging' => $totalCostWithoutPackaging
		];
	}
}
