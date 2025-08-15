<?php

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "uppersystem";
    
    $connection = new mysqli($servername, $username, $password, $database);
    
    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    // SQL query to calculate the mean score for each subject across grade_1, grade_2, and grade_3
    $sql = "
    SELECT 
        exams.exam_name AS exam_id,
        students.class AS grade,
        'Mathematics' AS subject,
        AVG(COALESCE(exam_results.Math, 0)) AS mean_score,
        COUNT(DISTINCT students.student_id) AS total_students
    FROM 
        students
    LEFT JOIN 
        exam_results ON students.student_id = exam_results.student_id
    LEFT JOIN 
        exams  ON exam_results.exam_id = exams.exam_id
    GROUP BY 
        exams.exam_name, students.class, subject

    UNION ALL

    SELECT 
        exams.exam_name AS exam_id,
        students.class AS grade,
        'English' AS subject,
        AVG(COALESCE(exam_results.English, 0)) AS mean_score,
        COUNT(DISTINCT students.student_id) AS total_students
    FROM 
        students
    LEFT JOIN 
        exam_results ON students.student_id = exam_results.student_id
    LEFT JOIN 
        exams  ON exam_results.exam_id = exams.exam_id
    GROUP BY 
        exams.exam_name, students.class, subject

    UNION ALL

    SELECT 
        exams.exam_name AS exam_id,
        students.class AS grade,
        'Kiswahili' AS subject,
        AVG(COALESCE(exam_results.Kiswahili, 0)) AS mean_score,
        COUNT(DISTINCT students.student_id) AS total_students
    FROM 
        students
    LEFT JOIN 
        exam_results ON students.student_id = exam_results.student_id
    LEFT JOIN 
        exams ON exam_results.exam_id = exams.exam_id
    GROUP BY 
        exams.exam_name, students.class, subject

    UNION ALL

    SELECT 
        exams.exam_name AS exam_id,
        students.class AS grade,
        'Creative' AS subject,
        AVG(COALESCE(exam_results.Creative, 0)) AS mean_score,
        COUNT(DISTINCT students.student_id) AS total_students
    FROM 
        students
    LEFT JOIN 
        exam_results ON students.student_id = exam_results.student_id
    LEFT JOIN 
        exams ON exam_results.exam_id = exams.exam_id
    GROUP BY 
        exams.exam_name, students.class, subject
    
    UNION ALL

    SELECT 
        exams.exam_name AS exam_id,
        students.class AS grade,
        'Agriculture' AS subject,
        AVG(COALESCE(exam_results.Kiswahili, 0)) AS mean_score,
        COUNT(DISTINCT students.student_id) AS total_students
    FROM 
        students
    LEFT JOIN 
        exam_results ON students.student_id = exam_results.student_id
    LEFT JOIN 
        exams ON exam_results.exam_id = exams.exam_id
    GROUP BY 
        exams.exam_name, students.class, subject;

    
";

$result = $connection->query($sql);

if (!$result) {
    die("Query failed: " . $connection->error);
}

$examSubjects = [];
while ($row = $result->fetch_assoc()) {
    $examSubjects[$row['exam_id']][] = $row;
}


$connection->close();
    
?>