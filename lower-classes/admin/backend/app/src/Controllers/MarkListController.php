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

		// Map display names to SQL column names with proper escaping
		$subjectInformation = [
			'Math' => 'Math',
			'LS/SP' => '`LS/SP`',
			'RDG' => 'RDG',
			'GRM' => 'GRM',
			'WRI' => 'WRI',
			'KUS/KUZ' => '`KUS/KUZ`',
			'KUS' => 'KUS',
			'LUG' => 'LUG',
			'KUA' => 'KUA',
			'Enviromental' => 'Enviromental',
			'Creative' => 'Creative',
			'Religious' => 'Religious'
		];

		$sql = "SELECT s.student_id, s.name AS Name";
		foreach ($subjectInformation as $displayName => $columnName) {
			$aliasName = str_replace(['/', '-', ' '], '', $displayName);
			$sql .= ", er." . $columnName . " AS " . $aliasName;
			// Get abbreviation from point_boundaries matching both subject and marks range
			$sql .= ", (SELECT ab FROM point_boundaries WHERE subject = '" . str_replace("'", "''", $displayName) . "' AND er." . $columnName . " BETWEEN min_marks AND max_marks LIMIT 1) AS PL_" . $aliasName;
		}
		$coalesceParts = array_map(function($columnName) {
			return "COALESCE(er." . $columnName . ", 0)";
		}, $subjectInformation);
		$sql .= ", (" . implode(" + ", $coalesceParts) . ") AS total_marks 
			FROM students s
			LEFT JOIN exam_results er ON s.student_id = er.student_id AND er.exam_id = :exam_id
			WHERE s.class = :grade
			ORDER BY total_marks DESC";

		$stmt = $this->db->query($sql, [
			':exam_id' => $examId,
			':grade' => $grade,
		]);
		$students = $stmt ? $stmt->fetchAll() : [];

		// Map display names to result column aliases
		$subjectAliasMap = [];
		foreach ($subjectInformation as $displayName => $columnName) {
			$aliasName = str_replace(['/', '-', ' '], '', $displayName);
			$subjectAliasMap[$displayName] = $aliasName;
		}

		$subjectTotals = array_fill_keys(array_keys($subjectInformation), 0);
		$subjectCounts = array_fill_keys(array_keys($subjectInformation), 0);
		$totalScore = 0;
		$totalStudents = 0;

		$rank = 1;
		foreach ($students as &$student) {
			foreach ($subjectInformation as $displayName => $columnName) {
				$aliasName = $subjectAliasMap[$displayName];
				if (isset($student[$aliasName]) && $student[$aliasName] !== null) {
					$subjectTotals[$displayName] += (float)$student[$aliasName];
					$subjectCounts[$displayName]++;
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
		foreach ($subjectInformation as $displayName => $columnName) {
			$count = $subjectCounts[$displayName] ?? 0;
			$total = $subjectTotals[$displayName] ?? 0;
			$meanScores[$displayName] = $count > 0 ? round($total / $count, 2) : 0;
		}
		$meanTotalMarks = $totalStudents > 0 ? round($totalScore / $totalStudents, 2) : 0;

		$exists = $this->db->has('exam_mean_scores', [
			'exam_id' => $examId,
			'class' => $grade,
		]);

		// Build SET clause for update with properly escaped column names
		$setClauses = [];
		$params = [':exam_id' => $examId, ':grade' => $grade];
		$paramIndex = 0;
		
		foreach ($meanScores as $displayName => $value) {
			$columnName = $subjectInformation[$displayName];
			$setClauses[] = $columnName . " = :val_" . $paramIndex;
			$params[':val_' . $paramIndex] = $value;
			$paramIndex++;
		}
		$setClauses[] = "total_mean = :total_mean";
		$params[':total_mean'] = $meanTotalMarks;

		if ($exists) {
			$updateSql = "UPDATE exam_mean_scores SET " . implode(", ", $setClauses) . " WHERE exam_id = :exam_id AND class = :grade";
			$this->db->query($updateSql, $params);
		} else {
			// Build INSERT statement
			$columns = array_merge(['id', 'exam_id', 'class'], array_keys($meanScores), ['total_mean']);
			$columnList = array_map(function($col) use ($subjectInformation) {
				if (in_array($col, ['id', 'exam_id', 'class', 'total_mean'])) {
					return $col;
				}
				return $subjectInformation[$col] ?? $col;
			}, $columns);
			
			$nextId = (int)$this->db->max('exam_mean_scores', 'id');
			$nextId = $nextId > 0 ? $nextId + 1 : 1;
			
			$placeholders = ['?', '?', '?'];
			foreach ($meanScores as $displayName => $value) {
				$placeholders[] = '?';
			}
			$placeholders[] = '?';
			
			$insertSql = "INSERT INTO exam_mean_scores (" . implode(", ", $columnList) . ") VALUES (" . implode(", ", $placeholders) . ")";
			$insertValues = [$nextId, $examId, $grade];
			foreach ($meanScores as $value) {
				$insertValues[] = $value;
			}
			$insertValues[] = $meanTotalMarks;
			
			$stmt = $this->db->pdo->prepare($insertSql);
			$stmt->execute($insertValues);
		}

		$prevMeanScores = array_fill_keys(array_keys($subjectInformation), '-');
		$prevMeanTotalMarks = '-';

		$prevRow = $this->db->get('exam_mean_scores', '*', [
			'exam_id[<]' => $examId,
			'class' => $grade,
			'ORDER' => ['exam_id' => 'DESC'],
			'LIMIT' => 1,
		]);
		$prevExamId = $prevRow['exam_id'] ?? $this->db->get('exams', 'exam_id', [
			'exam_id[<]' => $examId,
			'ORDER' => ['exam_id' => 'DESC'],
			'LIMIT' => 1,
		]);

		if ($prevExamId) {
			if ($prevRow) {
				foreach ($subjectInformation as $displayName => $columnName) {
					$prevMeanScores[$displayName] = $prevRow[$displayName] ?? '-';
				}
				$prevMeanTotalMarks = $prevRow['total_mean'] ?? '-';
			} else {
				$prevTotals = array_fill_keys(array_keys($subjectInformation), 0);
				$prevCounts = array_fill_keys(array_keys($subjectInformation), 0);
				$prevTotalScore = 0;
				$prevTotalStudents = 0;

				$prevSql = "SELECT er.*, s.student_id FROM exam_results er INNER JOIN students s ON s.student_id = er.student_id WHERE er.exam_id = :exam_id AND s.class = :grade";
				$prevStmt = $this->db->query($prevSql, [
					':exam_id' => $prevExamId,
					':grade' => $grade,
				]);
				$prevStudents = $prevStmt ? $prevStmt->fetchAll() : [];

				foreach ($prevStudents as $row) {
					$studentTotal = 0;
					foreach ($subjectInformation as $displayName => $columnName) {
						// For SELECT *, column names are the original names
						if (isset($row[$displayName]) && $row[$displayName] !== null) {
							$prevTotals[$displayName] += (float)$row[$displayName];
							$prevCounts[$displayName]++;
							$studentTotal += (float)$row[$displayName];
						}
					}

					if ($studentTotal > 0) {
						$prevTotalScore += $studentTotal;
						$prevTotalStudents++;
					}
				}

				$prevMeanScores = [];
				foreach ($subjectInformation as $displayName => $columnName) {
					$count = $prevCounts[$displayName] ?? 0;
					$total = $prevTotals[$displayName] ?? 0;
					$prevMeanScores[$displayName] = $count > 0 ? round($total / $count, 2) : '-';
				}
				$prevMeanTotalMarks = $prevTotalStudents > 0 ? round($prevTotalScore / $prevTotalStudents, 2) : '-';
			}
		}

		$deviationScores = [];
		foreach ($subjectInformation as $displayName => $columnName) {
			$prev = (is_numeric($prevMeanScores[$displayName] ?? null)) ? (float)$prevMeanScores[$displayName] : null;
			$curr = (is_numeric($meanScores[$displayName] ?? null)) ? (float)$meanScores[$displayName] : null;
			$deviationScores[$displayName] = ($prev !== null && $curr !== null)
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
				'subjects' => array_keys($subjectInformation),
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
