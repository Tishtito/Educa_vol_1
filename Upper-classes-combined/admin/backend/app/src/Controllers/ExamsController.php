<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ExamsController
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
			$rows = $this->db->select('exams', ['exam_id', 'exam_name', 'exam_type', 'term', 'status', 'date_created'], [
				'ORDER' => ['date_created' => 'DESC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[ExamsController] list error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load exams']);
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

		$examId = isset($payload['exam_id']) ? (int)$payload['exam_id'] : 0;
		if ($examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid exam id']);
			return;
		}

		try {
			$this->db->delete('exams', ['exam_id' => $examId]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExamsController] delete error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to delete exam']);
		}
	}

	public function update(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$payload = $_POST;
		if (empty($payload)) {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw ?: '', true) ?: [];
		}

		$examId = isset($payload['exam_id']) ? (int)$payload['exam_id'] : 0;
		$status = isset($payload['status']) ? (string)$payload['status'] : '';

		if ($examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid exam id']);
			return;
		}

		$validStatuses = ['Scheduled', 'Completed', 'Cancelled'];
		if (empty($status) || !in_array($status, $validStatuses)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)]);
			return;
		}

		try {
			$this->db->update('exams', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], ['exam_id' => $examId]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'message' => 'Exam status updated successfully']);
		} catch (\Throwable $e) {
			error_log('[ExamsController] update error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update exam']);
		}
	}
}
