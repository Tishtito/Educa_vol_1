<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ExamController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    // GET /exams - list all scheduled exams
    public function getExams(): void
    {
        $this->startSession();
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        try {
            $exams = $this->db->select('exams', '*', ['status' => 'Scheduled', 'ORDER' => ['date_created' => 'DESC']]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'exams' => $exams]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST /exams/select - set exam_id in session
    public function selectExam(): void
    {
        $this->startSession();
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $examId = $input['exam_id'] ?? null;
        if (!$examId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No exam_id provided']);
            return;
        }
        $_SESSION['exam_id'] = $examId;
        echo json_encode(['success' => true, 'message' => 'Exam selected']);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
