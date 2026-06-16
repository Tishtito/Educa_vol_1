<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ExaminerController
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

	public function subjects(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('subjects', ['subject_id', 'name'], [
				'ORDER' => ['name' => 'ASC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] subjects error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load subjects']);
		}
	}

	public function detail(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examinerId = isset($_GET['examiner_id']) ? (int)$_GET['examiner_id'] : 0;
		if ($examinerId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid examiner id']);
			return;
		}

		try {
			$examiner = $this->db->get('examiners', ['examiner_id', 'name', 'password'], [
				'examiner_id' => $examinerId,
			]);

			if (!$examiner) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Examiner not found']);
				return;
			}

			$subjectIds = $this->db->select('examiner_subjects', 'subject_id', [
				'examiner_id' => $examinerId,
			]) ?? [];

			$classIds = $this->db->select('examiner_classes', 'class_id', [
				'examiner_id' => $examinerId,
			]) ?? [];

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => [
					'examiner_id' => $examiner['examiner_id'],
					'name' => $examiner['name'],
					'subject_ids' => array_map('intval', $subjectIds),
					'class_ids' => array_map('intval', $classIds),
				],
			]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] detail error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load examiner']);
		}
	}

	public function update(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examinerId = isset($_POST['examiner_id']) ? (int)$_POST['examiner_id'] : 0;
		$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
		$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
		$subjects = $_POST['subjects'] ?? [];
		$classes = $_POST['classes'] ?? [];

		if ($examinerId <= 0 || $name === '') {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid input']);
			return;
		}

		$subjects = is_array($subjects) ? array_map('intval', $subjects) : [];
		$classes = is_array($classes) ? array_map('intval', $classes) : [];

		try {
			$existing = $this->db->get('examiners', ['password'], ['examiner_id' => $examinerId]);
			if (!$existing) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Examiner not found']);
				return;
			}

			$hash = $existing['password'];
			if ($password !== '') {
				$hash = password_hash($password, PASSWORD_DEFAULT);
			}

			$this->db->update('examiners', [
				'name' => $name,
				'password' => $hash,
			], ['examiner_id' => $examinerId]);

			$this->db->delete('examiner_subjects', ['examiner_id' => $examinerId]);
			foreach ($subjects as $subjectId) {
				$this->db->insert('examiner_subjects', [
					'examiner_id' => $examinerId,
					'subject_id' => $subjectId,
				]);
			}

			$this->db->delete('examiner_classes', ['examiner_id' => $examinerId]);
			foreach ($classes as $classId) {
				$this->db->insert('examiner_classes', [
					'examiner_id' => $examinerId,
					'class_id' => $classId,
				]);
			}

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] update error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update examiner']);
		}
	}

	public function create(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
		$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
		$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
		$confirm = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';
		$subjects = $_POST['subjects'] ?? [];
		$classes = $_POST['classes'] ?? [];

		if ($name === '' || $username === '' || $password === '') {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'All fields are required']);
			return;
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
			return;
		}

		if (strlen($password) < 6) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
			return;
		}

		if ($password !== $confirm) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
			return;
		}

		$subjects = is_array($subjects) ? array_map('intval', $subjects) : [];
		$classes = is_array($classes) ? array_map('intval', $classes) : [];
		if (count($subjects) === 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Select at least one subject']);
			return;
		}

		try {
			$exists = $this->db->has('examiners', ['username' => $username]);
			if ($exists) {
				http_response_code(409);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Username already taken']);
				return;
			}

			$hash = password_hash($password, PASSWORD_DEFAULT);
			$this->db->insert('examiners', [
				'name' => $name,
				'username' => $username,
				'password' => $hash,
			]);
			$examinerId = (int)$this->db->id();

			foreach ($subjects as $subjectId) {
				$this->db->insert('examiner_subjects', [
					'examiner_id' => $examinerId,
					'subject_id' => $subjectId,
				]);
			}

			foreach ($classes as $classId) {
				$this->db->insert('examiner_classes', [
					'examiner_id' => $examinerId,
					'class_id' => $classId,
				]);
			}

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] create error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to create examiner']);
		}
	}

	public function delete(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examinerId = isset($_POST['examiner_id']) ? (int)$_POST['examiner_id'] : 0;
		if ($examinerId <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid examiner id']);
			return;
		}

		try {
			$this->db->delete('examiner_subjects', ['examiner_id' => $examinerId]);
			$this->db->delete('examiner_classes', ['examiner_id' => $examinerId]);
			$this->db->delete('examiners', ['examiner_id' => $examinerId]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] delete error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to delete examiner']);
		}
	}
}
