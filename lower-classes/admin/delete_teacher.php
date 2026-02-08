<?php

session_start();
require_once "db/database.php";

// Check if 'id' is passed in the URL
if (isset($_GET['id'])) {
    $teacher_id = $_GET['id'];

    // Delete teacher based on their ID
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);

    // Check if the deletion was successful
    if ($stmt->execute()) {
        // Redirect after successful deletion
        header("Location: users.php?message=Teacher deleted successfully");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    // Redirect if no teacher ID is provided
    header("Location: users.php?message=No teacher selected for deletion");
    exit();
}

$conn->close();
?>
