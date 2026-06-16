<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SmsController
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

    public function results(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
        $class = isset($_GET['class']) ? trim((string)$_GET['class']) : '';

        if ($examId <= 0 || $class === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid exam or class']);
            return;
        }

        try {
            $payload = (function () use ($examId, $class) {
                $exam = $this->db->get('exams', ['exam_id', 'exam_name', 'exam_type', 'term'], [
                    'exam_id' => $examId,
                ]);

                if (!$exam) {
                    return ['error' => 'Exam not found'];
                }

                $subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Technical', 'Agriculture', 'SST', 'Science', 'Religious'];
                $subjectSelect = implode(', ', array_map(fn($subject) => "er.$subject", $subjects));
                $totalCalc = implode(' + ', array_map(fn($subject) => "COALESCE(er.$subject, 0)", $subjects));

                $sql = "SELECT s.student_id, s.name AS student_name, s.class, er.exam_id,
                    er.total_marks, er.position,
                    $subjectSelect,
                    ($totalCalc) AS computed_total
                    FROM students s
                    LEFT JOIN exam_results er ON s.student_id = er.student_id AND er.exam_id = :exam_id
                    WHERE s.class = :class AND s.deleted_at IS NULL
                    ORDER BY computed_total DESC, s.name ASC";

                $stmt = $this->db->query($sql, [
                    ':exam_id' => $examId,
                    ':class' => $class,
                ]);
                $rows = $stmt ? $stmt->fetchAll() : [];

                $data = [];
                $rank = 1;
                foreach ($rows as $row) {
                    $total = (int)($row['computed_total'] ?? 0);
                    $position = $rank;

                    $data[] = [
                        'student_id' => (int)($row['student_id'] ?? 0),
                        'student_name' => $row['student_name'] ?? '',
                        'class' => $row['class'] ?? '',
                        'exam_id' => (int)($row['exam_id'] ?? $examId),
                        'total_marks' => $total,
                        'position' => $position,
                        'English' => $row['English'] ?? null,
                        'Math' => $row['Math'] ?? null,
                        'Kiswahili' => $row['Kiswahili'] ?? null,
                        'Technical' => $row['Technical'] ?? null,
                        'Agriculture' => $row['Agriculture'] ?? null,
                        'Creative' => $row['Creative'] ?? null,
                        'Religious' => $row['Religious'] ?? null,
                        'SST' => $row['SST'] ?? null,
                        'Science' => $row['Science'] ?? null,
                    ];

                    $rank++;
                }

                return [
                    'exam' => $exam,
                    'data' => $data,
                ];
            })();

            if (isset($payload['error'])) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $payload['error']]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'exam' => $payload['exam'],
                'data' => $payload['data'],
            ]);
        } catch (\Throwable $e) {
            error_log('[SmsController] results error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to load results']);
        }
    }

    public function send(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw ?: '', true) ?: [];
        }

        $phone = isset($payload['phone']) ? trim((string)$payload['phone']) : '';
        $message = isset($payload['message']) ? trim((string)$payload['message']) : '';
        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : 0;
        $examId = isset($payload['exam_id']) ? (int)$payload['exam_id'] : 0;
        $className = isset($payload['class_name']) ? trim((string)$payload['class_name']) : '';

        if ($phone === '' || $message === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Phone and message are required']);
            return;
        }

        if (!preg_match('/^[+\d][\d\s-]{8,}$/', $phone)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
            return;
        }

        try {
            $logDir = dirname(__DIR__, 3) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }
            $logFile = $logDir . '/sms.log';
            $entry = [
                'sent_at' => date('c'),
                'phone' => $phone,
                'message' => $message,
                'student_id' => $studentId,
                'exam_id' => $examId,
                'class' => $className,
            ];
            file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            error_log('[SmsController] send error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to send SMS']);
        }
    }
}
