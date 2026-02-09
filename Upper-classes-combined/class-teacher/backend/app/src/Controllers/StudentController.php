<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class StudentController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get all students for the teacher's class
     * Uses the exact original query structure
     */
    public function getStudents(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $classAssigned = $_SESSION['class_assigned'] ?? null;
        $examId = $_SESSION['exam_id'] ?? null;

        if (!$classAssigned) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No class assigned']);
            return;
        }

        if (!$examId) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No exam selected']);
            return;
        }

        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

        try {
            // Use exact original SQL query
            $sql = "
                SELECT 
                    students.student_id AS student_id, 
                    students.name,
                    students.class, 
                    exam_results.Math, 
                    exam_results.English, 
                    exam_results.Kiswahili, 
                    exam_results.SciTech, 
                    exam_results.AgricNutri, 
                    exam_results.Creative,
                    exam_results.CRE,
                    exam_results.SST,
                    (exam_results.Math + exam_results.English + exam_results.Kiswahili + 
                     exam_results.SciTech + exam_results.AgricNutri + exam_results.Creative + exam_results.CRE + exam_results.SST) AS total_marks
                FROM students
                LEFT JOIN exam_results 
                    ON students.student_id = exam_results.student_id 
                    AND exam_results.exam_id = ?
                WHERE students.class = ?";

            if (!empty($searchTerm)) {
                $sql .= " AND students.name LIKE ?";
            }

            $sql .= " ORDER BY students.name ASC";

            // Execute using PDO via Medoo
            $pdo = $this->db->pdo;
            $stmt = $pdo->prepare($sql);
            
            if (!empty($searchTerm)) {
                $stmt->execute([$examId, $classAssigned, '%' . $searchTerm . '%']);
            } else {
                $stmt->execute([$examId, $classAssigned]);
            }
            
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'students' => $students ?? []
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error loading students: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /*Delete a student*/
    public function deleteStudent(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        // Get JSON body
        $body = json_decode(file_get_contents('php://input'), true);
        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : null;

        if (!$studentId) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student ID required']);
            return;
        }

        try {
            // Delete exam results first
            $this->db->delete('exam_results', ['student_id' => $studentId]);
            
            // Delete student classes
            $this->db->delete('student_classes', ['student_id' => $studentId]);
            
            // Delete student
            $this->db->delete('students', ['student_id' => $studentId]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting student: ' . $e->getMessage()
            ]);
        }
    }

    /* Update student class */
    public function updateClass(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        // Get JSON body
        $body = json_decode(file_get_contents('php://input'), true);
        $studentId = isset($body['student_id']) ? (int)$body['student_id'] : null;
        $newClass = isset($body['new_class']) ? trim($body['new_class']) : null;

        if (!$studentId || !$newClass) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $currentYear = date('Y');

            // Update student's class
            $this->db->update('students', ['class' => $newClass], ['student_id' => $studentId]);

            // Record class change in student_classes table
            $this->db->insert('student_classes', [
                'student_id' => $studentId,
                'class' => $newClass,
                'academic_year' => $currentYear,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Class updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error updating class: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all classes
     */
    public function getClasses(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $classes = $this->db->select('classes', ['class_id', 'class_name'], ['ORDER' => ['grade' => 'ASC']]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'classes' => $classes ?? []
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching classes: ' . $e->getMessage()
            ]);
        }
    }

    /*Create a new student*/
    public function createStudent(): void
    {
        $this->startSession();
        
        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        // Only allow class teachers to add students to their own class
        $classAssigned = $_SESSION['class_assigned'] ?? null;
        
        if (!$classAssigned) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No class assigned to teacher']);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // PNO (Parent Phone Number) is not required until SMS module is implemented, so we can skip validation for it (|| !input['pno'])
        if (!isset($input['name']) ) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student name and parent phone number are required']);
            return;
        }

        $studentName = trim($input['name']);
        // $parentPhone = trim($input['pno']);

        if (empty($studentName)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student name cannot be empty']);
            return;
        }

        // if (empty($parentPhone)) {
        //     http_response_code(400);
        //     header('Content-Type: application/json');
        //     echo json_encode(['success' => false, 'message' => 'Parent phone number cannot be empty']);
        //     return;
        // }

        try {
            $now = date('Y-m-d H:i:s');
            $currentYear = date('Y');

            // Insert new student with teacher's assigned class, Active status, and current timestamp
            $result = $this->db->insert('students', [
                'name' => $studentName,
                // 'pno' => $parentPhone,
                'class' => $classAssigned,
                'status' => 'Active',
                'created_at' => $now
            ]);

            if ($result->rowCount() > 0) {
                // Get the newly inserted student's ID
                $studentId = $this->db->pdo->lastInsertId();
                
                // Insert record into student_classes table for academic year tracking
                $this->db->insert('student_classes', [
                    'student_id' => $studentId,
                    'class' => $classAssigned,
                    'academic_year' => $currentYear,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "$studentName has been added as a new student"
                ]);
            } else {
                throw new \Exception('Failed to insert student record');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error creating student: ' . $e->getMessage()
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