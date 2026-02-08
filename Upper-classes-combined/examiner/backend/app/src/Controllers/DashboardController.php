<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class DashboardController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	public function index(): void
	{
		$this->startSession();

		// Check if user is authenticated
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$examinerId = $_SESSION['id'] ?? null;
		$classAssigned = $_SESSION['class_assigned'] ?? null;
		$examId = $_SESSION['exam_id'] ?? null;

		// Log session data for debugging
		error_log('[DASHBOARD] Session Data: examinerId=' . ($examinerId ? '✓' : '✗') . 
			', classAssigned=' . ($classAssigned ?? 'NULL') . 
			', examId=' . ($examId ? '✓' : '✗'));

		if (!$examinerId || !$classAssigned || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing examiner, class, or exam data', 'debug' => [
				'has_id' => (bool)$examinerId,
				'has_class' => (bool)$classAssigned,
				'has_exam' => (bool)$examId
			]]);
			return;
		}

		try {
			// Get examiner data
			$examiner = $this->db->get('examiners', ['examiner_id', 'name', 'username', 'class_assigned'], ['examiner_id' => $examinerId]);

			// Get mean scores for the selected exam and class
			$means = $this->calculateMeanScores($classAssigned, $examId);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'examiner' => $examiner,
				'class_assigned' => $classAssigned,
				'exam_id' => $examId,
				'title' => ucwords(str_replace('_', ' ', $classAssigned)),
				'means' => $means,
			]);
		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			error_log('[DASHBOARD] Exception: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => 'Internal server error']);
		}
	}

	private function calculateMeanScores(string $class, int $examId): array
	{
		$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Integrated_science', 'AgricNutri', 'SST', 'CRE', 'SciTech'];
		
		// Initialize arrays
		$subject_totals = array_fill_keys($subjects, 0);
		$subject_counts = array_fill_keys($subjects, 0);
		$total_score = 0;
		$total_students = 0;

		// Get all students in this class
		$students = $this->db->select('students', ['student_id'], ['class' => $class]);
		
		if (empty($students)) {
			// Return zeros if no students in class
			$means = [];
			foreach ($subjects as $subject) {
				$means[$subject] = 0;
			}
			$means['total_mean'] = 0;
			return $means;
		}

		$studentIds = array_column($students, 'student_id');

		// Fetch exam results for these students and this specific exam
		$results = $this->db->select('exam_results', '*', [
			'exam_id' => $examId,
			'student_id' => $studentIds
		]);

		// Process results
		if (is_array($results)) {
			foreach ($results as $row) {
				foreach ($subjects as $subject) {
					if (isset($row[$subject]) && $row[$subject] !== null) {
						$subject_totals[$subject] += $row[$subject];
						$subject_counts[$subject]++;
					}
				}
				if (isset($row['total_marks']) && $row['total_marks'] > 0) {
					$total_score += $row['total_marks'];
					$total_students++;
				}
			}
		}

		// Calculate means
		$means = [];
		foreach ($subjects as $subject) {
			$means[$subject] = $subject_counts[$subject] > 0 
				? round($subject_totals[$subject] / $subject_counts[$subject], 2) 
				: 0;
		}
		
		$means['total_mean'] = $total_students > 0 
			? round($total_score / $total_students, 2) 
			: 0;

		return $means;
	}

	private function startSession(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		session_set_cookie_params([
			'path' => $this->basePath() ?: '/',
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		session_start();
	}

	private function basePath(): string
	{
		$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
		$marker = '/backend/public/index.php';
		if ($scriptName !== '' && strlen($scriptName) >= strlen($marker)) {
			if (substr($scriptName, -strlen($marker)) === $marker) {
				$base = substr($scriptName, 0, -strlen($marker));
				return $base !== '' ? $base : '';
			}
		}

		$dir = dirname($scriptName);
		return $dir === '/' ? '' : $dir;
	}
}
