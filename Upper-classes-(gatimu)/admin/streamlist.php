<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "db/database.php";

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$grade = isset($_GET['grade']) ? $conn->real_escape_string($_GET['grade']) : '';
$exam_name = "";

// Validate parameters
if ($exam_id === 0 || empty($grade)) {
    die("Invalid or missing parameters.");
}

// Fetch the exam name
$sql = "SELECT exam_name FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $exam_name = $row['exam_name'];
} else {
    die("Exam not found.");
}

// Fetch all class names under the specified grade
$class_query = "SELECT class_name FROM classes WHERE grade = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("s", $grade);
$stmt->execute();
$class_result = $stmt->get_result();

$class_names = [];
while ($row = $class_result->fetch_assoc()) {
    $class_names[] = $row['class_name'];
}

// Convert class names into a SQL-friendly format ('6_blue', '6_red', etc.)
$class_placeholder = implode(',', array_fill(0, count($class_names), '?'));

// Fetch students from all classes under the given grade
$sql = "
SELECT 
    students.student_id AS student_id, 
    students.name AS Name, 
    students.class AS Class, 
    exam_results.English, 
    exam_results.Math, 
    exam_results.Kiswahili, 
    exam_results.Creative, 
    exam_results.SciTech, 
    exam_results.AgricNutri,
    exam_results.SST,
    exam_results.CRE,
    (
        exam_results.English + 
        exam_results.Math + 
        exam_results.Kiswahili + 
        exam_results.Creative + 
        exam_results.SciTech +
        exam_results.AgricNutri +
        exam_results.SST +
        exam_results.CRE
    ) AS Total_marks
FROM 
    students
LEFT JOIN 
    exam_results 
ON 
    students.student_id = exam_results.student_id AND exam_results.exam_id = ?
WHERE 
    students.class IN ($class_placeholder)
ORDER BY 
    Total_marks DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i" . str_repeat("s", count($class_names)), $exam_id, ...$class_names);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
$valid_students = 0;
$subject_totals = ["English" => 0, "Kiswahili" => 0, "Math" => 0, "Creative" => 0, "SciTech" => 0, "AgricNutri" => 0, "SST" => 0, "CRE" => 0];
$total_valid_marks = 0;

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
    $all_subjects_filled = true;
    foreach ($subject_totals as $subject => &$total) {
        if ($row[$subject] === null) {
            $all_subjects_filled = false;
        } else {
            $total += $row[$subject];
        }
    }
    if ($all_subjects_filled) {
        $valid_students++;
        $total_valid_marks += $row['Total_marks'];
    }
}

// Fetch performance levels from point_boundaries
$pl_query = "SELECT min_marks, max_marks, ab FROM point_boundaries";
$pl_result = $conn->query($pl_query);
$performance_levels = [];
while ($pl_row = $pl_result->fetch_assoc()) {
    $performance_levels[] = $pl_row;
}

function getPerformanceLevel($score, $performance_levels) {
    foreach ($performance_levels as $pl) {
        if ($score >= $pl['min_marks'] && $score <= $pl['max_marks']) {
            return $pl['ab'];
        }
    }
    return "-";
}

$mean_scores = [];
if ($valid_students > 0) {
    foreach ($subject_totals as $subject => $total) {
        $mean_scores[$subject] = round($total / $valid_students, 2);
    }
    $mean_total_marks = round($total_valid_marks / $valid_students, 2);
} else {
    $mean_total_marks = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark List</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }

        .row {
            display: flex;
        }

        .column {
            float: left;
            padding: 10px;
        }

        .left {
            width: 80%;
        }

        .right {
            width: 20%;
        }

        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        @media print{
            button{
                display: none !important;
            }
        }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Print Button -->
    <button onclick="printPage()" class="btn btn-secondary mb-3">Print</button>

    <div class="container my-5">
        <div class="row">
            <div class="column left">
                <h1>Mark List - <?php echo htmlspecialchars($exam_name); ?></h1>
                <h1>Grade: <?php echo htmlspecialchars($grade); ?></h1>
            </div>

            <div class="column right">
                <img src="images/logo.png" alt="" width="250px" height="200px">
            </div>
        </div>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th rowspan="2">Rank</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Class</th>
                    <?php foreach ($subject_totals as $subject => $_): ?>
                        <th colspan="2"> <?php echo $subject; ?> </th>
                    <?php endforeach; ?>
                    <th rowspan="2">Total Marks</th>
                </tr>
                <tr>
                    <?php foreach ($subject_totals as $_): ?>
                        <th>Marks</th><th>PL</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php $rank = 1;
                    foreach ($students as $student): 
                    {
                        $student_id = $student['student_id'];
                        $total_marks = $student['Total_marks'];
                        $update_sql = "UPDATE exam_results SET total_marks = ?, stream_position = ? WHERE student_id = ? AND exam_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("iiii", $total_marks, $rank, $student_id, $exam_id);
                        $update_stmt->execute();
                    }
                    ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <td><?php echo htmlspecialchars($student['Class']); ?></td>
                            <?php foreach ($subject_totals as $subject => $_): ?>
                                <td><?php echo htmlspecialchars($student[$subject]); ?></td>
                                <td><?php echo getPerformanceLevel($student[$subject], $performance_levels); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo htmlspecialchars($student['Total_marks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Mean Scores</th>
                    <?php foreach ($mean_scores as $mean): ?>
                        <td colspan="2"> <?php echo $mean; ?> </td>
                    <?php endforeach; ?>
                    <td><?php echo $mean_total_marks; ?></td>
                </tr>
            </tfoot>
        </table>
        <button onclick="printPage()" class="btn btn-secondary mb-3">Print</button>
    </div>
</body>
</html>
