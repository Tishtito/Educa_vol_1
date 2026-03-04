<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ProfileController
{
	private Medoo $db;

	public function __construct(Medoo $db)
	{
		$this->db = $db;
	}

	private function log(string $message): void
	{
		$logDir = __DIR__ . '/../../../logs';
		$logFile = $logDir . '/php_errors.log';
		
		if (!is_dir($logDir)) {
			mkdir($logDir, 0777, true);
		}
		
		$entry = sprintf("[%s] %s\n", date('c'), $message);
		file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
	}

	public function getProfile(): void
	{
		// Start session if not already started
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		header('Content-Type: application/json');

		// Check if examiner is authenticated
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			$this->log('[ProfileController::getProfile] UNAUTHORIZED - loggedin flag not set');
			http_response_code(401);
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$examiner_id = $_SESSION['id'] ?? null;

		if (!$examiner_id) {
			$this->log('[ProfileController::getProfile] ERROR - Examiner ID not found in session');
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Invalid session']);
			return;
		}

		try {
			// Fetch examiner profile data
			$examiner = $this->db->get('examiners', [
				'examiner_id',
				'name',
				'username'
			], [
				'examiner_id' => $examiner_id
			]);

			if (!$examiner) {
				$this->log('[ProfileController::getProfile] ERROR - Examiner not found: ID=' . $examiner_id);
				http_response_code(404);
				echo json_encode(['success' => false, 'message' => 'Examiner not found']);
				return;
			}

			// Fetch assigned subjects count
			$subjects = $this->db->select('examiner_subjects', ['subject_id'], [
				'examiner_id' => $examiner_id
			]);

			$total_subjects = is_array($subjects) ? count($subjects) : 0;

			echo json_encode([
				'success' => true,
				'data' => [
					'id' => $examiner['examiner_id'],
					'name' => $examiner['name'],
					'username' => $examiner['username'],
					'total_subjects' => $total_subjects
				]
			]);
		} catch (\Exception $e) {
			$this->log('[ProfileController::getProfile] EXCEPTION - ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Error fetching profile: ' . $e->getMessage()
			]);
		}
	}
}
