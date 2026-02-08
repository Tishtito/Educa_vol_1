<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SubjectsController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get marks for a specific subject
     */
    public function getSubjectMarks(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $classAssigned = $_SESSION['class_assigned'] ?? null;
        $examId = $_SESSION['exam_id'] ?? null;
        $subject = $_GET['subject'] ?? null;

        if (!$classAssigned || !$examId || !$subject) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }

        // Validate subject to prevent SQL injection
        $validSubjects = ['English', 'Math', 'Kiswahili', 'Creative', 'CRE', 'AgricNutri', 'SST', 'SciTech', 'Integrated_science'];
        if (!in_array($subject, $validSubjects)) {
            echo json_encode(['success' => false, 'message' => 'Invalid subject']);
            return;
        }

        try {
            // Query to fetch students and their marks for the specific subject
            $sql = "
                SELECT 
                    students.student_id AS student_id,
                    students.name AS student_name,
                    exam_results.{$subject} AS marks
                FROM 
                    students
                LEFT JOIN 
                    exam_results 
                ON 
                    students.student_id = exam_results.student_id AND exam_results.exam_id = ?
                WHERE 
                    students.class = ?
                ORDER BY students.name ASC
            ";

            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute([$examId, $classAssigned]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'subject' => $subject,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            error_log('Error in getSubjectMarks: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching subject marks']);
        }
    }

    /**
     * Get list of available subjects
     */
    public function getSubjects(): void
    {
        echo json_encode([
            'success' => true,
            'subjects' => [
                'English',
                'Math',
                'Kiswahili',
                'Creative',
                'Religious',
                'Agriculture',
                'SST',
                'Technical',
                'Science'
            ]
        ]);
    }

    /**
     * Start session if not already started
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
