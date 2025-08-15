<?php

session_start();
require_once "db/database.php";

// Check if 'id' is passed in the URL
if (isset($_GET['id'])) {
    $class_id = $_GET['id'];

    // Delete teacher based on their ID
    $sql = "DELETE FROM classes WHERE class_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);

    // Check if the deletion was successful
    if ($stmt->execute()) {
        // Redirect after successful deletion
        header("Location: classes.php?message=class deleted successfully");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    // Redirect if no teacher ID is provided
    header("Location: index.php?message=No class selected for deletion");
    exit();
}

$conn->close();
?>
