<?php
// update_class.php
require_once "db/database.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $new_class = $_POST['new_class'];
    
    try {
        // Update the student's class
        $stmt = $conn->prepare("UPDATE students SET class = ? WHERE student_id = ?");
        $stmt->bind_param("si", $new_class, $student_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => "Class updated successfully"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Error updating class: " . $conn->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => "Database error: " . $e->getMessage()
        ]);
    }
    exit();
}
?>