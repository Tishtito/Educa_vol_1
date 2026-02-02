<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ClassesController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	private function requireAuth(): bool
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return false;
		}

		return true;
	}

	public function list(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('classes', ['class_id', 'class_name', 'year'], [
				'ORDER' => ['class_name' => 'ASC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[ClassesController] list error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load classes']);
		}
	}

	public function create(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$payload = $_POST;
		if (empty($payload)) {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw ?: '', true) ?: [];
		}

		$name = isset($payload['class_name']) ? trim((string)$payload['class_name']) : '';
		$grade = isset($payload['grade']) ? (int)$payload['grade'] : 0;

		if ($name === '' || $grade <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid class data']);
			return;
		}

		try {
			$this->db->insert('classes', [
				'class_name' => $name,
				'grade' => $grade,
			]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ClassesController] create error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to create class']);
		}
	}

	public function delete(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$payload = $_POST;
		if (empty($payload)) {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw ?: '', true) ?: [];
		}

		$classId = isset($payload['class_id']) ? (int)$payload['class_id'] : 0;
		if ($classId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid class id']);
			return;
		}

		try {
			$this->db->delete('classes', ['class_id' => $classId]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ClassesController] delete error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to delete class']);
		}
	}
}
