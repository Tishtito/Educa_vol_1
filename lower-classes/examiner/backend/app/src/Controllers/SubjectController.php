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
	 * Fetch student list with their marks for all subjects or a specific subject
	 * If subject=all or subject is omitted, returns all subjects' marks
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

		$subject = $_GET['subject'] ?? 'all';
		$class = $_GET['class'] ?? null;
		$examId = $_GET['exam_id'] ?? null;

		if (!$class || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing required parameters: class and exam_id']);
			return;
		}

		try {
			// Get all marks out of for all subjects in this exam
			$marksOutOfRecords = $this->db->select('marks_out_of', '*', [
				'exam_id' => intval($examId)
			]);

			$marksOutOfMap = [];
			foreach ($marksOutOfRecords as $record) {
				$marksOutOfMap[$record['subject']] = intval($record['marks_out_of']);
			}

			// Get all active students in this class from students table
			$students = $this->db->select('students', ['student_id', 'name'], [
				'class' => $class,
				'status' => 'Active'
			]);

			// Get marks for each student in all subjects/exam
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

					// Get all marks from exam_results
					$result = $this->db->get('exam_results', '*', [
						'student_class_id' => intval($studentClassId),
						'exam_id' => intval($examId)
					]);

					if (!$result) {
						$result = [];
					}

					// Return all subject marks in raw form
					return [
						'student_id' => $student['student_id'],
						'student_name' => $student['name'] ?? 'Unknown',
						'student_class_id' => $studentClassId,

						// Individual component marks (raw)
						'rdg_marks' => isset($result['RDG']) ? $result['RDG'] : null,
						'grm_marks' => isset($result['GRM']) ? $result['GRM'] : null,
						'lug_marks' => isset($result['LUG']) ? $result['LUG'] : null,
						'kus_marks' => isset($result['KUS']) ? $result['KUS'] : null,

						// Combined subject totals (percentage)
						'english_marks' => isset($result['English']) ? $result['English'] : null,
						'kiswahili_marks' => isset($result['Kiswahili']) ? $result['Kiswahili'] : null,

						// Individual regular subject marks (percentage)
						'math_marks' => isset($result['Math']) ? $result['Math'] : null,
						'enviromental_marks' => isset($result['Enviromental']) ? $result['Enviromental'] : null,
						'creative_marks' => isset($result['Creative']) ? $result['Creative'] : null,
						'religious_marks' => isset($result['Religious']) ? $result['Religious'] : null,

						// Total marks
						'total_marks' => isset($result['total_marks']) ? $result['total_marks'] : null
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
				'marks_out_of' => $marksOutOfMap,
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
		$rdgMarks = isset($input['rdg_marks']) ? floatval($input['rdg_marks']) : null;
		$grmMarks = isset($input['grm_marks']) ? floatval($input['grm_marks']) : null;
		$lugMarks = isset($input['lug_marks']) ? floatval($input['lug_marks']) : null;
		$kusMarks = isset($input['kus_marks']) ? floatval($input['kus_marks']) : null;

		if ($studentClassId === null || !$subject || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
			return;
		}

		// Validate based on subject type
		if ($subject === 'GRM') {
			if ($rdgMarks === null || $grmMarks === null) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'RDG and GRM marks are required']);
				return;
			}
		} elseif ($subject === 'LUG') {
			if ($lugMarks === null || $kusMarks === null) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'LUG and KUS marks are required']);
				return;
			}
		}

		try {
			// Validate subject is valid
			//$validSubjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
			$validSubjects = ['Math', 'RDG', 'GRM', 'KUS', 'LUG','Enviromental', 'Creative', 'Religious'];
			if (!in_array($subject, $validSubjects)) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => "Invalid subject: {$subject}. Valid subjects are: " . implode(', ', $validSubjects)]);
				return;
			}

			// Get marks out of based on subject type
			if ($subject === 'GRM') {
				// Get individual marks out of for GRM and RDG
				$grmOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
					'exam_id' => intval($examId),
					'subject' => 'GRM'
				]);
				$rdgOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
					'exam_id' => intval($examId),
					'subject' => 'RDG'
				]);

				$grmMaxMarks = $grmOutOf ? intval($grmOutOf['marks_out_of']) : 100;
				$rdgMaxMarks = $rdgOutOf ? intval($rdgOutOf['marks_out_of']) : 100;

				// Validate marks are within bounds for each component
				if ($rdgMarks < 0 || $grmMarks < 0) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "RDG and GRM marks cannot be negative"]);
					return;
				}
				if ($rdgMarks > $rdgMaxMarks) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "RDG marks must be between 0 and {$rdgMaxMarks}"]);
					return;
				}
				if ($grmMarks > $grmMaxMarks) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "GRM marks must be between 0 and {$grmMaxMarks}"]);
					return;
				}
			} elseif ($subject === 'LUG') {
				// Get individual marks out of for LUG and KUS
				$lugOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
					'exam_id' => intval($examId),
					'subject' => 'LUG'
				]);
				$kusOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
					'exam_id' => intval($examId),
					'subject' => 'KUS'
				]);

				$lugMaxMarks = $lugOutOf ? intval($lugOutOf['marks_out_of']) : 100;
				$kusMaxMarks = $kusOutOf ? intval($kusOutOf['marks_out_of']) : 100;

				// Validate marks are within bounds for each component
				if ($lugMarks < 0 || $kusMarks < 0) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "LUG and KUS marks cannot be negative"]);
					return;
				}
				if ($lugMarks > $lugMaxMarks) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "LUG marks must be between 0 and {$lugMaxMarks}"]);
					return;
				}
				if ($kusMarks > $kusMaxMarks) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "KUS marks must be between 0 and {$kusMaxMarks}"]);
					return;
				}
			} else {
				// Get marks out of for regular subject
				$marksOutOf = $this->db->get('marks_out_of', ['marks_out_of'], [
					'exam_id' => intval($examId),
					'subject' => $subject
				]);
				$maxMarks = $marksOutOf ? intval($marksOutOf['marks_out_of']) : 100;

				// Validate marks are within bounds
				if ($marks < 0 || $marks > $maxMarks) {
					http_response_code(400);
					header('Content-Type: application/json');
					echo json_encode(['success' => false, 'message' => "Marks must be between 0 and {$maxMarks}"]);
					return;
				}
			}

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

			if ($subject === 'GRM') {

				// Calculate English total using individual component max marks
				$combinedMaxMarks = $grmMaxMarks + $rdgMaxMarks;
				$englishTotal = ($combinedMaxMarks > 0) ? (($rdgMarks + $grmMarks) / $combinedMaxMarks) * 100 : 0;

				// Handle GRM + RDG combined subject
				if ($existingResult) {

					// Update RDG, GRM and English total
					$updateSql = "
						UPDATE exam_results 
						SET RDG = ?, GRM = ?, English = ?
						WHERE student_class_id = ? AND exam_id = ?
					";

					$stmt = $this->db->pdo->prepare($updateSql);

					$stmt->execute([
						$rdgMarks,
						$grmMarks,
						$englishTotal,
						intval($studentClassId),
						intval($examId)
					]);

				} else {

					// Insert new record with RDG, GRM and English total
					$insertSql = "
						INSERT INTO exam_results 
						(exam_id, student_id, student_class_id, RDG, GRM, English)
						VALUES (?, ?, ?, ?, ?, ?)
					";

					$stmt = $this->db->pdo->prepare($insertSql);

					$stmt->execute([
						intval($examId),
						intval($studentId),
						intval($studentClassId),
						$rdgMarks,
						$grmMarks,
						$englishTotal
					]);
				}
			} elseif ($subject === 'LUG') {

				// Calculate Kiswahili total using individual component max marks
				$combinedMaxMarks = $lugMaxMarks + $kusMaxMarks;
				$kiswahiliTotal = ($combinedMaxMarks > 0) ? (($lugMarks + $kusMarks) / $combinedMaxMarks) * 100 : 0;

				if ($existingResult) {

					// Update LUG, KUS and kiswahili total
					$updateSql = "
						UPDATE exam_results 
						SET LUG = ?, KUS = ?, kiswahili = ?
						WHERE student_class_id = ? AND exam_id = ?
					";

					$stmt = $this->db->pdo->prepare($updateSql);

					$stmt->execute([
						$lugMarks,
						$kusMarks,
						$kiswahiliTotal,
						intval($studentClassId),
						intval($examId)
					]);

				} else {

					// Insert new record
					$insertSql = "
						INSERT INTO exam_results 
						(exam_id, student_id, student_class_id, LUG, KUS, kiswahili)
						VALUES (?, ?, ?, ?, ?, ?)
					";

					$stmt = $this->db->pdo->prepare($insertSql);

					$stmt->execute([
						intval($examId),
						intval($studentId),
						intval($studentClassId),
						$lugMarks,
						$kusMarks,
						$kiswahiliTotal
					]);
				}
			} else {
				// Handle regular subject
				$percentage = ($marks/$maxMarks)*100;

				// Escape subject column name for SQL
				$columnName = in_array($subject, ['LS/SP', 'KUS/KUZ']) ? '`' . $subject . '`' : $subject;

				if ($existingResult) {
					// Record exists, UPDATE it using raw SQL for proper escaping
					$updateSql = "UPDATE exam_results SET " . $columnName . " = ? WHERE student_class_id = ? AND exam_id = ?";
					$stmt = $this->db->pdo->prepare($updateSql);
					$stmt->execute([$percentage, intval($studentClassId), intval($examId)]);
				} else {
					// Record doesn't exist, INSERT it
					$insertSql = "INSERT INTO exam_results (exam_id, student_id, student_class_id, " . $columnName . ") VALUES (?, ?, ?, ?)";
					$stmt = $this->db->pdo->prepare($insertSql);
					$stmt->execute([intval($examId), intval($studentId), intval($studentClassId), $percentage]);
				}
			}

			// Recalculate total marks for this student
			$this->recalculateTotalMarks(intval($studentClassId), intval($examId));

			header('Content-Type: application/json');
			if ($subject === 'GRM') {
				echo json_encode([
					'success' => true,
					'message' => 'Marks updated successfully',
					'subject' => $subject,
					'exam_id' => intval($examId),
					'student_class_id' => intval($studentClassId),
					'rdg_marks' => $rdgMarks,
					'grm_marks' => $grmMarks
				]);
			} elseif ($subject === 'LUG') {
				echo json_encode([
					'success' => true,
					'message' => 'Marks updated successfully',
					'subject' => $subject,
					'exam_id' => intval($examId),
					'student_class_id' => intval($studentClassId),
					'lug_marks' => $lugMarks,
					'kus_marks' => $kusMarks
				]);
			} else {
				echo json_encode([
					'success' => true,
					'message' => 'Marks updated successfully',
					'subject' => $subject,
					'exam_id' => intval($examId),
					'student_class_id' => intval($studentClassId),
					'marks' => $marks
				]);
			}

		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update marks']);
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
			//$subjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
			$subjects = ['Math', 'RDG', 'GRM','KUS', 'LUG', 'Enviromental', 'Creative', 'Religious'];

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
				// Handle special character column names
				if (isset($examResult[$subject]) && $examResult[$subject] !== null) {
					$totalMarks += floatval($examResult[$subject]);
					$subjectCount++;
				}
			}

			// If this is a combined RDG/GRM record, calculate English as RDG + GRM
			if (isset($examResult['RDG']) && isset($examResult['GRM']) && $examResult['RDG'] !== null && $examResult['GRM'] !== null) {
				$englishMarks = floatval($examResult['RDG']) + floatval($examResult['GRM']);
				$totalMarks += $englishMarks;
				$subjectCount++;
			}

			// If this is a combined LUG/KUS record, calculate Kiswahili as LUG + KUS
			if (isset($examResult['LUG']) && isset($examResult['KUS']) && $examResult['LUG'] !== null && $examResult['KUS'] !== null) {
				$kiswahiliMarks = floatval($examResult['LUG']) + floatval($examResult['KUS']);
				$totalMarks += $kiswahiliMarks;
				$subjectCount++;
			}

			// Calculate mean if we have marks
			if ($subjectCount > 0) {
				$totalMarks = round($totalMarks / $subjectCount, 2);
			} else {
				$totalMarks = 0;
			}

			// Update total_marks in exam_results using raw SQL to ensure proper escaping
			$updateSql = "UPDATE exam_results SET total_marks = ? WHERE student_class_id = ? AND exam_id = ?";
			$stmt = $this->db->pdo->prepare($updateSql);
			$stmt->execute([$totalMarks, $studentClassId, $examId]);

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
