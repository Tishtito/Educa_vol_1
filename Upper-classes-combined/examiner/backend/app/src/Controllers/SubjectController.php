<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SubjectController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	/**
	 * GET /subjects/marks
	 * Fetch student list with their marks for a specific subject
	 */
	public function getMarks(): void
	{
		$this->startSession();

		// Check authentication
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$subject = $_GET['subject'] ?? null;
		$class = $_GET['class'] ?? null;
		$examId = $_GET['exam_id'] ?? null;

		if (!$subject || !$class || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
			return;
		}

		try {
			// Get marks out of for this subject and exam
			$marksOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
				'exam_id' => intval($examId),
				'subject' => $subject
			]);

			$marksOutOfValue = $marksOutOf ? intval($marksOutOf['marks_out_of']) : null;

			// Get all active students in this class from students table
			$students = $this->db->select('students', ['student_id', 'name'], [
				'class' => $class,
				'status' => 'Active'
			]);

			// Get marks for each student in this subject/exam
			if (!empty($students)) {
				$students = array_map(function ($student) use ($subject, $examId) {
					// Get the student_class_id from student_classes table
					$studentClass = $this->db->get('student_classes', ['student_class_id'], [
						'student_id' => intval($student['student_id'])
					]);

					if (!$studentClass) {
						return null;
					}

					$studentClassId = $studentClass['student_class_id'];

					// Get marks from exam_results
					$result = $this->db->get('exam_results', '*', [
						'student_class_id' => intval($studentClassId),
						'exam_id' => intval($examId)
					]);
					
					$marks = null;
					if ($result && isset($result[$subject])) {
						$marks = $result[$subject];
					}
					
					return [
						'student_id' => $student['student_id'],
						'student_name' => $student['name'] ?? 'Unknown',
						'student_class_id' => $studentClassId,
						'marks' => $marks
					];
				}, $students);

				// Filter out null entries
				$students = array_filter($students, function ($student) {
					return $student !== null;
				});
				
				// Sort by student name
				usort($students, function ($a, $b) {
					return strcmp($a['student_name'], $b['student_name']);
				});
			}

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'subject' => $subject,
				'class' => $class,
				'exam_id' => $examId,
				'marks_out_of' => $marksOutOfValue,
				'students' => $students ?? []
			]);

		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to fetch marks']);
		}
	}

	/**
	 * POST /subjects/marks/update
	 * Update marks for a student in a specific subject
	 */
	public function updateMarks(): void
	{
		$this->startSession();

		// Check authentication
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$rawInput = file_get_contents('php://input');
		$input = json_decode($rawInput, true);

		$studentClassId = $input['student_class_id'] ?? null;
		$subject = $input['subject'] ?? null;
		$examId = $input['exam_id'] ?? null;
		$marks = isset($input['marks']) ? floatval($input['marks']) : null;

		if ($studentClassId === null || !$subject || !$examId || $marks === null) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
			return;
		}

		try {
			// Get marks out of for this subject and exam
			$marksOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
				'exam_id' => intval($examId),
				'subject' => $subject
			]);

			$maxMarks = $marksOutOf ? intval($marksOutOf['marks_out_of']) : 100;

			// Validate subject is valid
			$validSubjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Integrated_science', 'AgricNutri', 'SST', 'CRE', 'SciTech'];
			if (!in_array($subject, $validSubjects)) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => "Invalid subject: {$subject}. Valid subjects are: " . implode(', ', $validSubjects)]);
				return;
			}

			// Validate marks are within bounds
			if ($marks < 0 || $marks > $maxMarks) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => "Marks must be between 0 and {$maxMarks}"]);
				return;
			}

			// Convert marks to percentage (following the working logic)
			$percentage = ($marks / $maxMarks) * 100;

			// Get student_id from student_classes table
			$studentClass = $this->db->get('student_classes', ['student_id'], [
				'student_class_id' => intval($studentClassId)
			]);

			if (!$studentClass) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student class record not found']);
				return;
			}

			$studentId = $studentClass['student_id'];

			// Check if exam_results record exists for this student/exam
			$existingResult = $this->db->get('exam_results', '*', [
				'student_class_id' => intval($studentClassId),
				'exam_id' => intval($examId)
			]);

			if ($existingResult) {
				// Record exists, UPDATE it
				$updateData = [
					$subject => $percentage
				];
				
				$this->db->update('exam_results', $updateData, [
					'student_class_id' => intval($studentClassId),
					'exam_id' => intval($examId)
				]);
			} else {
				// Record doesn't exist, INSERT it
				$insertData = [
					'exam_id' => intval($examId),
					'student_id' => intval($studentId),
					'student_class_id' => intval($studentClassId),
					$subject => $percentage
				];
				
				$this->db->insert('exam_results', $insertData);
			}

			// Recalculate total marks for this student
			$this->recalculateTotalMarks(intval($studentClassId), intval($examId));

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'message' => 'Marks updated successfully',
				'marks' => $marks,
				'percentage' => $percentage,
				'subject' => $subject
			]);

		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update marks', 'error' => $e->getMessage()]);
		}
	}

	/**
	 * POST /subjects/marks-out-of
	 * Set the marks out of for a subject in an exam
	 */
	public function setMarksOutOf(): void
	{
		$this->startSession();

		// Check authentication
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$input = json_decode(file_get_contents('php://input'), true);

		$subject = $input['subject'] ?? null;
		$examId = $input['exam_id'] ?? null;
		$marksOutOf = $input['marks_out_of'] ?? null;

		if (!$subject || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Subject and exam ID are required']);
			return;
		}

		if ($marksOutOf === null || $marksOutOf === '' || $marksOutOf < 1) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Marks out of must be a number greater than 0']);
			return;
		}

		try {
			// Check if record exists first
			$existing = $this->db->get('marks_out_of', '*', [
				'exam_id' => intval($examId),
				'subject' => $subject
			]);

			if ($existing) {
				// Update existing record
				$this->db->update('marks_out_of', [
					'marks_out_of' => intval($marksOutOf)
				], [
					'exam_id' => intval($examId),
					'subject' => $subject
				]);
			} else {
				// Insert new record
				$this->db->insert('marks_out_of', [
					'exam_id' => intval($examId),
					'subject' => $subject,
					'marks_out_of' => intval($marksOutOf)
				]);
			}

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'message' => 'Marks out of set successfully',
				'subject' => $subject,
				'exam_id' => intval($examId),
				'marks_out_of' => intval($marksOutOf)
			]);

		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to set marks out of']);
		}
	}

	/**
	 * Recalculate total marks for a student
	 */
	private function recalculateTotalMarks(int $studentClassId, int $examId): void
	{
		try {
			$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Integrated_science', 'AgricNutri', 'SST', 'CRE', 'SciTech'];

			$examResult = $this->db->get('exam_results', '*', [
				'student_class_id' => $studentClassId,
				'exam_id' => $examId
			]);

			if (!$examResult) {
				return;
			}

			$totalMarks = 0;
			$subjectCount = 0;

			foreach ($subjects as $subject) {
				if (isset($examResult[$subject]) && $examResult[$subject] !== null) {
					$totalMarks += floatval($examResult[$subject]);
					$subjectCount++;
				}
			}

			// Calculate mean if we have marks
			if ($subjectCount > 0) {
				$totalMarks = round($totalMarks / $subjectCount, 2);
			} else {
				$totalMarks = 0;
			}

			// Update total_marks in exam_results
			$this->db->update('exam_results', ['total_marks' => $totalMarks], [
				'student_class_id' => $studentClassId,
				'exam_id' => $examId
			]);

		} catch (\Exception $e) {
			// Silently fail recalculation
		}
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
