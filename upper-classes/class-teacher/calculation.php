<!-- <!-- <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "upper_classes";
    
    $connection = new mysqli($servername, $username, $password, $database);

    if($connection->connect_error){
    die("connection failed:".$connection->connect_error);
    }
?> -->

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

function calculateMeanScores($conn, $exam_id, $class_assigned) {
    $subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'SciTech', 'AgricNutri', 'SST', 'CRE'];
    
    // Initialize arrays
    $subject_totals = array_fill_keys($subjects, 0);
    $subject_counts = array_fill_keys($subjects, 0);
    $total_score = 0;
    $total_students = 0;

    // Fetch student marks
    $sql = "SELECT * FROM exam_results 
            WHERE exam_id = ? AND student_id IN 
            (SELECT student_id FROM students WHERE class = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $exam_id, $class_assigned);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        foreach ($subjects as $subject) {
            if ($row[$subject] !== null) {
                $subject_totals[$subject] += $row[$subject];
                $subject_counts[$subject]++;
            }
        }
        if ($row['total_marks'] > 0) {
            $total_score += $row['total_marks'];
            $total_students++;
        }
    }

    // Calculate means
    $means = [];
    foreach ($subjects as $subject) {
        $means[$subject] = $subject_counts[$subject] > 0 
            ? round($subject_totals[$subject] / $subject_counts[$subject], 2) 
            : 0;
    }
    
    $means['total_mean'] = $total_students > 0 
        ? round($total_score / $total_students, 2) 
        : 0;

    return $means;
}



?>