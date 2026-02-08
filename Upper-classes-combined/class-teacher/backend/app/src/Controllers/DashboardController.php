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

		$teacherId = $_SESSION['id'] ?? null;
		$classAssigned = $_SESSION['class_assigned'] ?? null;
		$examId = $_SESSION['exam_id'] ?? null;

		if (!$teacherId || !$classAssigned) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing teacher or class data']);
			return;
		}

		// Get teacher data
		$teacher = $this->db->get('class_teachers', ['id', 'name', 'username', 'class_assigned'], ['id' => $teacherId]);

		// Get mean scores for all subjects (use session exam_id if available)
		$means = $examId ? $this->calculateMeanScores($classAssigned, $examId) : $this->calculateMeanScores($classAssigned);

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'teacher' => $teacher,
			'class_assigned' => $classAssigned,
			'exam_id' => $examId,
			'title' => ucwords(str_replace('_', ' ', $classAssigned)),
			'means' => $means,
		]);
	}

	private function calculateMeanScores(string $class, ?int $examId = null): array
	{
		$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'SciTech', 'AgricNutri', 'SST', 'CRE', 'Integrated_science'];
		
		// Initialize arrays
		$subject_totals = array_fill_keys($subjects, 0);
		$subject_counts = array_fill_keys($subjects, 0);
		$total_score = 0;
		$total_students = 0;

		// Use provided examId or get the most recent exam
		if (!$examId) {
			$latestExam = $this->db->select('exams', ['exam_id'], ['ORDER' => ['date_created' => 'DESC'], 'LIMIT' => 1]);

			if (empty($latestExam)) {
				// Return zeros if no exams exist
				$means = [];
				foreach ($subjects as $subject) {
					$means[$subject] = 0;
				}
				$means['total_mean'] = 0;
				return $means;
			}
			
			// Extract examId from the latest exam
			$examId = $latestExam[0]['exam_id'] ?? null;
		}

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

		// Fetch exam results for these students and this exam
		$results = $this->db->select('exam_results', '*', [
			'exam_id' => $examId,
			'student_id' => $studentIds
		]);

		// Process results
		if (is_array($results)) {
			foreach ($results as $row) {
				foreach ($subjects as $subject) {
					if ($row[$subject] !== null) {
						$subject_totals[$subject] += $row[$subject];
						$subject_counts[$subject]++;
					}
				}
				if ($row['total_marks'] > 0) {
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
