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
    $query = "SELECT exam_type, term, YEAR(date_created) AS exam_year FROM exams WHERE exam_id = ?";
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
    $exam_type = $exam['exam_type']; // Get the type of exam

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
                            <img src="images/logo.png" data-holder-rendered="true"/>
                            </a>
                    </div>
                    <div class="col company-details">
                        <h2 class="name">
                            <a target="_blank" href="">
                            PCEA Junior Secondary School
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
                            <th class="text-left">SUBJECTS</th>
                            <th class="text-right">PERCENTAGE (%)</th>
                            <th class="text-right">Performance Levels</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($subjects as $db_column => $subject_name): ?>
                            <tr>
                                <td class="no"><?php echo $i++; ?></td>
                                <td class="text-left"><h3><?php echo $subject_name; ?></h3></td>
                                <td class="unit"><?php echo $exam_results[$db_column]; ?></td>
                                <td class="total"><?php echo getPerformanceLevel($exam_results[$db_column], $performance_levels); ?></td>
                            </tr>
                            <?php $total_marks += $exam_results[$db_column]; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>TOTAL MARK</td>
                            <td><?php echo $total_marks; ?></td>
                        </tr>
                    </tfoot>
                </table><br><br><br><br><br>
                
                <div class="thanks">
                    <h3>Class Teacher's Remarks:</h3>
                    <p>-------------------------------------------------------------------------------------------</p>
                </div><br><br><br>
                
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