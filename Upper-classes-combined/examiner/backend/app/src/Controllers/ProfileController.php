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

	public function getProfile(): void
	{
		// Start session if not already started
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		header('Content-Type: application/json');

		error_log('[ProfileController::getProfile] REQUEST RECEIVED - Method: ' . $_SERVER['REQUEST_METHOD']);

		// Check if examiner is authenticated
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			error_log('[ProfileController::getProfile] UNAUTHORIZED - loggedin flag not set. Session data: ' . json_encode($_SESSION));
			http_response_code(401);
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$examiner_id = $_SESSION['id'] ?? null;

		if (!$examiner_id) {
			error_log('[ProfileController::getProfile] ERROR - Examiner ID not found in session. Session: ' . json_encode($_SESSION));
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Invalid session']);
			return;
		}

		error_log('[ProfileController::getProfile] AUTHENTICATED - Examiner ID: ' . $examiner_id);

		try {
			error_log('[ProfileController::getProfile] ATTEMPTING - Fetch examiner data for ID: ' . $examiner_id);

			// Fetch examiner profile data
			$examiner = $this->db->get('examiners', [
				'examiner_id',
				'name',
				'username',
				'class_assigned'
			], [
				'examiner_id' => $examiner_id
			]);

			if (!$examiner) {
				error_log('[ProfileController::getProfile] ERROR - Examiner not found: ID=' . $examiner_id);
				http_response_code(404);
				echo json_encode(['success' => false, 'message' => 'Examiner not found']);
				return;
			}

			error_log('[ProfileController::getProfile] SUCCESS - Examiner found: ' . json_encode($examiner));

			// Fetch total students in the assigned class
			error_log('[ProfileController::getProfile] ATTEMPTING - Count students in class: ' . $examiner['class_assigned']);
			
			$studentCount = $this->db->count('students', [
				'class' => $examiner['class_assigned']
			]);
			
			error_log('[ProfileController::getProfile] SUCCESS - Total students found: ' . $studentCount);

			echo json_encode([
				'success' => true,
				'data' => [
					'id' => $examiner['examiner_id'],
					'name' => $examiner['name'],
					'username' => $examiner['username'],
					'class_assigned' => $examiner['class_assigned'],
					'total_students' => $studentCount
				]
			]);
		} catch (\Exception $e) {
			error_log('[ProfileController::getProfile] EXCEPTION CAUGHT');
			error_log('[ProfileController::getProfile] Exception Type: ' . get_class($e));
			error_log('[ProfileController::getProfile] Exception Message: ' . $e->getMessage());
			error_log('[ProfileController::getProfile] Exception Code: ' . $e->getCode());
			error_log('[ProfileController::getProfile] Exception File: ' . $e->getFile());
			error_log('[ProfileController::getProfile] Exception Line: ' . $e->getLine());
			error_log('[ProfileController::getProfile] Stack Trace: ' . $e->getTraceAsString());
			
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Error fetching profile: ' . $e->getMessage()
			]);
		}
	}
}
