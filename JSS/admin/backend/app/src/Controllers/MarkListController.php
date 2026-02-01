<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class MarkListController
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

		$tutor = $this->db->get('class_teachers', 'name', ['class_assigned' => $grade]);
		if (!$tutor) {
			$tutor = 'Class teacher not found';
		}

		$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Technical', 'Agriculture', 'SST', 'Science', 'Religious'];

		$sql = "SELECT s.student_id, s.name AS Name";
		foreach ($subjects as $subject) {
			$sql .= ", er.$subject, (SELECT ab FROM point_boundaries WHERE er.$subject BETWEEN min_marks AND max_marks LIMIT 1) AS PL_$subject";
		}
		$sql .= ", (" . implode(" + ", array_map(fn($s) => "COALESCE(er.$s, 0)", $subjects)) . ") AS total_marks 
			FROM students s
			LEFT JOIN exam_results er ON s.student_id = er.student_id AND er.exam_id = :exam_id
			WHERE s.class = :grade
			ORDER BY total_marks DESC";

		$stmt = $this->db->query($sql, [
			':exam_id' => $examId,
			':grade' => $grade,
		]);
		$students = $stmt ? $stmt->fetchAll() : [];

		$subjectTotals = array_fill_keys($subjects, 0);
		$subjectCounts = array_fill_keys($subjects, 0);
		$totalScore = 0;
		$totalStudents = 0;

		$rank = 1;
		foreach ($students as &$student) {
			foreach ($subjects as $subject) {
				if ($student[$subject] !== null) {
					$subjectTotals[$subject] += (float)$student[$subject];
					$subjectCounts[$subject]++;
				}
			}

			$studentTotal = isset($student['total_marks']) ? (int)$student['total_marks'] : 0;
			if ($studentTotal > 0) {
				$totalScore += $studentTotal;
				$totalStudents++;
			}

			$student['rank'] = $rank;
			$student['total_marks'] = $studentTotal;

			$this->db->update('exam_results', [
				'total_marks' => $studentTotal,
				'position' => $rank,
			], [
				'student_id' => $student['student_id'],
				'exam_id' => $examId,
			]);

			$rank++;
		}
		unset($student);

		$meanScores = [];
		foreach ($subjects as $subject) {
			$count = $subjectCounts[$subject] ?? 0;
			$total = $subjectTotals[$subject] ?? 0;
			$meanScores[$subject] = $count > 0 ? round($total / $count, 2) : 0;
		}
		$meanTotalMarks = $totalStudents > 0 ? round($totalScore / $totalStudents, 2) : 0;

		$exists = $this->db->has('exam_mean_scores', [
			'exam_id' => $examId,
			'class' => $grade,
		]);

		$meanPayload = array_merge($meanScores, ['total_mean' => $meanTotalMarks]);

		if ($exists) {
			$this->db->update('exam_mean_scores', $meanPayload, [
				'exam_id' => $examId,
				'class' => $grade,
			]);
		} else {
			$this->db->insert('exam_mean_scores', array_merge([
				'exam_id' => $examId,
				'class' => $grade,
			], $meanPayload));
		}

		$prevExamId = $this->db->get('exams', 'exam_id', [
			'exam_id[<]' => $examId,
			'ORDER' => ['exam_id' => 'DESC'],
			'LIMIT' => 1,
		]);

		$prevMeanScores = array_fill_keys($subjects, '-');
		$prevMeanTotalMarks = '-';

		if ($prevExamId) {
			$prevRow = $this->db->get('exam_mean_scores', '*', [
				'exam_id' => $prevExamId,
				'class' => $grade,
			]);
			if ($prevRow) {
				foreach ($subjects as $subject) {
					$prevMeanScores[$subject] = $prevRow[$subject] ?? '-';
				}
				$prevMeanTotalMarks = $prevRow['total_mean'] ?? '-';
			}
		}

		$deviationScores = [];
		foreach ($subjects as $subject) {
			$prev = (is_numeric($prevMeanScores[$subject] ?? null)) ? (float)$prevMeanScores[$subject] : null;
			$curr = (is_numeric($meanScores[$subject] ?? null)) ? (float)$meanScores[$subject] : null;
			$deviationScores[$subject] = ($prev !== null && $curr !== null)
				? round($curr - $prev, 2)
				: '-';
		}

		$totalMeanDeviation = (is_numeric($prevMeanTotalMarks) && is_numeric($meanTotalMarks))
			? round(((float)$meanTotalMarks) - ((float)$prevMeanTotalMarks), 2)
			: '-';

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => [
				'exam_name' => $examName,
				'grade_title' => ucwords(str_replace('_', ' ', $grade)),
				'tutor' => $tutor,
				'subjects' => $subjects,
				'students' => $students,
				'mean_scores' => $meanScores,
				'prev_mean_scores' => $prevMeanScores,
				'deviation_scores' => $deviationScores,
				'total_mean' => $meanTotalMarks,
				'prev_total_mean' => $prevMeanTotalMarks,
				'total_mean_deviation' => $totalMeanDeviation,
			],
		]);
	}
}
