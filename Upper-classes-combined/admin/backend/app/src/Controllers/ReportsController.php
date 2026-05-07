<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportsController
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

	public function exams(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
		$allowed = ['Weekly', 'Opener', 'Mid-Term', 'End-Term'];
		if (!in_array($type, $allowed, true)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid exam type']);
			return;
		}

		try {
			$rows = $this->db->select('exams', ['exam_id', 'exam_name', 'exam_type'], [
				'exam_type' => $type,
				'ORDER' => ['exam_id' => 'ASC'],
			]);

			$data = array_map(function ($row) {
				$examId = (int)$row['exam_id'];
				return [
					'exam_id' => $examId,
					'exam_name' => $row['exam_name'],
					'exam_type' => $row['exam_type'],
					'token' => $this->makeToken('exam:' . $examId),
				];
			}, $rows ?: []);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $data,
			]);
		} catch (\Throwable $e) {
			error_log('[ReportsController] exams error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load exams']);
		}
	}

	public function grades(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($examId <= 0 || !$this->isValidToken('exam:' . $examId, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$stmt = $this->db->query("SELECT DISTINCT class FROM students ORDER BY class ASC");
			$rows = $stmt ? $stmt->fetchAll() : [];
			$grades = array_map(fn($row) => $row['class'], $rows);
			$data = array_map(function ($grade) use ($examId) {
				return [
					'grade' => $grade,
					'token' => $this->makeToken('exam:' . $examId . '|grade:' . $grade),
				];
			}, $grades);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $data,
			]);
		} catch (\Throwable $e) {
			error_log('[ReportsController] grades error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load grades']);
		}
	}

	public function students(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$grade = isset($_GET['grade']) ? trim((string)$_GET['grade']) : '';
		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($grade === '' || $examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid grade or exam id']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId . '|grade:' . $grade, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$sql = "SELECT students.student_id, students.name, students.class FROM students INNER JOIN exam_results ON students.student_id = exam_results.student_id WHERE students.class = :grade AND exam_results.exam_id = :exam_id ORDER BY students.name ASC";
			$stmt = $this->db->query($sql, [':grade' => $grade, ':exam_id' => $examId]);
			$rows = $stmt ? $stmt->fetchAll() : [];

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[ReportsController] students error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load students']);
		}
	}

	public function reportSingle(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($studentId <= 0 || $examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student or exam id']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$student = $this->db->get('students', ['student_id', 'name', 'class'], [
				'student_id' => $studentId,
			]);
			if (!$student) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student not found']);
				return;
			}

			$teacher = $this->db->get('class_teachers', ['name'], [
				'class_assigned' => $student['class'],
			]);
			$tutor = $teacher['name'] ?? 'Not Assigned';

			$exam = $this->db->get('exams', ['term', 'exam_type', 'date_created'], [
				'exam_id' => $examId,
			]);
			if (!$exam) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Exam not found']);
				return;
			}
			$examYear = $exam['date_created'] ? (int)date('Y', strtotime((string)$exam['date_created'])) : null;

			$results = $this->db->get('exam_results', '*', [
				'student_id' => $studentId,
				'exam_id' => $examId,
			]);
			if (!$results) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'No exam results found']);
				return;
			}

			$levels = $this->db->select('point_boundaries', ['min_marks', 'max_marks', 'pl', 'ab']);
			$subjects = $this->subjects();

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'student' => $student,
					'tutor' => $tutor,
					'exam' => [
						'exam_id' => $examId,
						'term' => $exam['term'],
						'exam_type' => $exam['exam_type'],
						'exam_year' => $examYear,
					],
					'results' => $results,
					'levels' => $levels,
					'subjects' => $subjects,
				],
			]);
		} catch (\Throwable $e) {
			error_log('[ReportsController] reportSingle error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load report']);
		}
	}

	public function reportCombined(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($studentId <= 0 || $examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student or exam id']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$student = $this->db->get('students', ['student_id', 'name', 'class'], [
				'student_id' => $studentId,
			]);
			if (!$student) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student not found']);
				return;
			}

			$teacher = $this->db->get('class_teachers', ['name'], [
				'class_assigned' => $student['class'],
			]);
			$tutor = $teacher['name'] ?? 'Not Assigned';

			$exam = $this->db->get('exams', ['term', 'exam_type', 'date_created'], [
				'exam_id' => $examId,
			]);
			if (!$exam) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Exam not found']);
				return;
			}
			$examYear = $exam['date_created'] ? (int)date('Y', strtotime((string)$exam['date_created'])) : null;

			$term = (string)$exam['term'];
		$examType = trim((string)$exam['exam_type']);
		$examRows = $this->db->select('exams', ['exam_id', 'exam_type'], [
			'term' => $term,
			'exam_type' => ['Mid-Term', 'End-Term'],
		]);
		$midTermId = null;
		$endTermId = null;
	
	// First, check if the current exam is a Mid-Term or End-Term
	if ($examType === 'End-Term') {
		$endTermId = $examId;
	} elseif ($examType === 'Mid-Term') {
		$midTermId = $examId;
	}
	
	// Then search for the complementary exam (Mid-Term if we have End-Term, or vice versa)
	foreach ($examRows as $row) {
		$rowExamId = (int)$row['exam_id'];
		$rowExamType = trim((string)$row['exam_type']);
		if ($rowExamId === $examId) {
			// Skip the current exam, we already have it
			continue;
		}
		if ($rowExamType === 'Mid-Term' && $midTermId === null) {
			$midTermId = $rowExamId;
		} elseif ($rowExamType === 'End-Term' && $endTermId === null) {
			$endTermId = $rowExamId;
		}
	}
	
	error_log('[ReportsController] reportCombined: examId=' . $examId . ', examType=' . $examType . ', term=' . $term . ', midTermId=' . ($midTermId ?? 'null') . ', endTermId=' . ($endTermId ?? 'null'));
		$midResults = $midTermId ? $this->db->get('exam_results', '*', [
			'student_id' => $studentId,
			'exam_id' => $midTermId,
		]) : null;

		$endResults = $endTermId ? $this->db->get('exam_results', '*', [
			'student_id' => $studentId,
			'exam_id' => $endTermId,
		]) : null;

			error_log('[ReportsController] reportCombined: midResults=' . json_encode($midResults) . ', endResults=' . json_encode($endResults));

			$levels = $this->db->select('point_boundaries', ['min_marks', 'max_marks', 'pl', 'ab']);
			$subjects = $this->subjects();

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'student' => $student,
					'tutor' => $tutor,
					'term' => $term,
					'exam_year' => $examYear,
					'mid_term_id' => $midTermId,
					'end_term_id' => $endTermId,
					'mid_results' => $midResults,
					'end_results' => $endResults,
					'levels' => $levels,
					'subjects' => $subjects,
				],
			]);
		} catch (\Throwable $e) {
			error_log('[ReportsController] reportCombined error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load report']);
		}
	}

	private function subjects(): array
	{
		return [
			'Math' => 'Mathematics',
			'English' => 'English Language',
			'Kiswahili' => 'Kiswahili',
			'Creative' => 'Creative Arts',
			'SciTech' => 'Science and Technology',
			'AgricNutri' => 'Agriculture and Nutrition',
			'SST' => 'Social Studies',
			'CRE' => 'Christian Religious Education',
		];
	}

	public function download(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$grade = isset($_GET['grade']) ? trim((string)$_GET['grade']) : '';
		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$examType = isset($_GET['exam_type']) ? trim((string)$_GET['exam_type']) : '';
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($grade === '' || $examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid grade or exam id']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId . '|grade:' . $grade, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$students = $this->db->query(
				"SELECT students.student_id, students.name, students.class FROM students INNER JOIN exam_results ON students.student_id = exam_results.student_id WHERE students.class = :grade AND exam_results.exam_id = :exam_id ORDER BY students.name ASC",
				[':grade' => $grade, ':exam_id' => $examId]
			)->fetchAll();

			if (empty($students)) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'No students found for selected grade and exam']);
				return;
			}

			$exam = $this->db->get('exams', ['term', 'exam_type', 'date_created'], ['exam_id' => $examId]);
			$term = $exam['term'] ?? '';
			$examYear = isset($exam['date_created']) ? (int)date('Y', strtotime((string)$exam['date_created'])) : null;
			$isCombined = ($examType === 'End-Term') || ($exam['exam_type'] ?? '') === 'End-Term';

			// For combined End-Term reports, find the matching Mid-Term exam
			$midTermId = null;
			if ($isCombined) {
				$midExam = $this->db->get('exams', ['exam_id'], [
					'term' => $term,
					'exam_type' => 'Mid-Term',
				]);
				$midTermId = $midExam ? (int)$midExam['exam_id'] : null;
			}

			$levels = $this->db->select('point_boundaries', ['min_marks', 'max_marks', 'pl']);
			$subjects = $this->subjects();

			$rootPath = realpath(__DIR__ . '/../../../../');
			$cssPath = $rootPath ? $rootPath . '/css/report.css' : null;
			$css = '';
			if (!$cssPath || !file_exists($cssPath)) {
				throw new \Exception("CSS file not found at: " . ($cssPath ?? 'unknown path'));
			}
			$css = file_get_contents($cssPath);
			if ($css === false) {
				throw new \Exception("Failed to read CSS file");
			}

			$logoPath = $rootPath ? $rootPath . '/images/logo.png' : null;
			$logoData = '';
			if ($logoPath && file_exists($logoPath)) {
				$logoContent = file_get_contents($logoPath);
				if ($logoContent !== false) {
					$logoData = 'data:image/png;base64,' . base64_encode($logoContent);
				}
			}

			$pdfCss = '
@page { margin: 1.4cm 1.7cm; }
body { margin: 0; padding: 0; font-size: 13px; }
.report-page { page-break-after: always; }
.report-page:last-child { page-break-after: avoid; }
.invoice { padding: 0; min-height: auto; font-size: 13px; }
.invoice header { padding: 10px 0; margin-bottom: 10px; border-bottom: 1px solid #3989c6; }
.invoice .contacts { margin-bottom: 10px; }
.invoice main { padding-bottom: 0; }
.invoice table { margin-bottom: 14px; }
.invoice table td, .invoice table th { padding: 8px 10px; font-size: 14px; }
.invoice table th { font-size: 14px; }
.invoice table td h3 { font-size: 15px; margin: 0; }
.invoice table .no { font-size: 15px; }
.invoice table .unit { font-size: 14px; }
.invoice table .total { font-size: 14px; }
.invoice table tfoot td { padding: 8px 12px; font-size: 14px; }
.invoice table tfoot tr:last-child td { font-size: 15px; }
.invoice footer { padding: 7px 0; font-size: 10px; }
.invoice .company-details .name { font-size: 20px; }
.invoice .company-details div { font-size: 14px !important; }
.invoice .invoice-details .invoice-id { font-size: 20px; }
.invoice .invoice-details .date { font-size: 14px !important; }
.invoice .invoice-to .to { font-size: 20px; }
.invoice .invoice-to .address { font-size: 14px !important; }
.invoice header img { max-height: 88px; }
.invoice .row { display: table; width: 100%; }
.invoice .col { display: table-cell; vertical-align: top; }
.invoice .text-center { text-align: center; }
.invoice .text-left { text-align: left; }
.invoice .text-right { text-align: right; }
';

			$combinedHtml = '<!DOCTYPE html><html><head><style>' . $css . '</style><style>' . $pdfCss . '</style></head><body>';

			foreach ($students as $student) {
				$results = $this->db->get('exam_results', '*', [
					'student_id' => (int)$student['student_id'],
					'exam_id' => $examId,
				]);

				if (!$results) {
					continue;
				}

				$teacher = $this->db->get('class_teachers', ['name'], [
					'class_assigned' => $student['class'],
				]);
				$tutor = $teacher['name'] ?? 'Not Assigned';

				if ($isCombined) {
					$midResults = $midTermId ? $this->db->get('exam_results', '*', [
						'student_id' => (int)$student['student_id'],
						'exam_id' => $midTermId,
					]) : null;

					$combinedHtml .= $this->renderCombinedReport(
						$student,
						$tutor,
						$term,
						$examYear,
						$midResults,
						$results,
						$levels,
						$subjects,
						$logoData
					);
				} else {
					$combinedHtml .= $this->renderSingleReport(
						$student,
						$tutor,
						$term,
						$examYear,
						$results,
						$levels,
						$subjects,
						$logoData
					);
				}
			}

			$combinedHtml .= '</body></html>';

			$options = new Options();
			$options->set('isRemoteEnabled', true);
			$options->set('isHtml5ParserEnabled', true);
			$options->set('defaultFont', 'Helvetica');
			$options->set('tempDir', sys_get_temp_dir());
			$options->set('fontCache', sys_get_temp_dir());
			
			$dompdf = new Dompdf($options);
			$dompdf->loadHtml($combinedHtml);
			$dompdf->setPaper('A4', 'portrait');
			$dompdf->render();

			$filename = 'Class_Reports_Grade_' . str_replace(' ', '_', $grade) . '_Exam_' . $examId . '.pdf';
			$pdfOutput = $dompdf->output();
			
			if (empty($pdfOutput)) {
				throw new \Exception("PDF output is empty");
			}

			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . strlen($pdfOutput));
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			echo $pdfOutput;
		} catch (\Throwable $e) {
			error_log('[ReportsController] download error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to generate PDF: ' . $e->getMessage()]);
		}
	}

	private function renderSingleReport(
		array $student,
		string $tutor,
		string $term,
		?int $examYear,
		array $results,
		array $levels,
		array $subjects,
		string $logoData
	): string {
		$totalMarks = 0;
		$rowsHtml = '';
		$index = 1;

		foreach ($subjects as $key => $label) {
			$value = $results[$key] ?? null;
			if ($value !== null) {
				$totalMarks += (int)$value;
			}
			$rowsHtml .= '<tr>' .
				'<td class="no">' . $index++ . '</td>' .
				'<td class="text-left"><h3>' . htmlspecialchars($label) . '</h3></td>' .
				'<td class="unit">' . ($value ?? '-') . '</td>' .
				'<td class="total">' . $this->performanceLevel($value, $levels) . '</td>' .
				'</tr>';
		}

		$logoHtml = $logoData !== '' ? '<img src="' . $logoData . '" data-holder-rendered="true" />' : '';

		return '<div class="report-page">'
			. '<div class="invoice">'
			. '<div>'
			. '<header>'
			. '<div class="row">'
			. '<div class="col">' . $logoHtml . '</div>'
			. '<div class="col company-details">'
			. '<h2 class="name">GATIMU PRIMARY AND JUNIOR SCHOOL</h2>'
			. '<div>P.o Box 141-00217 LIMURU</div>'
			. '<div>debgatimuprimary@gmail.com</div>'
			. '</div>'
			. '</div>'
			. '</header>'
			. '<main>'
			. '<div class="row contacts">'
			. '<div class="col invoice-to">'
			. '<div class="text-gray-light">REPORT FOR:</div>'
			. '<h2 class="to">' . htmlspecialchars($student['name']) . '</h2>'
			. '<div class="address">Grade: ' . htmlspecialchars($student['class']) . '</div>'
			. '<div class="address">Tutor: ' . htmlspecialchars($tutor) . '</div>'
			. '</div>'
			. '<div class="col invoice-details">'
			. '<h1 class="invoice-id">Performance Report</h1>'
			. '<div class="date">' . htmlspecialchars($term) . '</div>'
			. '<div class="date">Year: ' . htmlspecialchars((string)$examYear) . '</div>'
			. '</div>'
			. '</div>'
			. '<table border="0" cellspacing="0" cellpadding="0">'
			. '<thead>'
			. '<tr>'
			. '<th>#</th>'
			. '<th class="text-left">SUBJECTS</th>'
			. '<th class="text-right">PERCENTAGE (%)</th>'
			. '<th class="text-right">Performance Levels</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>' . $rowsHtml . '</tbody>'
			. '<tfoot>'
			. '<tr>'
			. '<td></td>'
			. '<td>TOTAL MARK</td>'
			. '<td>' . $totalMarks . '</td>'
			. '</tr>'
			. '</tfoot>'
			. '</table>'
			. '<div style="margin-top:20px;margin-bottom:10px;">'
			. '<h3 style="font-size:16px;margin:0 0 5px 0;">Class Teacher\'s Remarks:</h3>'
			. '<p style="margin:0;font-size:14px;">-------------------------------------------------------------------------------------------</p>'
			. '</div>'
			. '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:10px;">'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Fee balance:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Next Term Feeding Amount:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Closing Date:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Opening Date:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Head Teacher Signature:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Parents Signature:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '</table>'
			. '<footer>Performance Report should be reach to all parents or else will be treated as an indispline action.</footer>'
			. '</main>'
			. '</div>'
			. '</div>'
			. '</div>';
	}

	private function renderCombinedReport(
		array $student,
		string $tutor,
		string $term,
		?int $examYear,
		?array $midResults,
		array $endResults,
		array $levels,
		array $subjects,
		string $logoData
	): string {
		$totalMid = 0;
		$totalEnd = 0;
		$rowsHtml = '';
		$index = 1;

		foreach ($subjects as $key => $label) {
			$midVal = $midResults[$key] ?? null;
			$endVal = $endResults[$key] ?? null;
			if ($midVal !== null) {
				$totalMid += (int)$midVal;
			}
			if ($endVal !== null) {
				$totalEnd += (int)$endVal;
			}
			$rowsHtml .= '<tr>'
				. '<td class="no">' . $index++ . '</td>'
				. '<td class="text-left"><h3>' . htmlspecialchars($label) . '</h3></td>'
				. '<td class="unit">' . ($midVal ?? '-') . '</td>'
				. '<td class="total">' . $this->performanceLevel($midVal, $levels) . '</td>'
				. '<td class="unit">' . ($endVal ?? '-') . '</td>'
				. '<td class="total">' . $this->performanceLevel($endVal, $levels) . '</td>'
				. '</tr>';
		}

		$logoHtml = $logoData !== '' ? '<img src="' . $logoData . '" data-holder-rendered="true" />' : '';

		return '<div class="report-page">'
			. '<div class="invoice">'
			. '<div>'
			. '<header>'
			. '<div class="row">'
			. '<div class="col">' . $logoHtml . '</div>'
			. '<div class="col company-details">'
			. '<h2 class="name">GATIMU PRIMARY AND JUNIOR SCHOOL</h2>'
			. '<div>P.o Box 141-00217 LIMURU</div>'
			. '<div>debgatimuprimary@gmail.com</div>'
			. '</div>'
			. '</div>'
			. '</header>'
			. '<main>'
			. '<div class="row contacts">'
			. '<div class="col invoice-to">'
			. '<div class="text-gray-light">REPORT FOR:</div>'
			. '<h2 class="to">' . htmlspecialchars($student['name']) . '</h2>'
			. '<div class="address">Grade: ' . htmlspecialchars($student['class']) . '</div>'
			. '<div class="address">Tutor: ' . htmlspecialchars($tutor) . '</div>'
			. '</div>'
			. '<div class="col invoice-details">'
			. '<h1 class="invoice-id">Performance Report</h1>'
			. '<div class="date">' . htmlspecialchars($term) . '</div>'
			. '<div class="date">Year: ' . htmlspecialchars((string)$examYear) . '</div>'
			. '</div>'
			. '</div>'
			. '<table border="0" cellspacing="0" cellpadding="0">'
			. '<thead>'
			. '<tr>'
			. '<th>#</th>'
			. '<th class="text-left">SUBJECTS</th>'
			. '<th colspan="2" class="text-center">Mid-Term</th>'
			. '<th colspan="2" class="text-center">End of Term</th>'
			. '</tr>'
			. '<tr>'
			. '<th></th>'
			. '<th></th>'
			. '<th class="text-center">MARKS</th>'
			. '<th class="text-center">Performance</th>'
			. '<th class="text-center">MARKS</th>'
			. '<th class="text-center">Performance</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>' . $rowsHtml . '</tbody>'
			. '<tfoot>'
			. '<tr>'
			. '<td></td>'
			. '<td>TOTAL MARK</td>'
			. '<td>' . $totalMid . '</td>'
			. '<td>TOTAL MARK</td>'
			. '<td>' . $totalEnd . '</td>'
			. '</tr>'
			. '</tfoot>'
			. '</table>'
			. '<div style="margin-top:20px;margin-bottom:10px;">'
			. '<h3 style="font-size:16px;margin:0 0 5px 0;">Class Teacher\'s Remarks:</h3>'
			. '<p style="margin:0;font-size:14px;">-------------------------------------------------------------------------------------------</p>'
			. '</div>'
			. '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;margin-bottom:10px;">'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Fee balance:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Next Term Feeding Amount:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Closing Date:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Opening Date:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '<tr>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Head Teacher Signature:</b><br>--------------------------------------------</td>'
			. '<td style="width:50%;padding:10px 12px;font-size:14px;vertical-align:top;"><b>Parents Signature:</b><br>--------------------------------------------</td>'
			. '</tr>'
			. '</table>'
			. '<footer>Performance Report should be reach to all parents or else will be treated as an indispline action.</footer>'
			. '</main>'
			. '</div>'
			. '</div>'
			. '</div>';
	}

	private function performanceLevel($score, array $levels): string
	{
		if ($score === null || $score === '') {
			return '-';
		}
		foreach ($levels as $level) {
			if ($score >= $level['min_marks'] && $score <= $level['max_marks']) {
				return (string)$level['pl'];
			}
		}
		return 'UNKNOWN';
	}
}
