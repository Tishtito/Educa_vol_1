<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class MssController
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
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($examId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Exam ID not specified']);
			return;
		}

		if (!$this->isValidToken('exam:' . $examId, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		$grades = $this->db->select('students', ['class'], [
			'GROUP' => 'class',
			'ORDER' => ['class' => 'ASC'],
		]);

		$mssList = [];
		foreach ($grades as $gradeRow) {
			$grade = (string)$gradeRow['class'];
			$sql = "
				SELECT 
					(SUM(COALESCE(English, 0) + COALESCE(Math, 0) + COALESCE(Kiswahili, 0) + 
					COALESCE(Creative, 0) + COALESCE(CRE, 0) + COALESCE(AgricNutri, 0) + COALESCE(SST, 0) + COALESCE(SciTech, 0)) / NULLIF(COUNT(exam_results.student_id), 0)) AS MeanScore 
				FROM exam_results
				INNER JOIN students ON exam_results.student_id = students.student_id
				WHERE students.class = :grade AND exam_results.exam_id = :exam_id
			";

			$stmt = $this->db->query($sql, [
				':grade' => $grade,
				':exam_id' => $examId,
			]);

			$row = $stmt ? $stmt->fetch() : null;
			$meanScore = 0.0;
			if ($row && isset($row['MeanScore']) && $row['MeanScore'] !== null) {
				$meanScore = round((float)$row['MeanScore'], 2);
			}

			$mssList[] = [
				'grade' => ucfirst($grade),
				'mean' => $meanScore,
			];
		}

		usort($mssList, function ($a, $b) {
			return $b['mean'] <=> $a['mean'];
		});

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $mssList,
		]);
	}
}
