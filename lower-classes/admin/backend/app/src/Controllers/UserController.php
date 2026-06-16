<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class UserController
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

	public function teachers(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('class_teachers', ['id', 'name', 'class_assigned']);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[UserController] teachers error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load teachers']);
		}
	}

	public function examiners(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('examiners', ['examiner_id', 'name', 'class_assigned']);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[UserController] examiners error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load examiners']);
		}
	}

	public function classes(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('classes', ['class_id', 'class_name'], [
				'ORDER' => ['class_name' => 'ASC'],
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $rows,
			]);
		} catch (\Throwable $e) {
			error_log('[UserController] classes error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to load classes']);
		}
	}

	public function updateTeacher(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
		$classAssigned = isset($_POST['class_assigned']) ? trim((string)$_POST['class_assigned']) : '';
		$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

		if ($id <= 0 || $name === '') {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid input']);
			return;
		}

		try {
			$existing = $this->db->get('class_teachers', ['password'], ['id' => $id]);
			if (!$existing) {
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Teacher not found']);
				return;
			}

			$hash = $existing['password'];
			if ($password !== '') {
				$hash = password_hash($password, PASSWORD_DEFAULT);
			}

			$this->db->update('class_teachers', [
				'name' => $name,
				'class_assigned' => $classAssigned,
				'password' => $hash,
			], ['id' => $id]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[UserController] updateTeacher error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to update teacher']);
		}
	}

	public function createTeacher(): void
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
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'All fields are required']);
			return;
		}

		if ($password !== $confirm) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
			return;
		}

		try {
			$exists = $this->db->has('class_teachers', ['username' => $username]);
			if ($exists) {
				http_response_code(409);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Username already taken']);
				return;
			}

			$hash = password_hash($password, PASSWORD_DEFAULT);
			$this->db->insert('class_teachers', [
				'name' => $name,
				'username' => $username,
				'password' => $hash,
				'class_assigned' => $classAssigned,
			]);

			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[UserController] createTeacher error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to create teacher']);
		}
	}

	public function deleteTeacher(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if ($id <= 0) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid teacher id']);
			return;
		}

		try {
			$this->db->delete('class_teachers', ['id' => $id]);
			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
		} catch (\Throwable $e) {
			error_log('[UserController] deleteTeacher error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Failed to delete teacher']);
		}
	}
}
