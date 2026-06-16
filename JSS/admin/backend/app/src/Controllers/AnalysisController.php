<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class AnalysisController
{
	private Medoo $db;
	private const TOKEN_SECRET = 'educa-jss-token-v1';

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

	private function tokenKey(): string
	{
		return hash('sha256', session_id() . '|' . self::TOKEN_SECRET);
	}

	private function makeToken(string $payload): string
	{
		return hash_hmac('sha256', $payload, $this->tokenKey());
	}

	private function isValidToken(string $payload, ?string $token): bool
	{
		return $token !== null && $token !== '' && hash_equals($this->makeToken($payload), $token);
	}

	public function exams(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		try {
			$rows = $this->db->select('exams', ['exam_id', 'exam_name'], [
				'ORDER' => ['date_created' => 'DESC'],
			]);

			$data = array_map(function ($row) {
				$examId = (int)$row['exam_id'];
				return [
					'exam_id' => $examId,
					'exam_name' => $row['exam_name'],
					'token' => $this->makeToken('exam:' . $examId),
				];
			}, $rows ?: []);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $data,
			]);
		} catch (\Throwable $e) {
			error_log('[AnalysisController] exams error: ' . $e->getMessage());
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode([
				'success' => false,
				'message' => 'Failed to load exams',
			]);
		}
	}

	public function grades(): void
	{
		if (!$this->requireAuth()) {
			return;
		}

		$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
		$token = isset($_GET['token']) ? (string)$_GET['token'] : null;
		if ($examId <= 0 || !$this->isValidToken('exam:' . $examId, $token)) {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Invalid or missing token']);
			return;
		}

		try {
			$rows = $this->db->select('classes', ['class_name'], [
				'GROUP' => 'class_name',
				'ORDER' => ['class_name' => 'ASC'],
			]);

			$grades = array_map(function ($row) {
				return $row['class_name'];
			}, $rows ?: []);
			$data = array_map(function ($grade) use ($examId) {
				return [
					'grade' => $grade,
					'token' => $this->makeToken('exam:' . $examId . '|grade:' . $grade),
				];
			}, $grades);

			header('Content-Type: application/json');
			echo json_encode([
				'success' => true,
				'data' => $data,
			]);
		} catch (\Throwable $e) {
			try {
				$rows = $this->db->select('classes', ['grade'], [
					'GROUP' => 'grade',
					'ORDER' => ['grade' => 'ASC'],
				]);

				$grades = array_map(function ($row) {
					return $row['grade'];
				}, $rows ?: []);
				$data = array_map(function ($grade) use ($examId) {
					return [
						'grade' => $grade,
						'token' => $this->makeToken('exam:' . $examId . '|grade:' . $grade),
					];
				}, $grades);

				header('Content-Type: application/json');
				echo json_encode([
					'success' => true,
					'data' => $data,
				]);
			} catch (\Throwable $fallbackError) {
				error_log('[AnalysisController] grades error: ' . $e->getMessage());
				error_log('[AnalysisController] grades fallback error: ' . $fallbackError->getMessage());
				http_response_code(500);
				header('Content-Type: application/json');
				echo json_encode([
					'success' => false,
					'message' => 'Failed to load grades',
				]);
			}
		}
	}
}
