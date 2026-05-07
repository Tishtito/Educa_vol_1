<?php

declare(strict_types=1);

namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Medoo\Medoo;

class StudentTranscriptController
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

	private function jsonResponse(array $data, int $code = 200): void
	{
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	private function subjects(): array
	{
		return [
			'Math' => 'Mathematics',
			'English' => 'English Language',
			'Kiswahili' => 'Kiswahili',
			'Technical' => 'Pre-Technical',
			'Agriculture' => 'Agriculture',
			'Creative' => 'Creative Arts',
			'Religious' => 'Christian Religious Education',
			'SST' => 'Social Studies',
			'Science' => 'Integrated Science',
		];
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
		return '-';
	}

	public function download(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		if ($studentId <= 0) {
			$this->jsonResponse(['success' => false, 'message' => 'Invalid student id'], 400);
			return;
		}

		try {
			$student = $this->db->get('students', ['student_id', 'name', 'class'], [
				'student_id' => $studentId,
			]);

			if (!$student) {
				$this->jsonResponse(['success' => false, 'message' => 'Student not found'], 404);
				return;
			}

			$sql = "SELECT er.exam_id, er.result_id, er.total_marks, er.position, er.stream_position, er.created_at,
					er.Math, er.English, er.Kiswahili, er.Technical, er.Agriculture, er.Creative, er.Religious, er.SST, er.Science,
					e.exam_name, e.term, e.exam_type, e.date_created
					FROM exam_results er
					LEFT JOIN exams e ON er.exam_id = e.exam_id
					WHERE er.student_id = :student_id AND er.deleted_at IS NULL
					ORDER BY er.created_at DESC";
			$stmt = $this->db->query($sql, [':student_id' => $studentId]);
			$results = $stmt ? $stmt->fetchAll() : [];

			if (empty($results)) {
				$this->jsonResponse(['success' => false, 'message' => 'No exam results found for this student'], 400);
				return;
			}

			$levels = $this->db->select('point_boundaries', ['min_marks', 'max_marks', 'pl']);
			$subjects = $this->subjects();

			$rootPath = realpath(__DIR__ . '/../../../../');
			$logoPath = $rootPath ? $rootPath . '/images/logo.png' : null;
			$logoData = '';
			if ($logoPath && file_exists($logoPath)) {
				$logoContent = file_get_contents($logoPath);
				if ($logoContent !== false) {
					$logoData = 'data:image/png;base64,' . base64_encode($logoContent);
				}
			}

			$html = $this->buildTranscriptHtml($student, $results, $levels, $subjects, $logoData);

			$options = new Options();
			$options->set('isRemoteEnabled', true);
			$options->set('isHtml5ParserEnabled', true);
			$options->set('defaultFont', 'Helvetica');
			$options->set('tempDir', sys_get_temp_dir());
			$options->set('fontCache', sys_get_temp_dir());

			$dompdf = new Dompdf($options);
			$dompdf->loadHtml($html);
			$dompdf->setPaper('A4', 'portrait');
			$dompdf->render();

			$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student['name']);
			$filename = 'Transcript_' . $safeName . '.pdf';
			$pdfOutput = $dompdf->output();

			if (empty($pdfOutput)) {
				throw new \Exception('PDF output is empty');
			}

			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . strlen($pdfOutput));
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			echo $pdfOutput;
		} catch (\Throwable $e) {
			error_log('[StudentTranscriptController] download error: ' . $e->getMessage());
			$this->jsonResponse(['success' => false, 'message' => 'Failed to generate transcript PDF'], 500);
		}
	}

	private function buildTranscriptHtml(
		array $student,
		array $results,
		array $levels,
		array $subjects,
		string $logoData
	): string {
		$logoHtml = $logoData !== '' ? '<img src="' . $logoData . '" style="max-height:70px;" />' : '';

		$css = '
@page { margin: 1.4cm 1.7cm; }
* { box-sizing: border-box; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; color: #333; }
.page { page-break-after: always; }
.page:last-child { page-break-after: avoid; }
.header-row { display: table; width: 100%; margin-bottom: 12px; border-bottom: 2px solid #3989c6; padding-bottom: 10px; }
.header-col { display: table-cell; vertical-align: middle; }
.header-col.logo { width: 80px; }
.header-col.school { text-align: center; }
.school-name { font-size: 18px; font-weight: bold; color: #2c3e50; margin: 0; }
.school-info { font-size: 11px; color: #666; margin: 2px 0; }
.transcript-title { text-align: center; font-size: 16px; font-weight: bold; color: #3989c6; margin: 14px 0 10px; text-transform: uppercase; letter-spacing: 1px; }
.student-info { display: table; width: 100%; margin-bottom: 14px; background: #f8f9fa; padding: 8px 12px; border-radius: 4px; }
.student-info .col { display: table-cell; vertical-align: top; width: 50%; }
.student-info .label { font-weight: bold; color: #555; font-size: 11px; }
.student-info .value { font-size: 13px; color: #222; }
.exam-section { margin-bottom: 8px; }
.exam-header { background: #3989c6; color: #fff; padding: 6px 10px; font-size: 13px; font-weight: bold; margin-bottom: 0; border-radius: 3px 3px 0 0; }
.exam-header .exam-date { float: right; font-weight: normal; font-size: 11px; }
.exam-meta { background: #e8f0f8; padding: 4px 10px; font-size: 11px; color: #555; border-bottom: 1px solid #ccc; }
table.marks { width: 100%; border-collapse: collapse; margin-bottom: 0; }
table.marks th { background: #f0f0f0; padding: 5px 8px; text-align: left; font-size: 11px; border: 1px solid #ddd; color: #444; }
table.marks td { padding: 4px 8px; border: 1px solid #ddd; font-size: 11px; }
table.marks tr:nth-child(even) { background: #fafafa; }
table.marks .num { text-align: center; width: 30px; }
table.marks .mark { text-align: center; width: 70px; }
table.marks .pl { text-align: center; width: 50px; }
.totals-row { background: #f0f0f0; font-weight: bold; }
.totals-row td { border-top: 2px solid #3989c6; }
.summary-table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 10px; }
.summary-table th { background: #3989c6; color: #fff; padding: 6px 10px; font-size: 12px; text-align: left; border: 1px solid #2e7ab5; }
.summary-table td { padding: 5px 10px; border: 1px solid #ddd; font-size: 11px; }
.summary-table tr:nth-child(even) { background: #f8f9fa; }
.footer { text-align: center; font-size: 9px; color: #999; margin-top: 16px; border-top: 1px solid #ddd; padding-top: 6px; }
';

		$html = '<!DOCTYPE html><html><head><style>' . $css . '</style></head><body>';

		// Split results into chunks of 4 exams per page
		$examsPerPage = 4;
		$chunks = array_chunk($results, $examsPerPage);

		foreach ($chunks as $pageIndex => $pageResults) {
			$html .= '<div class="page">';

			// Header on every page
			$html .= '<div class="header-row">';
			$html .= '<div class="header-col logo">' . $logoHtml . '</div>';
			$html .= '<div class="header-col school">';
			$html .= '<p class="school-name">GATIMU PRIMARY AND JUNIOR SCHOOL</p>';
			$html .= '<p class="school-info">P.O Box 141-00217 LIMURU | debgatimuprimary@gmail.com</p>';
			$html .= '</div>';
			$html .= '<div class="header-col logo"></div>';
			$html .= '</div>';

			if ($pageIndex === 0) {
				$html .= '<div class="transcript-title">Student Academic Transcript</div>';

				$html .= '<div class="student-info">';
				$html .= '<div class="col"><span class="label">Student Name: </span><span class="value">' . htmlspecialchars($student['name']) . '</span></div>';
				$html .= '<div class="col"><span class="label">Class: </span><span class="value">' . htmlspecialchars($student['class']) . '</span></div>';
				$html .= '</div>';

				// Summary table on first page
				$html .= '<table class="summary-table">';
				$html .= '<thead><tr><th>#</th><th>Exam</th><th>Term</th><th>Total Marks</th><th>Position</th><th>Stream Pos.</th><th>Date</th></tr></thead>';
				$html .= '<tbody>';
				foreach ($results as $i => $r) {
					$date = !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-';
					$html .= '<tr>';
					$html .= '<td>' . ($i + 1) . '</td>';
					$html .= '<td>' . htmlspecialchars($r['exam_name'] ?? 'N/A') . '</td>';
					$html .= '<td>' . htmlspecialchars($r['term'] ?? '-') . '</td>';
					$html .= '<td>' . ($r['total_marks'] ?? '-') . '</td>';
					$html .= '<td>' . ($r['position'] ?? '-') . '</td>';
					$html .= '<td>' . ($r['stream_position'] ?? '-') . '</td>';
					$html .= '<td>' . $date . '</td>';
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			}

			// Detailed exam breakdowns
			foreach ($pageResults as $result) {
				$examDate = !empty($result['created_at']) ? date('d M Y', strtotime($result['created_at'])) : '';
				$examYear = !empty($result['date_created']) ? date('Y', strtotime($result['date_created'])) : '';

				$html .= '<div class="exam-section">';
				$html .= '<div class="exam-header">';
				$html .= htmlspecialchars($result['exam_name'] ?? 'Exam');
				$html .= '<span class="exam-date">' . $examDate . '</span>';
				$html .= '</div>';
				$html .= '<div class="exam-meta">';
				$html .= 'Term: ' . htmlspecialchars($result['term'] ?? '-');
				$html .= ' &nbsp;|&nbsp; Type: ' . htmlspecialchars($result['exam_type'] ?? '-');
				$html .= ' &nbsp;|&nbsp; Year: ' . htmlspecialchars($examYear ?: '-');
				$html .= ' &nbsp;|&nbsp; Position: ' . ($result['position'] ?? '-');
				$html .= ' &nbsp;|&nbsp; Stream Position: ' . ($result['stream_position'] ?? '-');
				$html .= '</div>';

				$html .= '<table class="marks">';
				$html .= '<thead><tr><th class="num">#</th><th>Subject</th><th class="mark">Marks</th><th class="pl">PL</th></tr></thead>';
				$html .= '<tbody>';

				$total = 0;
				$idx = 1;
				foreach ($subjects as $key => $label) {
					$val = $result[$key] ?? null;
					if ($val !== null) {
						$total += (int)$val;
					}
					$html .= '<tr>';
					$html .= '<td class="num">' . $idx++ . '</td>';
					$html .= '<td>' . htmlspecialchars($label) . '</td>';
					$html .= '<td class="mark">' . ($val ?? '-') . '</td>';
					$html .= '<td class="pl">' . $this->performanceLevel($val, $levels) . '</td>';
					$html .= '</tr>';
				}

				$html .= '<tr class="totals-row"><td></td><td>Total Marks</td><td class="mark">' . $total . '</td><td></td></tr>';
				$html .= '</tbody></table>';
				$html .= '</div>';
			}

			$html .= '<div class="footer">Gatimu Primary and Junior School &mdash; Academic Transcript &mdash; Page ' . ($pageIndex + 1) . ' of ' . count($chunks) . '</div>';
			$html .= '</div>';
		}

		$html .= '</body></html>';
		return $html;
	}
}
