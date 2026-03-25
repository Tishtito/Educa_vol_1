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
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return false;
        }

        return true;
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function subjects(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        try {
            $rows = $this->db->select('subjects', ['subject_id', 'name'], [
                'ORDER' => ['name' => 'ASC'],
            ]);

            $this->jsonResponse(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('[ExaminerController] subjects error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load subjects'], 500);
        }
    }

    public function classes(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        try {
            $rows = $this->db->select('classes', ['class_id', 'class_name', 'grade', 'year'], [
                'ORDER' => ['class_name' => 'ASC'],
            ]);

            $this->jsonResponse(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('[ExaminerController] classes error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load classes'], 500);
        }
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
                $examiner = $this->db->get('examiners', '*', [
                    'examiner_id' => $examinerId,
                ]);

                if (!$examiner) {
                    $this->jsonResponse(['success' => false, 'message' => 'Examiner not found'], 404);
                    return;
                }

                // Get assigned subject-class combinations
                $assignments = $this->db->select('examiner_subject_classes', 
                    ['subject_id', 'class_id'], 
                    ['examiner_id' => $examinerId]
                ) ?? [];

                $this->jsonResponse([
                    'success' => true,
                    'data' => [
                        'examiner_id' => (int)$examiner['examiner_id'],
                        'name' => $examiner['name'],
                        'username' => $examiner['username'],
                        'assignments' => $assignments,
                    ],
                ]);
        } catch (\Throwable $e) {
            error_log('[ExaminerController] detail error: ' . $e->getMessage());
            error_log('[ExaminerController] detail trace: ' . $e->getTraceAsString());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load examiner details'], 500);
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
        $assignments = $_POST['assignments'] ?? [];  // Array of {subject_id, class_id}

        if ($examinerId <= 0 || $name === '') {
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
            ], ['examiner_id' => $examinerId]);

            // Delete old assignments
            $this->db->delete('examiner_subject_classes', ['examiner_id' => $examinerId]);

            // Insert new assignments
            if (is_array($assignments)) {
                foreach ($assignments as $assignmentJson) {
                    // Decode JSON-stringified assignment from FormData
                    $assignment = is_string($assignmentJson) ? json_decode($assignmentJson, true) : $assignmentJson;
                    
                    if (is_array($assignment)) {
                        $subjectId = isset($assignment['subject_id']) ? (int)$assignment['subject_id'] : 0;
                        $classId = isset($assignment['class_id']) ? (int)$assignment['class_id'] : 0;

                        if ($subjectId > 0 && $classId > 0) {
                            $this->db->insert('examiner_subject_classes', [
                                'examiner_id' => $examinerId,
                                'subject_id' => $subjectId,
                                'class_id' => $classId,
                            ]);
                        }
                    }
                }
            }

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
        $assignments = $_POST['assignments'] ?? [];  // Array of {subject_id, class_id}

        if ($name === '' || $username === '' || $password === '') {
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

        if (!is_array($assignments) || count($assignments) === 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Assign at least one subject to a class'], 400);
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
            ]);
            $examinerId = (int)$this->db->id();

            foreach ($assignments as $assignmentJson) {
                // Decode JSON-stringified assignment from FormData
                $assignment = is_string($assignmentJson) ? json_decode($assignmentJson, true) : $assignmentJson;
                
                if (is_array($assignment)) {
                    $subjectId = isset($assignment['subject_id']) ? (int)$assignment['subject_id'] : 0;
                    $classId = isset($assignment['class_id']) ? (int)$assignment['class_id'] : 0;

                    if ($subjectId > 0 && $classId > 0) {
                        $this->db->insert('examiner_subject_classes', [
                            'examiner_id' => $examinerId,
                            'subject_id' => $subjectId,
                            'class_id' => $classId,
                        ]);
                    }
                }
            }

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[ExaminerController] create error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create examiner'], 500);
        }
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
            $this->db->delete('examiner_subject_classes', ['examiner_id' => $examinerId]);
            $this->db->delete('examiners', ['examiner_id' => $examinerId]);

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[ExaminerController] delete error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete examiner'], 500);
        }
    }
}