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

	public function index(): void
	{
		$this->startSession();

		// Check if user is authenticated
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$examinerId = $_SESSION['id'] ?? null;
		$classAssigned = $_SESSION['class_assigned'] ?? null;
		$examId = $_SESSION['exam_id'] ?? null;

		// Log session data for debugging
		error_log('[DASHBOARD] Session Data: examinerId=' . ($examinerId ? '✓' : '✗') . 
			', classAssigned=' . ($classAssigned ?? 'NULL') . 
			', examId=' . ($examId ? '✓' : '✗'));

		if (!$examinerId || !$classAssigned || !$examId) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Missing examiner, class, or exam data', 'debug' => [
				'has_id' => (bool)$examinerId,
				'has_class' => (bool)$classAssigned,
				'has_exam' => (bool)$examId
			]]);
			return;
		}

		try {
			// Get examiner data
			$examiner = $this->db->get('examiners', ['examiner_id', 'name', 'username', 'class_assigned'], ['examiner_id' => $examinerId]);

			// Get mean scores for the selected exam and class
			$means = $this->calculateMeanScores($classAssigned, $examId);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'examiner' => $examiner,
				'class_assigned' => $classAssigned,
				'exam_id' => $examId,
				'title' => ucwords(str_replace('_', ' ', $classAssigned)),
				'means' => $means,
			]);
		} catch (\Exception $e) {
			http_response_code(500);
			header('Content-Type: application/json');
			error_log('[DASHBOARD] Exception: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => 'Internal server error']);
		}
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
		session_start();
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
}
