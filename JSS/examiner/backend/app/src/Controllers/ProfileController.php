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

		// Check if examiner is authenticated
		if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
			http_response_code(401);
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			return;
		}

		$examiner_id = $_SESSION['id'] ?? null;

		if (!$examiner_id) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Invalid session']);
			return;
		}

		try {
			// Fetch examiner profile data
			$examiner = $this->db->get('examiners', [
				'id',
				'name',
				'username',
				'email',
				'phone'
			], [
				'id' => $examiner_id
			]);

			if (!$examiner) {
				http_response_code(404);
				echo json_encode(['success' => false, 'message' => 'Examiner not found']);
				return;
			}

			// Fetch assigned exams count
			$exams = $this->db->select('exams', ['exam_id'], [
				'examiner_assigned' => $examiner_id
			]);

			$total_exams = is_array($exams) ? count($exams) : 0;

			echo json_encode([
				'success' => true,
				'data' => [
					'id' => $examiner['id'],
					'name' => $examiner['name'],
					'username' => $examiner['username'],
					'email' => $examiner['email'] ?? 'N/A',
					'phone' => $examiner['phone'] ?? 'N/A',
					'total_exams' => $total_exams
				]
			]);
		} catch (\Exception $e) {
			http_response_code(500);
			echo json_encode([
				'success' => false,
				'message' => 'Error fetching profile: ' . $e->getMessage()
			]);
		}
	}
}
