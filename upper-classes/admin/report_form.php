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

    // Fetch the term of the selected exam
    $query = "SELECT term FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();

    if (!$exam) {
        die("Exam not found.");
    }

    $term = $exam['term']; // Get the term of the selected exam

    // Fetch both the mid-term and end-of-term exam IDs for this term
    $query = "SELECT exam_id, exam_type FROM exams WHERE term = ? AND (exam_type = 'Mid-Term' OR exam_type = 'End-Term')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $term); // Change from "i" to "s" (string)
    $stmt->execute();
    $result = $stmt->get_result();

    $exam_ids = [];
    while ($row = $result->fetch_assoc()) {
        $exam_ids[$row['exam_type']] = $row['exam_id'];
    }

    // Ensure both exam types exist in the array
    $mid_term_id = $exam_ids['Mid-Term'] ?? null;
    $end_term_id = $exam_ids['End-Term'] ?? null;


    // Fetch student details
    $query = "SELECT name, class FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        die("Student not found.");
    }

    // Fetch performance levels
    $performance_levels = [];
    $query = "SELECT min_marks, max_marks, pl FROM point_boundaries";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $performance_levels[] = $row;
    }

    function getPerformanceLevel($score, $levels) {
        foreach ($levels as $level) {
            if ($score >= $level['min_marks'] && $score <= $level['max_marks']) {
                return $level['pl'];
            }
        }
        return "UNKNOWN";
    }

    $subjects = [
        'Math' => 'Mathematics',
        'English' => 'English Language',
        'Kiswahili' => 'Kiswahili',
        'SciTech' => 'Science & Technology',
        'AgricNutri' => 'Agriculture & Nutrition',
        'Creative' => 'Creative Arts',
        'CRE' => 'Christian Religious Education',
        'SST' => 'Social Studies',
    ];

    // Fetch exam results for both mid-term and end-of-term
    $exam_results = [];

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
            echo "No End-term marks found for student ID: $student_id and exam ID: $mid_term_id";
        }
        $exam_results['End-Term'] = $result->fetch_assoc();
    }

    $total_marks_mid = 0;
    $total_marks_end = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
<div id="invoice">

    <div class="hide-on-print">
        <div class="text-right">
            <button id="printInvoice" class="btn btn-info"><i class="fa fa-print"></i> Print</button>
            <button class="btn btn-info"><i class="fa fa-file-pdf-o"></i> Export as PDF</button>
        </div>
        <hr>
    </div>
    <div class="invoice overflow-auto">
        <div style="min-width: 600px">
            <header>
                <div class="row">
                    <div class="col">
                        <a target="_blank" href="">
                            <img src="images/logo.png" data-holder-rendered="true" />
                            </a>
                    </div>
                    <div class="col company-details">
                        <h2 class="name">
                            <a target="_blank" href="">
                            PCEA Ngure Primary School
                            </a>
                        </h2>
                        <div style="font-size:30px">143-00902, KIKUYU</div>
                        <div style="font-size:30px">ngureprimary22@gmail.com</div>
                    </div>
                </div>
            </header>
            <main>
                <div class="row contacts">
                    <div class="col invoice-to">
                        <div class="text-gray-light">REPORT FOR:</div>
                        <h2 class="to"><?php echo htmlspecialchars($student['name']); ?></h2>
                        <div class="address" style="font-size:20px">Grade: <?php echo htmlspecialchars($student['class']); ?></div>
                        <div class="address" style="font-size:20px">Tutor: <?php echo htmlspecialchars($tutor); ?></div>
                    </div>
                    <div class="col invoice-details">
                        <h1 class="invoice-id">Performance Report</h1>
                        <div class="date" style="font-size:20px"><?php echo htmlspecialchars($term); ?></div>
                        <div class="date" style="font-size:20px">Year: <?php echo htmlspecialchars($exam_year); ?></div>
                    </div>
                </div>
                <table border="0" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="text-left">LEARNING AREAS</th>
                            <th colspan="2" class="text-center">Mid-Term</th>
                            <th colspan="2" class="text-center">End of Term</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th></th>
                            <th class="text-center">MARKS</th>
                            <th class="text-center">Performance</th>
                            <th class="text-center">MARKS</th>
                            <th class="text-center">Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($subjects as $db_column => $subject_name): ?>
                            <tr>
                                <td class="no"><?php echo $i++; ?></td>
                                <td class="text-left"><h3><?php echo $subject_name; ?></h3></td>

                                <!-- Mid-Term Exam -->
                                <td class="unit"><?php echo $exam_results['Mid-Term'][$db_column] ?? '-'; ?></td>
                                <td class="total"><?php echo getPerformanceLevel($exam_results['Mid-Term'][$db_column] ?? 0, $performance_levels); ?></td>

                                <!-- End of Term Exam -->
                                <td class="unit"><?php echo $exam_results['End-Term'][$db_column] ?? '-'; ?></td>
                                <td class="total"><?php echo getPerformanceLevel($exam_results['End-Term'][$db_column] ?? 0, $performance_levels); ?></td>
                            </tr>
                            <?php $total_marks_mid += $exam_results['Mid-Term'][$db_column]; ?>
                            <?php $total_marks_end += $exam_results['End-Term'][$db_column]; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>TOTAL MARK</td>
                            <td><u><?php echo $total_marks_mid; ?> /800</u></td>
                            <td>TOTAL MARK</td>
                            <td><u><?php echo $total_marks_end ?> /800</u></td>
                        </tr>
                    </tfoot>
                </table><br><br><br><br><br>
                <div class="thanks">
                    <h3>Class Teacher's Remarks:</h3>
                    <p>-------------------------------------------------------------------------------------------</p>
                </div><br><br>

                <div class="comments">
                    <div class="thanks">
                        <h5>Fee balance:</h5>
                        <p>--------------------------------------------</p>
                    </div>

                    <div class="thanks">
                        <h5>Next Term Feeding Amount:</h5>
                        <p>--------------------------------------------</p> 
                    </div>

                    <div class="thanks">
                        <h5>Closing Date:</h5>
                        <p>--------------------------------------------</p> 
                    </div>

                    <div class="thanks">
                        <h5>Opening Date:</h5>
                        <p>--------------------------------------------</p> 
                    </div>

                    <div class="thanks">
                        <h5>Head Teacher Signature:</h5>
                        <p>--------------------------------------------</p>
                    </div>

                    <div class="thanks">
                        <h5>Parents Signature:</h5>
                        <p>--------------------------------------------</p>
                    </div>
                </div>

                <!--
                <div class="notices">
                    <div>NOTICE:</div>
                    <div class="notice">A finance charge of 1.5% will be made on unpaid balances after 30 days.</div>
                </div> -->
                <footer>
                    Performance Report should be reach to all parents or else will be treated as an indispline action.
                </footer>
            </main>
            
        </div>
        <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
        <div></div>
    </div>
</div>

<script src="js/report.js"></script>


</body>
</html>