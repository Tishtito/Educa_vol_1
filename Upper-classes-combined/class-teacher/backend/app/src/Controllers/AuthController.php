<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class AuthController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	public function login(): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);
			return;
		}

		$this->startSession();

		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
		$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

		header('Content-Type: application/json');

		if ($username === '' || $password === '') {
			$this->clearSession();
			error_log('[AUTH] Empty username or password');
			echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
			return;
		}

		// Query class_teachers table instead of admins
		$teacher = $this->db->get('class_teachers', ['id', 'name', 'username', 'password', 'class_assigned'], ['username' => $username]);

		$hasTeacher = (bool)$teacher;
		$validPassword = $hasTeacher && isset($teacher['password']) && password_verify($password, $teacher['password']);
		if (!$validPassword) {
			$this->clearSession();
			error_log('[AUTH] Invalid credentials for username=' . $username . ' teacherFound=' . ($hasTeacher ? '1' : '0'));
			echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
			return;
		}

		session_regenerate_id(true);
		$_SESSION['loggedin'] = true;
		$_SESSION['id'] = $teacher['id'];
		$_SESSION['username'] = $teacher['username'];
		$_SESSION['name'] = $teacher['name'];
		$_SESSION['class_assigned'] = $teacher['class_assigned'];
		error_log('[AUTH] Login success username=' . $teacher['username']);

		echo json_encode(['success' => true, 'message' => 'Login successful.']);
	}

	public function check(): void
	{
		$this->startSession();
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		header('Content-Type: application/json');
		echo json_encode([
			'success' => isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true,
			'username' => $_SESSION['username'] ?? null,
			'name' => $_SESSION['name'] ?? null,
			'id' => $_SESSION['id'] ?? null,
			'class_assigned' => $_SESSION['class_assigned'] ?? null,
		]);
	}

	public function getClasses(): void
	{
		header('Content-Type: application/json');
		try {
			$classes = $this->db->select('classes', ['class_id', 'class_name'], 
				['ORDER' => ['grade' => 'ASC']]
			);
			echo json_encode([
				'success' => true,
				'classes' => $classes ?? []
			]);
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Error fetching classes: ' . $e->getMessage()
			]);
		}
	}

	public function logout(): void
	{
		$this->startSession();
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$this->clearSession();
		header('Location: ' . $this->basePath() . '/pages/index.html');
	}

	private function startSession(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		session_set_cookie_params([
			'path' => $this->basePath() ?: '/',
			'httponly' => true,
			'samesite' => 'Lax',
		]);
	}

	private function basePath(): string
	{
		$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
		$marker = '/backend/public/index.php';
		if ($scriptName !== '' && strlen($scriptName) >= strlen($marker)) {
			if (substr($scriptName, -strlen($marker)) === $marker) {
				$base = substr($scriptName, 0, -strlen($marker));
				return $base !== '' ? $base : '';
			}
		}

		$dir = dirname($scriptName);
		return $dir === '/' ? '' : $dir;
	}

	private function clearSession(): void
	{
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}
		session_destroy();
	}
}
