<?php
/**
 * Менеджер для работы с операциями
 */

require_once __DIR__ . '/../config/database.php';

class OperationManager {
	private $db;

	public function __construct() {
		$this->db = Database::getInstance();
	}

	public function getAll() {
		return $this->db->fetchAll(
			"SELECT o.id, o.number, o.description, o.cost, o.unit_id, u.name as unit_name,
				o.created_at, o.updated_at
			FROM operations o
			LEFT JOIN units u ON o.unit_id = u.id
			ORDER BY o.number"
		);
	}

	public function getById($id) {
		return $this->db->fetchOne(
			"SELECT o.id, o.number, o.description, o.cost, o.unit_id, u.name as unit_name,
				o.created_at, o.updated_at
			FROM operations o
			LEFT JOIN units u ON o.unit_id = u.id
			WHERE o.id = ?",
			[$id]
		);
	}

	public function create($number, $description, $unitId, $cost) {
		$this->db->execute(
			"INSERT INTO operations (number, description, unit_id, cost) VALUES (?, ?, ?, ?)",
			[$number, $description, $unitId, $cost]
		);
		return $this->db->lastInsertId();
	}

	public function update($id, $number, $description, $unitId, $cost) {
		return $this->db->execute(
			"UPDATE operations SET number = ?, description = ?, unit_id = ?, cost = ? WHERE id = ?",
			[$number, $description, $unitId, $cost, $id]
		);
	}

	public function delete($id) {
		return $this->db->execute("DELETE FROM operations WHERE id = ?", [$id]);
	}
}
