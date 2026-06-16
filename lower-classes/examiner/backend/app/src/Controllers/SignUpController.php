<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SignUpController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	public function register(): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);
			return;
		}

		header('Content-Type: application/json');

		$input = json_decode(file_get_contents('php://input'), true);
		
		$name = isset($input['name']) ? trim((string)$input['name']) : '';
		$username = isset($input['username']) ? trim((string)$input['username']) : '';
		$password = isset($input['password']) ? (string)$input['password'] : '';
		$confirmPassword = isset($input['confirm_password']) ? (string)$input['confirm_password'] : '';
		$role = isset($input['role']) ? trim((string)$input['role']) : '';
		$classAssigned = isset($input['class_assigned']) ? trim((string)$input['class_assigned']) : '';

		// Validation
		if (!$name || strlen($name) < 3) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Name must be at least 3 characters']);
			return;
		}

		if (!$username || strlen($username) < 3) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
			return;
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
			return;
		}

		if (!$password || strlen($password) < 6) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
			return;
		}

		if ($password !== $confirmPassword) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
			return;
		}

		if (!$role || !in_array($role, ['classTeacher', 'examiner', 'both'])) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
			return;
		}

		if (!$classAssigned) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Class assignment is required']);
			return;
		}

		// Check if username already exists in class_teachers
		$existingTeacher = $this->db->count('class_teachers', ['username' => $username]);
		if ($existingTeacher > 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Username already exists']);
			return;
		}

		// Check if username already exists in examiners
		$existingExaminer = $this->db->count('examiners', ['username' => $username]);
		if ($existingExaminer > 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Username already exists']);
			return;
		}

		try {
			$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
			$now = date('Y-m-d H:i:s');

			// Register as class teacher if role is 'classTeacher' or 'both'
			if ($role === 'classTeacher' || $role === 'both') {
				$this->db->insert('class_teachers', [
					'name' => $name,
					'username' => $username,
					'password' => $hashedPassword,
					'class_assigned' => $classAssigned,
					'created_at' => $now,
					'updated_at' => $now
				]);
			}

			// Register as examiner if role is 'examiner' or 'both'
			if ($role === 'examiner' || $role === 'both') {
				$this->db->insert('examiners', [
					'name' => $name,
					'username' => $username,
					'password' => $hashedPassword,
					'class_assigned' => $classAssigned,
					'created_at' => $now,
					'updated_at' => $now
				]);
			}

			http_response_code(201);
			echo json_encode([
				'success' => true,
				'message' => 'Account created successfully. Redirecting to login...',
				'role' => $role
			]);
		} catch (\Exception $e) {
			error_log('[SignUp::register] Error: ' . $e->getMessage());
			http_response_code(500);
			echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $e->getMessage()]);
		}
	}
}
