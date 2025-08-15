<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "upper_classes";

$connection = new mysqli($servername, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Array to store exam data
$check_exams = [];

try {
    // SQL query to fetch exam details and total students with results entered
    $sql = "
    SELECT 
        exams.exam_name AS Name,
        DATE_FORMAT(exams.date_created, '%Y-%m-%d') AS date,
        COUNT(exam_results.student_id) AS Totalstudents
    FROM 
        exams
    LEFT JOIN 
        exam_results 
    ON 
        exams.exam_id = exam_results.exam_id
    GROUP BY 
        exams.exam_id
    ORDER BY 
        exams.date_created DESC";

    // Execute the query
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch data into an array
    while ($row = $result->fetch_assoc()) {
        $check_exams[] = $row;
    }

    // Free result set and close the statement
    $result->free();
    $stmt->close();
} catch (Exception $e) {
    die("Error fetching exam data: " . $e->getMessage());
}

$connection->close();
?>
