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

	public function summary(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$totalStudents = (int)($this->db->count('students'));
		$totalExaminers = (int)($this->db->count('examiners'));

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => [
				'total_students' => $totalStudents,
				'total_examiners' => $totalExaminers,
			],
		]);
	}

	public function topExams(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$sql = "
			SELECT 
				exams.exam_name AS name,
				DATE_FORMAT(exams.date_created, '%Y-%m-%d') AS date,
				COUNT(exam_results.student_id) AS total_students
			FROM 
				exams
			LEFT JOIN 
				exam_results 
			ON 
				exams.exam_id = exam_results.exam_id
			GROUP BY 
				exams.exam_id
			ORDER BY 
				exams.date_created DESC
		";

		$stmt = $this->db->query($sql);
		$rows = $stmt ? $stmt->fetchAll() : [];

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $rows,
		]);
	}

	public function exams(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$rows = $this->db->select('exams', ['exam_id', 'exam_name'], [
			'ORDER' => ['date_created' => 'DESC'],
		]);

		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'data' => $rows,
		]);
	}
}
