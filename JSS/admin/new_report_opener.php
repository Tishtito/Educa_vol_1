<?php
    require_once "db/database.php";

    // Get student_id and exam from URL
    $student_id = $_GET['student_id'] ?? null;
    $exam_id = $_GET['exam_id'] ?? null;

    if (!$student_id || !$exam_id) {
        die("Invalid request. Student ID and Exam ID are required.");
    }

    // Fetch student details
    $query = "SELECT name, class FROM students  WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        die("Student not found.");
    }

    $class_name = $student['class']; // Get the class name

    // Fetch the class teacher (tutor) based on the class name
    $query = "SELECT name FROM class_teachers WHERE class_assigned = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $class_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_teacher = $result->fetch_assoc();

    $tutor = $class_teacher['name'] ?? "Not Assigned"; // Default if no teacher is found

    //Fetch exam's year
    $query = "SELECT term, YEAR(date_created) AS exam_year FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();

    if (!$exam) {
        die("Exam not found.");
    }

    $term = $exam['term']; 
    $exam_year = $exam['exam_year']; // Get the year of exam creation

    // Fetch exam results
    $query = "SELECT * FROM exam_results WHERE student_id = ? AND exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $student_id, $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam_results = $result->fetch_assoc();

    if (!$exam_results) {
        die("No exam results found for this student.");
    }

    // Fetch exam details
    $query = "SELECT exam_type, term FROM exams  WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();

    if (!$student) {
        die("Student not found.");
    }

    // Fetch performance levels and achievement bands
    $performance_data = [];
    $query = "SELECT min_marks, max_marks, pl, ab FROM point_boundaries";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $performance_data[] = $row;
    }

    if (!function_exists('getPerformanceData')) {
        function getPerformanceData($score, $levels) {
            foreach ($levels as $level) {
                if ($score >= $level['min_marks'] && $score <= $level['max_marks']) {
                    return [
                        'pl' => $level['pl'],
                        'ab' => $level['ab']
                    ];
                }
            }
            return [
                'pl' => "UNKNOWN",
                'ab' => "UNKNOWN"
            ];
        }
    }

    $subjects = [
        'Math' => 'Mathematics',
        'English' => 'English Language',
        'Kiswahili' => 'Kiswahili',
        'Technical' => 'Pre-Technical',
        'Agriculture' => 'Agriculture',
        'Creative' => 'Creative Arts',
        'Religious' => 'Christian Religious Education',
        'SST' => 'Social Studies',
        'Science' => 'Integrated Science'
    ];

    $total_marks = 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mai-I-Ihii JUNIOR SCHOOL - Report Form</title>
    <link rel="stylesheet" href="css/new_report.css">
</head>
<body>
    <div class="document-border">
        <div class="report-container">
            <div class="print-header no-print">
                <h2>Student Report</h2>
                <button onclick="window.print()" class="print-button">Print Report</button>
            </div>

            <div class="school-header-container">
                <div class="logo-left">
                    <img src="images/logo.png" alt="logo">
                </div>

                <div class="school-header">
                    <div class="school-name">P.C.E.A MAI-A-IHII JUNIOR SCHOOL</div>
                    <div class="school-contact">P.O. BOX 56-00902 KIKUYU<br>Email: pceamaiaihiijuniorsecondarysch@gmail.com</div>
                </div>
                
                <div class="logo-right">
                    <img src="images/logo.png" alt="logo">
                </div>
            </div>

            <div class="report-title">REPORT FORM</div>
            
            <table class="info-table">
                <tr>
                    <td><?php echo htmlspecialchars($term); ?></td>
                    <td>Full Name : <b><?php echo htmlspecialchars($student['name']); ?></b></td>
                </tr>
                <tr>
                    <td><b>Opener</b></td>
                    <td>Grade/Class : <b><?php echo htmlspecialchars($student['class']); ?> <?php echo htmlspecialchars($exam_year); ?></b></td>
                </tr>
            </table>
            
            <table class="results-table">
                <thead>
                    <tr>
                        <th>LEARNING AREA</th>
                        <th colspan="2">Opener out of 100</th>
                        <th colspan="2">End-term out of 100</th>
                        <th>%</th>
                        <th>Grid</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($subjects as $db_column => $subject_name): $performance = getPerformanceData($exam_results[$db_column], $performance_data);?>
                    <tr>
                        <td><?php echo $subject_name; ?></td>
                        <td><?php echo $exam_results[$db_column]; ?></td>
                        <td><?php echo $performance['ab']; ?></td>
                        <td>-</td>
                        <td>-</td>
                        <td><?php echo $exam_results[$db_column]; ?> %</td>
                        <td><?php echo $performance['ab']; ?></td>
                        <td><?php echo $performance['pl']; ?></td>
                    </tr>
                    <?php $total_marks += $exam_results[$db_column]; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="words">
                <?php
                    // Calculate percentage score
                    $percentage = ($total_marks / 900) * 100;
                    $formatted_percentage = number_format($percentage, 2);

                    // Get performance level for the average
                    $average_performance = getPerformanceData($percentage, $performance_data);

                    // Grade Teacher Comments based on performance
                    if ($percentage >= 80) {
                        $teacher_comment = "Excellent performance! You have demonstrated outstanding mastery of the subjects. Keep up the good work!";
                    } elseif ($percentage >= 70) {
                        $teacher_comment = "Very good performance. You're doing well, but there's still room for improvement in some areas.";
                    } elseif ($percentage >= 60) {
                        $teacher_comment = "Good effort. Continue working hard and pay more attention to the subjects where you scored lower.";
                    } elseif ($percentage >= 50) {
                        $teacher_comment = "Average performance. You need to put in more effort, especially in your weaker subjects.";
                    } else {
                        $teacher_comment = "Below average performance. Immediate improvement is needed. Please seek extra help from your teachers.";
                    }

                    // Principal Comments based on performance
                    if ($percentage >= 85) {
                        $principal_comment = "Exceptional work! You're a model student for the school. Maintain this excellent standard.";
                    } elseif ($percentage >= 75) {
                        $principal_comment = "Commendable performance. With consistent effort, you can achieve even greater results.";
                    } elseif ($percentage >= 60) {
                        $principal_comment = "Satisfactory performance. Focus on improving your weaker areas to reach your full potential.";
                    } elseif ($percentage >= 50) {
                        $principal_comment = "Your results indicate you need to work harder. We believe you can do better with more dedication.";
                    } else {
                        $principal_comment = "We're concerned about your performance. Please meet with your grade teacher to discuss improvement strategies.";
                    }
                ?>
                <div><strong>Total Marks :</strong> <?php echo $total_marks; ?> (out of 900)</div>
                <div><strong>Average Marks :</strong> <?php echo $formatted_percentage; ?>% (<?php echo $average_performance['pl']; ?>)</div>
                <div><strong>Value Added :</strong></div>
            </div>
            
        
            
            <div class="signature-section">
                <div><strong>GRADE TEACHER :</strong> <?php echo htmlspecialchars($tutor); ?></div></div>
                <div><strong>Signature:</strong> _________________________</div>
                
                <div class="comments"><strong>COMMENTS:</strong> <?php echo $teacher_comment; ?></div>
                
                <div><strong>PRINCIPAL:</strong></div>
                <div><strong>Signature:</strong> _________________________</div>
                
                <div class="comments"><strong>COMMENTS:</strong> <?php echo $principal_comment; ?></div>
                
                <div><strong>Parent's Signatures:</strong> _________________________</div>
            </div>
            
            <div class="footer">
                <div><strong>Next Term Begins On:</strong> Monday, April 28, 2025</div>
                <div><strong>Print Date :</strong><?php echo date('jS M Y'); ?></div>
            </div>
            
            <div class="motto">SCHOOL MOTTO: UNITY BRING SUCCESS</div>
            <div class="stamp-notice">Not valid if without an official school rubber stamp</div>
        </div>
    </div>
</body>
</html>