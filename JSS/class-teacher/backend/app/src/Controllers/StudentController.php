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
                    exam_results.English, 
                    exam_results.Kiswahili, 
                    exam_results.Math, 
                    exam_results.Creative, 
                    exam_results.Technical, 
                    exam_results.Agriculture,
                    exam_results.SST,
                    exam_results.Science,
                    exam_results.Religious,
                    (exam_results.English + exam_results.Kiswahili + exam_results.Math + 
                     exam_results.Creative + exam_results.Technical + exam_results.Agriculture + exam_results.SST + exam_results.Science + exam_results.Religious) AS total_marks
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
            $this->db->update('students', ['class' => $newClass], ['student_id' => $studentId]);

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

    /*Graduate all students*/
    public function graduateAll(): void
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
        $targetClassId = isset($body['target_class']) ? trim($body['target_class']) : null;
        $classAssigned = $_SESSION['class_assigned'] ?? null;

        if (!$targetClassId || !$classAssigned) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        try {
            $now = date("Y-m-d H:i:s");
            $academicYear = date("Y");
            $count = 0;

            // ✅ If FINISHED option is selected
            if ($targetClassId === "FINISHED") {
                // Get all students in the current class
                $students = $this->db->select('students', ['student_id'], ['class' => $classAssigned]);
                
                foreach ($students as $student) {
                    $studentId = is_array($student) ? $student['student_id'] : $student;
                    
                    // Update status, class, updated_at and finished_at
                    $this->db->update('students', [
                        'status' => 'Finished',
                        'class' => 'Completed',
                        'updated_at' => $now,
                        'finished_at' => date("Y-01-01 00:00:00")
                    ], ['student_id' => $studentId]);
                    
                    $count++;
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "$count students marked as Finished and class updated to Completed."
                ]);
                return;
            }

            // ✅ Normal graduation flow - target_class_id is numeric
            if (!is_numeric($targetClassId)) {
                throw new \Exception("Target class id is not numeric: " . $targetClassId);
            }

            $id = (int)$targetClassId;
            
            // Get the target class name
            $targetClass = $this->db->get('classes', 'class_name', ['class_id' => $id]);
            if (!$targetClass) {
                throw new \Exception("Target class not found for id: $id");
            }
            
            $targetClassName = $targetClass;

            // Get all students in the current class
            $students = $this->db->select('students', ['student_id'], ['class' => $classAssigned]);
            
            foreach ($students as $student) {
                $studentId = is_array($student) ? $student['student_id'] : $student;

                // Update class, status and updated_at
                $this->db->update('students', [
                    'class' => $targetClassName,
                    'status' => 'Active',
                    'updated_at' => $now
                ], ['student_id' => $studentId]);

                // Insert into student_classes history
                $this->db->insert('student_classes', [
                    'student_id' => $studentId,
                    'class' => $targetClassName,
                    'academic_year' => $academicYear
                ]);

                $count++;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "$count students graduated to $targetClassName"
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error graduating students: ' . $e->getMessage()
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

        if (!isset($input['name']) || !isset($input['pno'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student name and parent phone number are required']);
            return;
        }

        $studentName = trim($input['name']);
        $parentPhone = trim($input['pno']);

        if (empty($studentName)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Student name cannot be empty']);
            return;
        }

        if (empty($parentPhone)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parent phone number cannot be empty']);
            return;
        }

        try {
            // Insert new student with teacher's assigned class, Active status, and current timestamp
            $result = $this->db->insert('students', [
                'name' => $studentName,
                'pno' => $parentPhone,
                'class' => $classAssigned,
                'status' => 'Active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result->rowCount() > 0) {
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