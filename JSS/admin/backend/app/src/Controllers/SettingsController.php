<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SettingsController
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

	public function pointBoundaries(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('point_boundaries', ['id', 'grade', 'min_marks', 'max_marks'], [
				'ORDER' => ['min_marks' => 'ASC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[SettingsController] pointBoundaries error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load grade boundaries']);
		}
	}

	public function updatePointBoundaries(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$payload = $_POST;
		if (empty($payload)) {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw ?: '', true) ?: [];
		}

		$grades = $payload['grades'] ?? [];
		if (!is_array($grades) || $grades === []) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'No grades provided']);
			return;
		}

		try {
			foreach ($grades as $gradeData) {
				$id = isset($gradeData['id']) ? (int)$gradeData['id'] : 0;
				$grade = isset($gradeData['grade']) ? trim((string)$gradeData['grade']) : '';
				$minMarks = isset($gradeData['min_marks']) ? (int)$gradeData['min_marks'] : null;
				$maxMarks = isset($gradeData['max_marks']) ? (int)$gradeData['max_marks'] : null;

				if ($id <= 0 || $grade === '' || $minMarks === null || $maxMarks === null) {
					continue;
				}

				$this->db->update('point_boundaries', [
					'grade' => $grade,
					'min_marks' => $minMarks,
					'max_marks' => $maxMarks,
				], ['id' => $id]);
			}

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[SettingsController] updatePointBoundaries error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update grade boundaries']);
		}
	}

	public function createExam(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$payload = $_POST;
		if (empty($payload)) {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw ?: '', true) ?: [];
		}

		$name = isset($payload['name']) ? trim((string)$payload['name']) : '';
		$examType = isset($payload['exam_type']) ? trim((string)$payload['exam_type']) : '';
		$term = isset($payload['term']) ? trim((string)$payload['term']) : '';

		$allowedTypes = ['Opener', 'Mid-Term', 'End-Term'];
		$allowedTerms = ['Term 1', 'Term 2', 'Term 3'];

		if ($name === '' || !in_array($examType, $allowedTypes, true) || !in_array($term, $allowedTerms, true)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid exam data']);
			return;
		}

		try {
			$this->db->insert('exams', [
				'exam_name' => $name,
				'exam_type' => $examType,
				'term' => $term,
			]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[SettingsController] createExam error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to create exam']);
		}
	}

}
