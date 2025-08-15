<?php
session_start();
require_once "db/database.php";

// Set header for JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    try {
        // Begin transaction for atomic operations
        $conn->begin_transaction();
        
        // 1. First check if student exists
        $check = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $check->bind_param("i", $student_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Student not found');
        }
        
        // 2. Delete the student's exam results
        $deleteExamResultsQuery = "DELETE FROM exam_results WHERE student_id = ?";
        $stmtResults = $conn->prepare($deleteExamResultsQuery);
        $stmtResults->bind_param("i", $student_id);
        
        if (!$stmtResults->execute()) {
            throw new Exception('Error deleting exam results: ' . $conn->error);
        }
        
        // 3. Delete the student from the students table
        $deleteStudentQuery = "DELETE FROM students WHERE student_id = ?";
        $stmtStudent = $conn->prepare($deleteStudentQuery);
        $stmtStudent->bind_param("i", $student_id);
        
        if (!$stmtStudent->execute()) {
            throw new Exception('Error deleting student: ' . $conn->error);
        }
        
        // Commit the transaction if all operations succeeded
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student and all related data deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>