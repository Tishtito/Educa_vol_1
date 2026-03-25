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

		$subject = $_GET['subject'] ?? 'Math';
		$validSubjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
		
		if (!in_array($subject, $validSubjects)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid subject']);
			return;
		}

		try {
			$rows = $this->db->select('point_boundaries', ['id', 'subject', 'grade', 'min_marks', 'max_marks', 'pl', 'ab'], [
				'subject' => $subject,
				'ORDER' => ['min_marks' => 'ASC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'subject' => $subject,
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
		$newGrades = $payload['new_grades'] ?? [];

		// Allow either existing grades to update or new grades to insert
		if ((!is_array($grades)) || (empty($grades) && empty($newGrades))) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'No grades provided']);
			return;
		}

		$subject = $payload['subject'] ?? 'Math';
		$validSubjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
		
		if (!in_array($subject, $validSubjects)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid subject']);
			return;
		}

		try {
			foreach ($grades as $gradeData) {
				$id = isset($gradeData['id']) ? (int)$gradeData['id'] : 0;
				$grade = isset($gradeData['grade']) ? trim((string)$gradeData['grade']) : '';
				$minMarks = isset($gradeData['min_marks']) ? (int)$gradeData['min_marks'] : null;
				$maxMarks = isset($gradeData['max_marks']) ? (int)$gradeData['max_marks'] : null;
				$pl = isset($gradeData['pl']) ? trim((string)$gradeData['pl']) : '';
				$ab = isset($gradeData['ab']) ? trim((string)$gradeData['ab']) : '';

				if ($id <= 0 || $grade === '' || $minMarks === null || $maxMarks === null) {
					continue;
				}

				$updateData = [
					'subject' => $subject,
					'grade' => $grade,
					'min_marks' => $minMarks,
					'max_marks' => $maxMarks,
				];

				// Include pl and ab if provided
				if ($pl !== '') {
					$updateData['pl'] = $pl;
				}
				if ($ab !== '') {
					$updateData['ab'] = $ab;
				}

				$this->db->update('point_boundaries', $updateData, ['id' => $id]);
			}

			// Insert new grades
			if (is_array($newGrades) && !empty($newGrades)) {
				foreach ($newGrades as $newGrade) {
					$grade = isset($newGrade['grade']) ? trim((string)$newGrade['grade']) : '';
					$minMarks = isset($newGrade['min_marks']) ? (int)$newGrade['min_marks'] : null;
					$maxMarks = isset($newGrade['max_marks']) ? (int)$newGrade['max_marks'] : null;
					$pl = isset($newGrade['pl']) ? trim((string)$newGrade['pl']) : '';
					$ab = isset($newGrade['ab']) ? trim((string)$newGrade['ab']) : '';

					if ($grade === '' || $minMarks === null || $maxMarks === null) {
						continue;
					}

					$insertData = [
						'subject' => $subject,
						'grade' => $grade,
						'min_marks' => $minMarks,
						'max_marks' => $maxMarks,
					];

					// Include pl and ab if provided
					if ($pl !== '') {
						$insertData['pl'] = $pl;
					}
					if ($ab !== '') {
						$insertData['ab'] = $ab;
					}

					$this->db->insert('point_boundaries', $insertData);
				}
			}

			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'subject' => $subject]);
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
