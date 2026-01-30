<?php
/**
 * Класс для генерации PDF с поддержкой кириллицы
 * Использует TCPDF для генерации PDF
 */

class PDFGenerator {
	private $pdf;
	
	public function __construct() {
		// Проверяем наличие TCPDF
		if (!class_exists('TCPDF')) {
			// Пытаемся подключить TCPDF из разных возможных мест
			// Сначала проверяем vendor (если есть локально)
			$possiblePaths = [
				__DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
				__DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php',
				__DIR__ . '/../../backend/vendor/tecnickcom/tcpdf/tcpdf.php',
				__DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php',
				// Потом проверяем возможные места на сервере
				__DIR__ . '/../../tcpdf/tcpdf.php',
				__DIR__ . '/../tcpdf/tcpdf.php',
				__DIR__ . '/tcpdf/tcpdf.php',
				'/usr/share/php/tcpdf/tcpdf.php',
				'/var/www/tcpdf/tcpdf.php',
			];
			
			$found = false;
			foreach ($possiblePaths as $path) {
				$realPath = realpath($path);
				if ($realPath && file_exists($realPath)) {
					require_once $realPath;
					$found = true;
					break;
				}
			}
			
			if (!$found || !class_exists('TCPDF')) {
				// TCPDF не найден - это нормально, будем использовать клиентскую генерацию
				throw new Exception('TCPDF не найден. Будет использована клиентская генерация PDF.');
			}
		}
		
		// Определяем константы TCPDF если они не определены
		if (!defined('PDF_PAGE_ORIENTATION')) {
			define('PDF_PAGE_ORIENTATION', 'P');
		}
		if (!defined('PDF_UNIT')) {
			define('PDF_UNIT', 'mm');
		}
		if (!defined('PDF_PAGE_FORMAT')) {
			define('PDF_PAGE_FORMAT', 'A4');
		}
		
		// Создаем PDF документ
		$this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// Настройки документа
		$this->pdf->SetCreator('Калькулятор себестоимости');
		$this->pdf->SetAuthor('Система калькуляции');
		$this->pdf->SetTitle('Калькуляция себестоимости');
		$this->pdf->SetSubject('Расчет себестоимости изделия');
		
		// Удаляем верхний и нижний колонтитулы по умолчанию
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);
		
		// Устанавливаем шрифт с поддержкой кириллицы
		$this->pdf->SetFont('dejavusans', '', 10);
		
		// Добавляем страницу
		$this->pdf->AddPage();
	}
	
	public function generateCalculationPDF($calculation, $parameters, $operations, $result, $paramLabelMap = []) {
		$productName = $calculation['product_name'] ?? '';
		$materialName = $calculation['material_name'] ?? '';
		$productTypeName = $calculation['product_type_name'] ?? '';
		$createdAt = date('d.m.Y H:i', strtotime($calculation['created_at'] ?? 'now'));
		
		// Заголовок
		$this->pdf->SetFont('dejavusans', 'B', 18);
		$this->pdf->Cell(0, 10, 'Калькуляция себестоимости', 0, 1, 'C');
		$this->pdf->SetFont('dejavusans', '', 10);
		$this->pdf->Cell(0, 5, 'Дата создания: ' . $createdAt, 0, 1, 'C');
		$this->pdf->Ln(5);
		
		// Основная информация
		$this->pdf->SetFont('dejavusans', 'B', 12);
		$this->pdf->Cell(0, 8, 'Основная информация', 0, 1);
		$this->pdf->SetFont('dejavusans', '', 10);
		$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
		$this->pdf->Ln(3);
		
		$this->pdf->Cell(60, 6, 'Название изделия:', 0, 0);
		$this->pdf->Cell(0, 6, $productName, 0, 1);
		$this->pdf->Cell(60, 6, 'Материал:', 0, 0);
		$this->pdf->Cell(0, 6, $materialName, 0, 1);
		$this->pdf->Cell(60, 6, 'Тип изделия:', 0, 0);
		$this->pdf->Cell(0, 6, $productTypeName, 0, 1);
		$this->pdf->Ln(5);
		
		// Параметры
		if (!empty($parameters)) {
			$this->pdf->SetFont('dejavusans', 'B', 12);
			$this->pdf->Cell(0, 8, 'Параметры изделия', 0, 1);
			$this->pdf->SetFont('dejavusans', '', 10);
			$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
			$this->pdf->Ln(3);
			
			// Таблица параметров
			$this->pdf->SetFillColor(245, 245, 245);
			$this->pdf->SetFont('dejavusans', 'B', 10);
			$this->pdf->Cell(90, 6, 'Параметр', 1, 0, 'L', true);
			$this->pdf->Cell(90, 6, 'Значение', 1, 1, 'L', true);
			$this->pdf->SetFont('dejavusans', '', 10);
			
			foreach ($parameters as $key => $value) {
				// Используем лейбл если есть, иначе имя параметра
				$label = $paramLabelMap[$key] ?? $key;
				$this->pdf->Cell(90, 6, $label, 1, 0, 'L');
				$this->pdf->Cell(90, 6, $value, 1, 1, 'L');
			}
			$this->pdf->Ln(5);
		}
		
		// Результаты
		if (!empty($result)) {
			$this->pdf->SetFont('dejavusans', 'B', 12);
			$this->pdf->Cell(0, 8, 'Результаты расчета', 0, 1);
			$this->pdf->SetFont('dejavusans', '', 10);
			$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
			$this->pdf->Ln(3);
			
			$this->pdf->Cell(100, 6, 'Объем заготовки:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['workpiece_volume'] ?? 0, 2, '.', ' ') . ' мм³', 0, 1);
			$this->pdf->Cell(100, 6, 'Объем изделия:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['product_volume'] ?? 0, 2, '.', ' ') . ' мм³', 0, 1);
			$this->pdf->Cell(100, 6, 'Объем отходов:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['waste_volume'] ?? 0, 2, '.', ' ') . ' мм³', 0, 1);
			$this->pdf->Cell(100, 6, 'Масса заготовки:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['workpiece_mass'] ?? 0, 4, '.', ' ') . ' кг', 0, 1);
			$this->pdf->Cell(100, 6, 'Масса изделия:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['product_mass'] ?? 0, 4, '.', ' ') . ' кг', 0, 1);
			$this->pdf->Cell(100, 6, 'Масса отходов:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['waste_mass'] ?? 0, 4, '.', ' ') . ' кг', 0, 1);
			$this->pdf->Cell(100, 6, 'Стоимость материала:', 0, 0);
			$this->pdf->Cell(0, 6, number_format($result['material_cost'] ?? 0, 2, '.', ' ') . ' руб', 0, 1);
			$salaryDisplay = $result['salary_with_quantity_coef'] ?? $result['total_operations_cost'] ?? 0;
			$this->pdf->Cell(100, 6, 'Зарплата (операции):', 0, 0);
			$this->pdf->Cell(0, 6, number_format($salaryDisplay, 2, '.', ' ') . ' руб', 0, 1);
			
			if (!empty($result['coefficients'])) {
				$this->pdf->Ln(3);
				$this->pdf->SetFont('dejavusans', 'B', 10);
				$this->pdf->Cell(0, 6, 'Коэффициенты (налоги):', 0, 1);
				$this->pdf->SetFont('dejavusans', '', 10);
				
				foreach ($result['coefficients'] as $coef) {
					$coefText = $coef['name'] . ' (' . $coef['value'] . '%):';
					$this->pdf->Cell(100, 6, $coefText, 0, 0);
					$this->pdf->Cell(0, 6, number_format($coef['amount'] ?? 0, 2, '.', ' ') . ' руб', 0, 1);
				}
				$this->pdf->Cell(100, 6, 'Итого коэффициенты:', 0, 0);
				$this->pdf->Cell(0, 6, number_format($result['coefficients_cost'] ?? 0, 2, '.', ' ') . ' руб', 0, 1);
			}
			
			if (isset($result['ohr_cost'])) {
				$this->pdf->Cell(100, 6, 'ОХР (коэф. массы ' . ($result['mass_coefficient'] ?? '') . '):', 0, 0);
				$this->pdf->Cell(0, 6, number_format($result['ohr_cost'], 2, '.', ' ') . ' руб', 0, 1);
			}
			
			$this->pdf->Ln(5);
			$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
			$this->pdf->Ln(5);
			$this->pdf->SetFont('dejavusans', 'B', 14);
			$this->pdf->Cell(0, 8, 'Общая себестоимость: ' . number_format($result['total_cost_without_packaging'] ?? 0, 2, '.', ' ') . ' руб', 0, 1);
			if (isset($result['total_cost_with_margin'])) {
				$this->pdf->SetFont('dejavusans', 'B', 14);
				$this->pdf->Cell(0, 8, 'Итого с маржой 40%: ' . number_format($result['total_cost_with_margin'], 2, '.', ' ') . ' руб', 0, 1);
			}
			$this->pdf->SetFont('dejavusans', '', 10);
		}
		
		// Операции
		if (!empty($operations)) {
			$this->pdf->Ln(5);
			$this->pdf->SetFont('dejavusans', 'B', 12);
			$this->pdf->Cell(0, 8, 'Операции', 0, 1);
			$this->pdf->SetFont('dejavusans', '', 10);
			$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
			$this->pdf->Ln(3);
			
			// Таблица операций
			$this->pdf->SetFillColor(245, 245, 245);
			$this->pdf->SetFont('dejavusans', 'B', 10);
			$this->pdf->Cell(30, 6, 'Номер', 1, 0, 'L', true);
			$this->pdf->Cell(80, 6, 'Описание', 1, 0, 'L', true);
			$this->pdf->Cell(35, 6, 'Коэф. сложности', 1, 0, 'C', true);
			$this->pdf->Cell(35, 6, 'Стоимость', 1, 1, 'R', true);
			$this->pdf->SetFont('dejavusans', '', 10);
			
			foreach ($operations as $op) {
				// Вычисляем необходимую высоту для описания
				$description = $op['operation_description'] ?? '';
				$nb = $this->pdf->getNumLines($description, 80);
				$rowHeight = $nb * 6; // 6 - базовая высота строки
				
				// Запоминаем позицию начала строки
				$startY = $this->pdf->GetY();
				$startX = $this->pdf->GetX();
				
				// Рисуем ячейку с номером
				$this->pdf->MultiCell(30, $rowHeight, $op['operation_number'] ?? '', 1, 'L', false, 0, $startX, $startY, true, 0, false, true, $rowHeight, 'M');
				
				// Рисуем ячейку с описанием (с переносом слов)
				$this->pdf->MultiCell(80, $rowHeight, $description, 1, 'L', false, 0, $startX + 30, $startY, true, 0, false, true, $rowHeight, 'T');
				
				// Рисуем ячейку с коэффициентом
				$this->pdf->MultiCell(35, $rowHeight, number_format($op['complexity_coefficient'] ?? 1, 2, '.', ' '), 1, 'C', false, 0, $startX + 110, $startY, true, 0, false, true, $rowHeight, 'M');
				
				// Рисуем ячейку со стоимостью
				$this->pdf->MultiCell(35, $rowHeight, number_format($op['total_cost'] ?? 0, 2, '.', ' ') . ' руб', 1, 'R', false, 1, $startX + 145, $startY, true, 0, false, true, $rowHeight, 'M');
			}
		}
	}
	
	public function output($filename = 'calculation.pdf') {
		$this->pdf->Output($filename, 'D');
	}
	
	public function getPDFContent() {
		return $this->pdf->Output('', 'S');
	}
}
