<?php
/**
 * Менеджер для работы с операциями
 */

require_once __DIR__ . '/../config/database.php';

class OperationManager {
	private $db;
	private $hasMaterialMarks = null;

	private function hasMaterialMarksColumn() {
		if ($this->hasMaterialMarks === null) {
			$row = $this->db->fetchOne(
				"SELECT 1 FROM information_schema.COLUMNS 
				WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'operations' AND COLUMN_NAME = 'material_marks'"
			);
			$this->hasMaterialMarks = !empty($row);
		}
		return $this->hasMaterialMarks;
	}

	public function __construct() {
		$this->db = Database::getInstance();
	}

	public function getAll() {
		$cols = $this->hasMaterialMarksColumn() ? 'o.id, o.number, o.description, o.cost, o.unit_id, o.material_marks' : 'o.id, o.number, o.description, o.cost, o.unit_id';
		$rows = $this->db->fetchAll(
			"SELECT $cols, u.name as unit_name, o.created_at, o.updated_at
			FROM operations o
			LEFT JOIN units u ON o.unit_id = u.id
			ORDER BY CAST(o.number AS UNSIGNED), o.number"
		);
		return $this->ensureMaterialMarks($rows);
	}

	public function getById($id) {
		$cols = $this->hasMaterialMarksColumn() ? 'o.id, o.number, o.description, o.cost, o.unit_id, o.material_marks' : 'o.id, o.number, o.description, o.cost, o.unit_id';
		$row = $this->db->fetchOne(
			"SELECT $cols, u.name as unit_name, o.created_at, o.updated_at
			FROM operations o
			LEFT JOIN units u ON o.unit_id = u.id
			WHERE o.id = ?",
			[$id]
		);
		return $row ? $this->ensureMaterialMarks([$row])[0] : null;
	}

	private function ensureMaterialMarks(array $rows) {
		foreach ($rows as &$r) {
			if (!array_key_exists('material_marks', $r)) {
				$r['material_marks'] = '';
			}
		}
		return $rows;
	}

	public function create($number, $description, $unitId, $cost, $materialMarks = '') {
		if ($this->hasMaterialMarksColumn()) {
			$this->db->execute(
				"INSERT INTO operations (number, description, unit_id, cost, material_marks) VALUES (?, ?, ?, ?, ?)",
				[$number, $description, $unitId, $cost, $materialMarks]
			);
		} else {
			$this->db->execute(
				"INSERT INTO operations (number, description, unit_id, cost) VALUES (?, ?, ?, ?)",
				[$number, $description, $unitId, $cost]
			);
		}
		return $this->db->lastInsertId();
	}

	public function update($id, $number, $description, $unitId, $cost, $materialMarks = '') {
		if ($this->hasMaterialMarksColumn()) {
			return $this->db->execute(
				"UPDATE operations SET number = ?, description = ?, unit_id = ?, cost = ?, material_marks = ? WHERE id = ?",
				[$number, $description, $unitId, $cost, $materialMarks, $id]
			);
		} else {
			return $this->db->execute(
				"UPDATE operations SET number = ?, description = ?, unit_id = ?, cost = ? WHERE id = ?",
				[$number, $description, $unitId, $cost, $id]
			);
		}
	}

	public function delete($id) {
		return $this->db->execute("DELETE FROM operations WHERE id = ?", [$id]);
	}
}
