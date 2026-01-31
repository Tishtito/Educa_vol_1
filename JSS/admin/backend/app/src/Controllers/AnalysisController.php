<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class AnalysisController
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

	public function exams(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('exams', ['exam_id', 'exam_name'], [
				'ORDER' => ['date_created' => 'DESC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[AnalysisController] exams error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode([
				'success' => false,
				'message' => 'Failed to load exams',
			]);
		}
	}

	public function grades(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('classes', ['class_name'], [
				'GROUP' => 'class_name',
				'ORDER' => ['class_name' => 'ASC'],
			]);

			$grades = array_map(function ($row) {
				return $row['class_name'];
			}, $rows ?: []);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $grades,
			]);
		} catch (\Throwable $e) {
			try {
				$rows = $this->db->select('classes', ['grade'], [
					'GROUP' => 'grade',
					'ORDER' => ['grade' => 'ASC'],
				]);

				$grades = array_map(function ($row) {
					return $row['grade'];
				}, $rows ?: []);

				header('Content-Type: application/json');
				echo json_encode([
					'success' => true,
					'data' => $grades,
				]);
			} catch (\Throwable $fallbackError) {
				error_log('[AnalysisController] grades error: ' . $e->getMessage());
				error_log('[AnalysisController] grades fallback error: ' . $fallbackError->getMessage());
				http_response_code(500);
				header('Content-Type: application/json');
				echo json_encode([
					'success' => false,
					'message' => 'Failed to load grades',
				]);
			}
		}
	}
}
