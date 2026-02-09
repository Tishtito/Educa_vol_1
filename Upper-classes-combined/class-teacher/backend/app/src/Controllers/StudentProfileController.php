<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class StudentProfileController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get student profile with marks for selected exam
     */
    public function getStudentProfile(): void
    {
        $this->startSession();
        
        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[StudentProfileController::getStudentProfile] UNAUTHORIZED - loggedin flag not set');
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        // Get student ID from query parameter
        $studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$studentId) {
            error_log('[StudentProfileController::getStudentProfile] ERROR - Student ID is required but not provided');
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }

        // Get exam ID from session
        $examId = $_SESSION['exam_id'] ?? null;

        if (!$examId) {
            error_log('[StudentProfileController::getStudentProfile] ERROR - No exam ID in session');
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No exam selected']);
            return;
        }

        try {
            // Fetch student details and exam results
            $student = $this->db->select('students', '*', ['student_id' => $studentId]);
            
            if (!$student || count($student) === 0) {
                error_log('[StudentProfileController::getStudentProfile] ERROR - Student not found: ID=' . $studentId);
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                return;
            }

            // Handle both array and single result
            $studentData = is_array($student[0] ?? null) ? $student[0] : $student;

            // Fetch exam results for this student
            $examResults = $this->db->select('exam_results', '*', [
                'student_id' => $studentId,
                'exam_id' => $examId
            ]);

            $marks = [];
            if ($examResults) {
                $examResult = is_array($examResults[0] ?? null) ? $examResults[0] : $examResults;
                $marks = [
                    'Math' => $examResult['Math'] ?? null,
                    'English' => $examResult['English'] ?? null,
                    'Kiswahili' => $examResult['Kiswahili'] ?? null,
                    'SciTech' => $examResult['SciTech'] ?? null,
                    'AgricNutri' => $examResult['AgricNutri'] ?? null,
                    'Creative' => $examResult['Creative'] ?? null,
                    'CRE' => $examResult['CRE'] ?? null,
                    'SST' => $examResult['SST'] ?? null
                ];
            }

            // Calculate total marks
            $totalMarks = array_sum(array_filter($marks, fn($val) => $val !== null));

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'student' => [
                    'student_id' => $studentData['student_id'],
                    'name' => $studentData['name'],
                    'class' => $studentData['class'],
                    'status' => $studentData['status'] ?? 'Active'
                ],
                'marks' => $marks,
                'total_marks' => $totalMarks
            ]);
        } catch (\Exception $e) {
            error_log('[StudentProfileController::getStudentProfile] EXCEPTION - ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching student profile: ' . $e->getMessage()
            ]);
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
