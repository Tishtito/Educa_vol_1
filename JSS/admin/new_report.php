<?php
    require_once "db/database.php";
    
    // Get student ID and exam type from URL
    $student_id = $_GET['student_id'] ?? null;
    $exam_id = $_GET['exam_id'] ?? null;
    $exam_type = $_GET['exam_type'] ?? null;

    if (!$student_id || !$exam_id || !$exam_type) {
        die("Invalid request. Student ID, Exam ID, and Exam Type are required.");
    }

    // Get student details including their class name
    $query = "SELECT name, class FROM students WHERE student_id = ?";
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

    // Fetch the year of exam
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

    // Fetch both the mid-term and end-of-term exam IDs for this term
    $query = "SELECT exam_id, exam_type FROM exams WHERE term = ? AND (exam_type = 'Mid-Term' OR exam_type = 'End-Term')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $exam_ids = [];
    while ($row = $result->fetch_assoc()) {
        $exam_ids[$row['exam_type']] = $row['exam_id'];
    }

    // Ensure both exam types exist in the array
    $mid_term_id = $exam_ids['Mid-Term'] ?? null;
    $end_term_id = $exam_ids['End-Term'] ?? null;

    // Fetch performance levels
    $performance_data = []; // Changed from $performance_levels to $performance_data for consistency
    $query = "SELECT min_marks, max_marks, pl, ab FROM point_boundaries"; // Added ab column
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $performance_data[] = $row;
    }

    function getPerformanceLevel($score, $levels) {
        if ($score === null) return ['pl' => "-", 'ab' => "-"];
        
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

    // Fetch exam results for both mid-term and end-of-term
    $exam_results = [];
    $total_marks_mid = 0;
    $total_marks_end = 0;
    $total_marks = 0;

    if ($mid_term_id) {
        $query = "SELECT * FROM exam_results WHERE student_id = ? AND exam_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $student_id, $mid_term_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo "No mid-term marks found for student ID: $student_id and exam ID: $mid_term_id";
        }
        $exam_results['Mid-Term'] = $result->fetch_assoc();
    }
    if ($end_term_id) {
        $query = "SELECT * FROM exam_results WHERE student_id = ? AND exam_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $student_id, $end_term_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo "No End-term marks found for student ID: $student_id and exam ID: $end_term_id";
        }
        $exam_results['End-Term'] = $result->fetch_assoc();
    }
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
                    <td><b>End-Term</b></td>
                    <td>Grade/Class : <b><?php echo htmlspecialchars($student['class']); ?> <?php echo htmlspecialchars($exam_year); ?></b></td>
                </tr>
            </table>
            
            <table class="results-table">
                <thead>
                    <tr>
                        <th>LEARNING AREA</th>
                        <th colspan="2">Mid term out of 100</th>
                        <th colspan="2">End-term out of 100</th>
                        <th>%</th>
                        <th>Grid</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $db_column => $subject_name): 
                        // Get scores for both exam types
                        $mid_score = $exam_results['Mid-Term'][$db_column] ?? null;
                        $end_score = $exam_results['End-Term'][$db_column] ?? null;
                        
                        // Calculate average score
                        if ($mid_score !== null && $end_score !== null) {
                            $average_score = ($mid_score + $end_score) / 2;
                        } elseif ($mid_score !== null) {
                            $average_score = ($mid_score + $end_score) / 2;
                        } elseif ($end_score !== null) {
                            $average_score = ($mid_score + $end_score) / 2;
                        } else {
                            $average_score = null;
                        }
                        
                        // Get performance levels
                        $mid_performance = getPerformanceLevel($mid_score, $performance_data);
                        $end_performance = getPerformanceLevel($end_score, $performance_data);
                        $average_performance = getPerformanceLevel($average_score, $performance_data);
                        
                        // Add to totals if scores exist
                        if ($mid_score !== null) $total_marks_mid += $mid_score;
                        if ($end_score !== null) $total_marks_end += $end_score;
                        if ($average_score !== null) $total_marks += $average_score;
                    ?>
                    <tr>
                        <td><?php echo $subject_name; ?></td>
                        <td><?php echo $mid_score ?? '-'; ?></td>
                        <td><?php echo $mid_performance['ab']; ?></td>
                        <td><?php echo $end_score ?? '-'; ?></td>
                        <td><?php echo $end_performance['ab']; ?></td>
                        <td><?php echo $average_score !== null ? number_format($average_score, 1) . '%' : '-'; ?></td>
                        <td><?php echo $average_performance['ab']; ?></td>
                        <td><?php echo $average_performance['pl']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="words">
                <?php
                    // Calculate percentage score
                    $percentage = (count($subjects) > 0) ? ($total_marks / (count($subjects) * 100)) * 100 : 0;
                    $formatted_percentage = number_format($percentage, 2);

                    // Get performance level for the average
                    $average_performance = getPerformanceLevel($percentage, $performance_data);

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
                <div><strong>Total Marks Mid-Term:</strong> <?php echo $total_marks_mid; ?> (out of <?php echo count($subjects) * 100; ?>)</div>
                <div><strong>Total Marks End-Term:</strong> <?php echo $total_marks_end; ?> (out of <?php echo count($subjects) * 100; ?>)</div>
                <div><strong>Average Marks:</strong> <?php echo $formatted_percentage; ?>% (<?php echo $average_performance['pl']; ?>)</div>
                <!-- <div><strong>Value Added:</strong></div> -->
            </div>
                   
            
            <div class="signature-section">
                <div><strong>GRADE TEACHER :</strong> <?php echo htmlspecialchars($tutor); ?></div></div>
                <div><strong>Signature:</strong> _________________________</div>
                
                <div class="comments"><strong>COMMENTS:</strong> <?php echo $teacher_comment; ?></div>
                
                <div><strong>PRINCIPAL:</strong></div>
                <div><strong>Signature:</strong> ___<u>Lucy Maina</u>_____________</div>
                
                <div class="comments"><strong>COMMENTS:</strong> <?php echo $principal_comment; ?></div>
                
                <div><strong>Parent's Signatures:</strong> _________________________</div>
            </div>
            
            <div class="footer">
                <div><strong>Next Term Begins On:</strong> Monday, August 25th, 2025</div>
                <div><strong>Print Date :</strong><?php echo date('jS M Y'); ?></div>
            </div>
            
            <div class="motto">SCHOOL MOTTO: FORWARD EVER BACKWARD NEVER</div>
            <div class="stamp-notice">Not valid if without an official school rubber stamp</div>
        </div>
    </div>
</body>
</html>