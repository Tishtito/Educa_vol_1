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



	public function detail(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examinerId = isset($_GET['examiner_id']) ? (int)$_GET['examiner_id'] : 0;
		if ($examinerId <= 0) {
			$this->jsonResponse(['success' => false, 'message' => 'Invalid examiner id'], 400);
			return;
		}

		try {
			$examiner = $this->db->get('examiners', ['examiner_id', 'name', 'class_assigned'], [
				'examiner_id' => $examinerId,
			]);

			if (!$examiner) {
				$this->jsonResponse(['success' => false, 'message' => 'Examiner not found'], 404);
				return;
			}

			$this->jsonResponse([
				'success' => true,
				'data' => [
					'examiner_id' => $examiner['examiner_id'],
					'name' => $examiner['name'],
					'class_assigned' => $examiner['class_assigned'],
				],
			]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] detail error: ' . $e->getMessage());
			$this->jsonResponse(['success' => false, 'message' => 'Failed to load examiner'], 500);
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
		$classAssigned = isset($_POST['class_assigned']) ? trim((string)$_POST['class_assigned']) : '';

		if ($examinerId <= 0 || $name === '' || $classAssigned === '') {
			$this->jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
			return;
		}

		try {
			$existing = $this->db->get('examiners', ['password'], ['examiner_id' => $examinerId]);
			if (!$existing) {
				$this->jsonResponse(['success' => false, 'message' => 'Examiner not found'], 404);
				return;
			}

			$hash = $existing['password'];
			if ($password !== '') {
				$hash = password_hash($password, PASSWORD_DEFAULT);
			}

			$this->db->update('examiners', [
				'name' => $name,
				'password' => $hash,
				'class_assigned' => $classAssigned,
			], ['examiner_id' => $examinerId]);

			$this->jsonResponse(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] update error: ' . $e->getMessage());
			$this->jsonResponse(['success' => false, 'message' => 'Failed to update examiner'], 500);
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
		$classAssigned = isset($_POST['class_assigned']) ? trim((string)$_POST['class_assigned']) : '';

		if ($name === '' || $username === '' || $password === '' || $classAssigned === '') {
			$this->jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
			return;
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
			$this->jsonResponse(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores'], 400);
			return;
		}

		if (strlen($password) < 6) {
			$this->jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
			return;
		}

		if ($password !== $confirm) {
			$this->jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
			return;
		}

		try {
			$exists = $this->db->has('examiners', ['username' => $username]);
			if ($exists) {
				$this->jsonResponse(['success' => false, 'message' => 'Username already taken'], 409);
				return;
			}

			$hash = password_hash($password, PASSWORD_DEFAULT);
			$this->db->insert('examiners', [
				'name' => $name,
				'username' => $username,
				'password' => $hash,
				'class_assigned' => $classAssigned,
			]);

			$this->jsonResponse(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] create error: ' . $e->getMessage());
			$this->jsonResponse(['success' => false, 'message' => 'Failed to create examiner'], 500);
		}
	}

	private function jsonResponse(array $data, int $code = 200): void
	{
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	public function delete(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examinerId = isset($_POST['examiner_id']) ? (int)$_POST['examiner_id'] : 0;
		if ($examinerId <= 0) {
			$this->jsonResponse(['success' => false, 'message' => 'Invalid examiner id'], 400);
			return;
		}

		try {
			// Verify examiner exists before deletion
			$examiner = $this->db->get('examiners', ['examiner_id'], ['examiner_id' => $examinerId]);
			if (!$examiner) {
				$this->jsonResponse(['success' => false, 'message' => 'Examiner not found'], 404);
				return;
			}

			// Delete examiner - cascade delete will handle related records
			$this->db->delete('examiners', ['examiner_id' => $examinerId]);

			$this->jsonResponse(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[ExaminerController] delete error: ' . $e->getMessage());
			$this->jsonResponse(['success' => false, 'message' => 'Failed to delete examiner'], 500);
		}
	}
}
