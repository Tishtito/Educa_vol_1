<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "lower_classes";
    
    $connection = new mysqli($servername, $username, $password, $database);

    if($connection->connect_error){
    die("connection failed:".$connection->connect_error);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>register</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<?php

$class_assigned = $_SESSION['class_assigned'] ?? null;

if ($class_assigned) {
    // Proceed with the query
} else {
    // Display alert and redirect to logout if class is not assigned
    echo "<script>
            swal({
                title: 'Class not assigned!',
                text: 'Visit Admin for assistance!',
                icon: 'warning',
                button: 'OK',
            }).then(function() {
                window.location.href = 'logout.php'; // Redirect after the alert
            });
          </script>";
    exit(); // Stop further execution of the script
}
?>
</html>

<?php

$exam_id = $_SESSION['exam_id'];

// SQL query to calculate mean for each subject for the selected exam and class
$sql = "
    SELECT 
        AVG(COALESCE(exam_results.English, 0)) AS EnglishMean,
        AVG(COALESCE(exam_results.Kiswahili, 0)) AS KiswahiliMean,
        AVG(COALESCE(exam_results.Math, 0)) AS MathMean,
        AVG(COALESCE(exam_results.Creative, 0)) AS CreativeMean,
        AVG(COALESCE(exam_results.Religious, 0)) AS ReligiousMean,
        AVG(COALESCE(exam_results.Enviromental, 0)) AS EnviromentalMean
    FROM 
        exam_results 
    INNER JOIN 
        students  ON exam_results.student_id = students.student_id
    WHERE 
        students.class = ? AND exam_results.exam_id = ?
";

$stmt = $connection->prepare($sql);
$stmt->bind_param("si", $class_assigned, $exam_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $english_mean = round($row['EnglishMean'], 2);
    $kiswahili_mean = round($row['KiswahiliMean'], 2);
    $math_mean = round($row['MathMean'], 2);
    $creative_mean = round($row['CreativeMean'], 2);
    $religious_mean = round($row['ReligiousMean'], 2);
    $enviromental_mean = round($row['EnviromentalMean'], 2);
} else {
    $english_mean = $kiswahili_mean = $math_mean = $creative_mean = $religious_mean = $enviromental_mean = 0;
}

//calculate of MSS

$sql = "
    SELECT 
        students.student_id AS student_id, 
        students.Name AS student_name, 
        exam_results.English, 
        exam_results.Math, 
        exam_results.Kiswahili, 
        exam_results.Creative, 
        exam_results.Religious,
        exam_results.Enviromental,
        (COALESCE(exam_results.English, 0) + COALESCE(exam_results.Math, 0) + COALESCE(exam_results.Kiswahili, 0) + 
         COALESCE(exam_results.Creative, 0) + COALESCE(exam_results.Religious, 0) + COALESCE(exam_results.Enviromental, 0)) AS Total
    FROM 
        students 
    JOIN 
        exam_results  ON students.student_id = exam_results.student_id
    WHERE 
        students.class = ? AND 
        exam_results.exam_id = ?
    ORDER BY 
        Total DESC;
";

// Prepare and execute the statement
$stmt = $connection->prepare($sql);
$stmt->bind_param("si", $class_assigned, $exam_id); // Bind class_assigned and exam_id
$stmt->execute();
$result = $stmt->get_result();

$totalMarks = 0; // Total of all student marks
$studentCount = 0; // Number of students

// Fetch data
while ($row = $result->fetch_assoc()) {
    $totalMarks += $row['Total']; // Add each student's total to totalMarks
    $studentCount++; // Increment student count
}

// Calculate the mean score
if ($studentCount > 0) {
    $meanScore = $totalMarks / $studentCount;
} else {
    $meanScore = 0; // In case no students are found
}


?>