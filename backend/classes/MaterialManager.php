<?php
/**
 * Менеджер для работы с материалами
 */

require_once __DIR__ . '/../config/database.php';

class MaterialManager {
	private $db;

	public function __construct() {
		$this->db = Database::getInstance();
	}

	public function getAll() {
		return $this->db->fetchAll(
			"SELECT id, mark, density, price, created_at, updated_at FROM materials ORDER BY mark"
		);
	}

	public function getById($id) {
		return $this->db->fetchOne(
			"SELECT id, mark, density, price, created_at, updated_at FROM materials WHERE id = ?",
			[$id]
		);
	}

	public function create($mark, $density, $price) {
		$this->db->execute(
			"INSERT INTO materials (mark, density, price) VALUES (?, ?, ?)",
			[$mark, $density, $price]
		);
		return $this->db->lastInsertId();
	}

	public function update($id, $mark, $density, $price) {
		return $this->db->execute(
			"UPDATE materials SET mark = ?, density = ?, price = ? WHERE id = ?",
			[$mark, $density, $price, $id]
		);
	}

	public function delete($id) {
		return $this->db->execute("DELETE FROM materials WHERE id = ?", [$id]);
	}
}
