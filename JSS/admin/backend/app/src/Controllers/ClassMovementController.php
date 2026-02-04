<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ClassMovementController
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

    private function getPayload(): array
    {
        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw ?: '', true) ?: [];
        }
        return $payload;
    }

    public function moveAll(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $payload = $this->getPayload();
        $fromClass = trim((string)($payload['from_class'] ?? ''));
        $targetClass = trim((string)($payload['target_class'] ?? ''));

        if ($fromClass === '' || $targetClass === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'From and target classes are required']);
            return;
        }

        if ($fromClass === $targetClass) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Target class must be different']);
            return;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $result = $this->db->update('students', [
                'class' => $targetClass,
                'updated_at' => $now,
            ], [
                'class' => $fromClass,
                'status' => 'Active',
            ]);

            $count = $result ? $result->rowCount() : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "$count students moved to $targetClass",
            ]);
        } catch (\Throwable $e) {
            error_log('[ClassMovementController] moveAll error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to move class']);
        }
    }

    public function moveStudent(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $payload = $this->getPayload();
        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : 0;
        $targetClass = trim((string)($payload['target_class'] ?? ''));

        if ($studentId <= 0 || $targetClass === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student and target class are required']);
            return;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $result = $this->db->update('students', [
                'class' => $targetClass,
                'updated_at' => $now,
            ], ['student_id' => $studentId]);

            $count = $result ? $result->rowCount() : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $count > 0 ? 'Student moved successfully' : 'No student updated',
            ]);
        } catch (\Throwable $e) {
            error_log('[ClassMovementController] moveStudent error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to move student']);
        }
    }

    public function graduateAll(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $payload = $this->getPayload();
        $fromClass = trim((string)($payload['from_class'] ?? ''));
        $targetClass = trim((string)($payload['target_class'] ?? ''));

        if ($fromClass === '' || $targetClass === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'From and target classes are required']);
            return;
        }

        try {
            $now = date('Y-m-d H:i:s');

            if ($targetClass === 'FINISHED') {
                $result = $this->db->update('students', [
                    'status' => 'Finished',
                    'class' => 'Completed',
                    'updated_at' => $now,
                    'finished_at' => $now,
                ], [
                    'class' => $fromClass,
                    'status' => 'Active',
                ]);

                $count = $result ? $result->rowCount() : 0;

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "$count students marked as Finished.",
                ]);
                return;
            }

            $result = $this->db->update('students', [
                'class' => $targetClass,
                'updated_at' => $now,
            ], [
                'class' => $fromClass,
                'status' => 'Active',
            ]);

            $count = $result ? $result->rowCount() : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "$count students graduated to $targetClass",
            ]);
        } catch (\Throwable $e) {
            error_log('[ClassMovementController] graduateAll error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to graduate class']);
        }
    }
}
