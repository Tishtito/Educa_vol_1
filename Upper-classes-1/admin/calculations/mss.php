<?php
   // Database connection
   $servername = "localhost";
   $username = "root";
   $password = "";
   $database = "upper_classes1";
   
   $connection = new mysqli($servername, $username, $password, $database);
   
   // Check connection
   if ($connection->connect_error) {
       die("Connection failed: " . $connection->connect_error);
   }


    //calculation of MSS
    if (!isset($_GET['exam_id'])) {
        die("Exam ID not specified.");
    }
    
    $exam_id = intval($_GET['exam_id']);
    $grades = []; // Grades will be dynamically fetched
    $mssList = [];

    // Fetch distinct grades from the students table
    $gradeQuery = "SELECT DISTINCT class FROM students";
    $gradeResult = $conn->query($gradeQuery);

    if ($gradeResult && $gradeResult->num_rows > 0) {
        while ($row = $gradeResult->fetch_assoc()) {
            $grades[] = $row['class'];
        }
    }

    foreach ($grades as $grade) {
        // Query to calculate the mean score for the specific grade and exam
        $sql = "
            SELECT 
                (SUM(COALESCE(English, 0) + COALESCE(Math, 0) + COALESCE(Kiswahili, 0) + 
                COALESCE(Creative, 0) + COALESCE(SciTech, 0) + COALESCE(AgricNutri, 0) + COALESCE(CRE, 0) + COALESCE(SST, 0)) / COUNT(exam_results.student_id)) AS MeanScore 
            FROM exam_results
            INNER JOIN students ON exam_results.student_id = students.student_id
            WHERE students.class = '$grade' AND exam_results.exam_id = $exam_id";
        
        $result = $conn->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            $meanScore = round($row['MeanScore'], 2);
            $mssList[] = ['grade' => ucfirst($grade), 'mean' => $meanScore];
        } else {
            $mssList[] = ['grade' => ucfirst($grade), 'mean' => 0]; // Handle cases with no data
        }
    }

    // Sort MSS by mean score in descending order
    usort($mssList, function($a, $b) {
        return $b['mean'] <=> $a['mean'];
    });

?>