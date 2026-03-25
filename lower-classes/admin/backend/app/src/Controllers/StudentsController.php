<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class StudentsController
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

		try {
			$sql = "SELECT status, COUNT(*) AS total FROM students GROUP BY status";
			$stmt = $this->db->query($sql);
			$rows = $stmt ? $stmt->fetchAll() : [];

			$active = 0;
			$finished = 0;
			foreach ($rows as $row) {
				$status = strtolower((string)($row['status'] ?? ''));
				$total = (int)($row['total'] ?? 0);
				if ($status === 'active') {
					$active = $total;
				} elseif ($status === 'finished') {
					$finished = $total;
				}
			}

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'active' => $active,
					'finished' => $finished,
				],
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] summary error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load student summary']);
		}
	}

	public function activeClasses(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$sql = "SELECT DISTINCT class FROM students WHERE status = 'Active' AND deleted_at IS NULL ORDER BY class ASC";
			$stmt = $this->db->query($sql);
			$rows = $stmt ? $stmt->fetchAll() : [];
			$classes = array_map(fn($row) => $row['class'], $rows);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $classes,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] activeClasses error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load active classes']);
		}
	}

	public function activeByClass(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$class = isset($_GET['class']) ? trim((string)$_GET['class']) : '';
		if ($class === '') {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Class is required']);
			return;
		}

		try {
			$sql = "SELECT student_id, name, class, created_at FROM students WHERE status = 'Active' AND class = :class AND deleted_at IS NULL ORDER BY name ASC";
			$stmt = $this->db->query($sql, [':class' => $class]);
			$rows = $stmt ? $stmt->fetchAll() : [];

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] activeByClass error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load active students']);
		}
	}

	public function finishedYears(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$sql = "SELECT DISTINCT YEAR(finished_at) AS finished_year FROM students WHERE status = 'Finished' AND updated_at IS NOT NULL ORDER BY finished_year DESC";
			$stmt = $this->db->query($sql);
			$rows = $stmt ? $stmt->fetchAll() : [];
			$years = array_map(fn($row) => (int)$row['finished_year'], $rows);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $years,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] finishedYears error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load finished years']);
		}
	}

	public function finishedByYear(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$year = isset($_GET['year']) ? trim((string)$_GET['year']) : '';
		if (!preg_match('/^\d{4}$/', $year)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid year']);
			return;
		}

		try {
			$sql = "SELECT student_id, name, class, finished_at FROM students WHERE status = 'Finished' AND YEAR(updated_at) = :year ORDER BY updated_at DESC";
			$stmt = $this->db->query($sql, [':year' => (int)$year]);
			$rows = $stmt ? $stmt->fetchAll() : [];

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] finishedByYear error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load finished students']);
		}
	}

	public function detail(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		if ($studentId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student id']);
			return;
		}

		try {
			$student = $this->db->get('students', ['student_id', 'name', 'class', 'status', 'created_at', 'updated_at'], [
				'student_id' => $studentId,
			]);

			if (!$student) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student not found']);
				return;
			}

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $student,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] detail error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load student']);
		}
	}

	public function profile(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		if ($studentId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student id']);
			return;
		}

		try {
			$student = $this->db->get('students', ['student_id', 'name', 'class', 'status', 'created_at', 'updated_at'], [
				'student_id' => $studentId,
			]);

			if (!$student) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student not found']);
				return;
			}

			$countRow = $this->db->query(
				"SELECT COUNT(*) AS total FROM exam_results WHERE student_id = :student_id AND deleted_at IS NULL",
				[':student_id' => $studentId]
			)->fetch();
			$resultsCount = (int)($countRow['total'] ?? 0);

			$lastRow = $this->db->query(
				"SELECT er.created_at, e.exam_name FROM exam_results er LEFT JOIN exams e ON er.exam_id = e.exam_id WHERE er.student_id = :student_id AND er.deleted_at IS NULL ORDER BY er.created_at DESC LIMIT 1",
				[':student_id' => $studentId]
			)->fetch();

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'student' => $student,
					'results_count' => $resultsCount,
					'last_exam_name' => $lastRow['exam_name'] ?? null,
					'last_exam_date' => $lastRow['created_at'] ?? null,
				],
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] profile error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load student profile']);
		}
	}

	public function results(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
		if ($studentId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student id']);
			return;
		}

		try {
			$sql = "SELECT er.*, e.exam_name FROM exam_results er LEFT JOIN exams e ON er.exam_id = e.exam_id WHERE er.student_id = :student_id AND er.deleted_at IS NULL ORDER BY er.created_at DESC";
			$stmt = $this->db->query($sql, [':student_id' => $studentId]);
			$rows = $stmt ? $stmt->fetchAll() : [];

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] results error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load results']);
		}
	}

	public function resultDetail(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$resultId = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
		if ($resultId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid result id']);
			return;
		}

		try {
			$sql = "SELECT er.*, s.name AS student_name, s.class AS student_class, e.exam_name FROM exam_results er JOIN students s ON er.student_id = s.student_id LEFT JOIN exams e ON er.exam_id = e.exam_id WHERE er.result_id = :result_id AND er.deleted_at IS NULL";
			$stmt = $this->db->query($sql, [':result_id' => $resultId]);
			$row = $stmt ? $stmt->fetch() : null;

			if (!$row) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Exam result not found']);
				return;
			}

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $row,
			]);
		} catch (\Throwable $e) {
			error_log('[StudentsController] resultDetail error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load exam result']);
		}
	}

	public function updateName(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$input = file_get_contents('php://input');
		$data = json_decode($input, true);

		$studentId = isset($data['student_id']) ? (int)$data['student_id'] : 0;
		$name = isset($data['name']) ? trim((string)$data['name']) : '';

		if ($studentId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid student id']);
			return;
		}

		if (empty($name)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Student name is required']);
			return;
		}

		if (strlen($name) > 255) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Student name is too long']);
			return;
		}

		try {
			$student = $this->db->get('students', 'student_id', ['student_id' => $studentId]);

			if (!$student) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Student not found']);
				return;
			}

			$this->db->update('students', ['name' => $name], ['student_id' => $studentId]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'message' => 'Student name updated successfully']);
		} catch (\Throwable $e) {
			error_log('[StudentsController] updateName error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update student name']);
		}
	}
}
