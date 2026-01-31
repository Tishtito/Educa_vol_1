<?php
session_start();
require_once "db/database.php";

// Check if the exam_id is passed in the URL
if (isset($_GET['exam_id'])) {
    $exam_id = $_GET['exam_id'];

    // Store the selected exam ID in the session
    $_SESSION['exam_id'] = $exam_id;

    // Redirect to the home page or any other page you want
    header("Location: home.php");
    exit;
} else {
    echo "Error: No exam ID provided.";
}
?>
