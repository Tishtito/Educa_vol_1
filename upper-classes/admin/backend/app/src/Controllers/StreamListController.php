<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class StreamListController
{
	private Medoo $db;
	private const TOKEN_SECRET = 'educa-jss-token-v1';

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

	private function tokenKey(): string
	{
		return hash('sha256', session_id() . '|' . self::TOKEN_SECRET);
	}

	private function makeToken(string $payload): string
	{
		return hash_hmac('sha256', $payload, $this->tokenKey());
	}

	private function isValidToken(string $payload, ?string $token): bool
	{
		return $token !== null && $token !== '' && hash_equals($this->makeToken($payload), $token);
	}

	public function list(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$grade = isset($_GET['grade']) ? trim((string)$_GET['grade']) : '';
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;

		if ($examId <= 0 || $grade === '') {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing parameters']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId . '|grade:' . $grade, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		$examName = $this->db->get('exams', 'exam_name', ['exam_id' => $examId]);
		if (!$examName) {
			http_response_code(404);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Exam not found']);
			return;
		}

		$classes = [];
		try {
			$classNames = $this->db->select('classes', ['class_name'], [
				'grade' => $grade,
				'ORDER' => ['class_name' => 'ASC'],
			]);

			$classes = array_map(function ($row) {
				return $row['class_name'];
			}, $classNames ?: []);
		} catch (\Throwable $e) {
			try {
				$classNames = $this->db->select('classes', ['class_name'], [
					'class_name[~]' => $grade . '%',
					'ORDER' => ['class_name' => 'ASC'],
				]);

				$classes = array_map(function ($row) {
					return $row['class_name'];
				}, $classNames ?: []);
			} catch (\Throwable $fallbackError) {
				error_log('[StreamListController] classes error: ' . $e->getMessage());
				error_log('[StreamListController] classes fallback error: ' . $fallbackError->getMessage());
				http_response_code(500);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Failed to load classes']);
				return;
			}
		}

		if (empty($classes)) {
			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'exam_name' => $examName,
					'grade' => $grade,
					'subjects' => [],
					'students' => [],
					'mean_scores' => [],
					'total_mean' => 0,
					'performance_levels' => [],
					'classes' => [],
				],
			]);
			return;
		}

		$subjects = ['English', 'Kiswahili', 'Math', 'Creative', 'SciTech', 'AgricNutri', 'SST', 'CRE'];

		$paramKeys = [];
		$params = [':exam_id' => $examId];
		foreach ($classes as $index => $className) {
			$key = ':class_' . $index;
			$paramKeys[] = $key;
			$params[$key] = $className;
		}
		$placeholders = implode(',', $paramKeys);
		$sql = "
			SELECT 
				students.student_id AS student_id, 
				students.name AS Name, 
				students.class AS Class, 
				exam_results.English, 
				exam_results.Math, 
				exam_results.Kiswahili, 
				exam_results.Creative, 
				exam_results.SciTech,
				exam_results.AgricNutri,
				exam_results.SST,
				exam_results.CRE,
				(
					exam_results.English + 
					exam_results.Math + 
					exam_results.Kiswahili + 
					exam_results.Creative + 
					exam_results.SciTech +
					exam_results.AgricNutri +
					exam_results.SST +
					exam_results.CRE
				) AS Total_marks
			FROM 
				students
			LEFT JOIN 
				exam_results 
			ON 
				students.student_id = exam_results.student_id AND exam_results.exam_id = :exam_id
			WHERE 
				students.class IN ($placeholders)
			ORDER BY 
				Total_marks DESC
		";

		$stmt = $this->db->query($sql, $params);
		$students = $stmt ? $stmt->fetchAll() : [];

		$validStudents = 0;
		$subjectTotals = array_fill_keys($subjects, 0);
		$totalValidMarks = 0;

		foreach ($students as $index => &$student) {
			$rank = $index + 1;
			$student['rank'] = $rank;
			$totalMarks = isset($student['Total_marks']) ? (int)$student['Total_marks'] : 0;
			$student['Total_marks'] = $totalMarks;

			$allSubjectsFilled = true;
			foreach ($subjectTotals as $subject => &$total) {
				if ($student[$subject] === null) {
					$allSubjectsFilled = false;
				} else {
					$total += (float)$student[$subject];
				}
			}
			unset($total);

			if ($allSubjectsFilled) {
				$validStudents++;
				$totalValidMarks += $totalMarks;
			}

			$this->db->update('exam_results', [
				'total_marks' => $totalMarks,
				'stream_position' => $rank,
			], [
				'student_id' => $student['student_id'],
				'exam_id' => $examId,
			]);
		}
		unset($student);

		$meanScores = [];
		if ($validStudents > 0) {
			foreach ($subjectTotals as $subject => $total) {
				$meanScores[$subject] = round($total / $validStudents, 2);
			}
			$meanTotalMarks = round($totalValidMarks / $validStudents, 2);
		} else {
			foreach ($subjectTotals as $subject => $total) {
				$meanScores[$subject] = 0;
			}
			$meanTotalMarks = 0;
		}

		$plRows = $this->db->select('point_boundaries', ['min_marks', 'max_marks', 'ab']);

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => [
				'exam_name' => $examName,
				'grade' => $grade,
				'subjects' => $subjects,
				'students' => $students,
				'mean_scores' => $meanScores,
				'total_mean' => $meanTotalMarks,
				'performance_levels' => $plRows,
			],
		]);
	}
}
