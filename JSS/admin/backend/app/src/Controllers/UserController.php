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

    public function teachers(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        try {
            $rows = $this->db->select('class_teachers', ['id', 'name', 'class_assigned']);
            $this->jsonResponse(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('[UserController] teachers error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load teachers'], 500);
        }
    }

    public function examiners(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        try {
            // Fetch all examiners from the examiners table
            $examiners = $this->db->select('examiners', '*', [
                'ORDER' => ['name' => 'ASC'],
            ]);

            if (!is_array($examiners)) {
                $examiners = [];
            }

            // Build result with examiner_id and assignments
            $result = [];
            foreach ($examiners as $examiner) {
                // Extract examiner_id
                $examinerId = (int)($examiner['examiner_id'] ?? 0);
                
                if ($examinerId === 0) {
                    continue;
                }

                // Fetch assignments for this examiner (subject_id and class_id)
                $assignmentRows = $this->db->select(
                    'examiner_subject_classes',
                    ['subject_id', 'class_id'],
                    ['examiner_id' => $examinerId]
                ) ?? [];

                // Enrich assignments with subject and class names
                $assignments = [];
                foreach ($assignmentRows as $row) {
                    $subjectId = (int)($row['subject_id'] ?? 0);
                    $classId = (int)($row['class_id'] ?? 0);

                    if ($subjectId > 0) {
                        $subject = $this->db->get('subjects', 'name', ['subject_id' => $subjectId]);
                        $subjectName = $subject ?? 'Unknown Subject';
                    } else {
                        $subjectName = 'Unknown Subject';
                    }

                    if ($classId > 0) {
                        $class = $this->db->get('classes', 'class_name', ['class_id' => $classId]);
                        $className = $class ?? 'Unknown Class';
                    } else {
                        $className = 'Unknown Class';
                    }

                    $assignments[] = [
                        'subject_id' => $subjectId,
                        'class_id' => $classId,
                        'subject_name' => $subjectName,
                        'class_name' => $className,
                    ];
                }

                $result[] = [
                    'examiner_id' => $examinerId,
                    'name' => $examiner['name'] ?? '',
                    'assignments' => $assignments,
                ];
            }

            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            error_log('[UserController] examiners error: ' . $e->getMessage());
            error_log('[UserController] Stack trace: ' . $e->getTraceAsString());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load examiners: ' . $e->getMessage()], 500);
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

            $this->jsonResponse(['success' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('[UserController] classes error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load classes'], 500);
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
            $this->jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
            return;
        }

        try {
            $existing = $this->db->get('class_teachers', ['password'], ['id' => $id]);
            if (!$existing) {
                $this->jsonResponse(['success' => false, 'message' => 'Teacher not found'], 404);
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

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[UserController] updateTeacher error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update teacher'], 500);
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
            $this->jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
            return;
        }

        if ($password !== $confirm) {
            $this->jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
            return;
        }

        try {
            $exists = $this->db->has('class_teachers', ['username' => $username]);
            if ($exists) {
                $this->jsonResponse(['success' => false, 'message' => 'Username already taken'], 409);
                return;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->insert('class_teachers', [
                'name' => $name,
                'username' => $username,
                'password' => $hash,
                'class_assigned' => $classAssigned,
            ]);

            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[UserController] createTeacher error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create teacher'], 500);
        }
    }

    public function deleteTeacher(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid teacher id'], 400);
            return;
        }

        try {
            $this->db->delete('class_teachers', ['id' => $id]);
            $this->jsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[UserController] deleteTeacher error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete teacher'], 500);
        }
    }
}