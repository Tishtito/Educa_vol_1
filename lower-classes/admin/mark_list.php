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
if (!$stmt) {
    die("Error preparing query:" . $conn->error);
}
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $exam_name = $row['exam_name'];
} else {
    die("Exam not found.");
}

// Fetch the tutor name
$sql = "SELECT name FROM users WHERE class_assigned = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("s", $grade); // Bind grade as a string
$stmt->execute();
$result = $stmt->get_result();

$tutor = "Class teacher not found"; // Default message if not found
if ($row = $result->fetch_assoc()) {
    $tutor = $row['name'];
}


$title = ucwords(str_replace('_', ' ', $grade));

// Fetch and calculate total marks for each student
$sql = "
SELECT 
    students.student_id AS student_id, 
    students.name AS Name, 
    exam_results.English, 
    exam_results.Math, 
    exam_results.Kiswahili,
    exam_results.Enviromental,
    exam_results.Creative,
    exam_results.Religious,  
    (
        exam_results.English + 
        exam_results.Math + 
        exam_results.Kiswahili + 
        exam_results.Enviromental +
        exam_results.Creative +
        exam_results.Religious
    ) AS Total_marks
FROM 
    students
LEFT JOIN 
    exam_results 
ON 
    students.student_id = exam_results.student_id AND exam_results.exam_id = ?
WHERE 
    students.class = ?
ORDER BY 
    Total_marks DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("is", $exam_id, $grade);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
$valid_students = 0;
$subject_totals = ["English" => 0, "Kiswahili" => 0, "Math" => 0, "Enviromental" => 0, "Creative" => 0, "Religious" => 0];
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
    if ($score === null) return "-"; // Show "-" if blank
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

// Check if mean scores already exist for this exam
$check_sql = "SELECT * FROM exam_mean_scores WHERE exam_id = ? AND class = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $exam_id, $grade);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing mean scores
    $update_sql = "
    UPDATE exam_mean_scores SET
        English = ?, Math = ?, Kiswahili = ?, Enviromental = ?, Creative = ?, Religious = ?, total_mean = ? 
    WHERE exam_id = ? AND class = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("dddddddis", 
        $mean_scores['English'], $mean_scores['Math'], $mean_scores['Kiswahili'], 
         $mean_scores['Enviromental'], $mean_scores['Creative'], $mean_scores['Religious'], $mean_total_marks, $exam_id, $grade);
    $update_stmt->execute();
} else {
    // Insert new mean scores
    $insert_sql = "
    INSERT INTO exam_mean_scores (exam_id, class, English, Math, Kiswahili, Enviromental, Creative, Religious, total_mean)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("isddddddd", 
        $exam_id, $grade, $mean_scores['English'], $mean_scores['Math'], 
        $mean_scores['Kiswahili'], $mean_scores['Enviromental'], $mean_scores['Creative'], $mean_scores['Religious'], $mean_total_marks);
    $insert_stmt->execute();
}

// Get the last exam before the current one
$prev_exam_sql = "
SELECT exam_id FROM exams 
WHERE exam_id < ? 
ORDER BY exam_id DESC 
LIMIT 1";
$prev_exam_stmt = $conn->prepare($prev_exam_sql);
$prev_exam_stmt->bind_param("i", $exam_id);
$prev_exam_stmt->execute();
$prev_exam_result = $prev_exam_stmt->get_result();

$prev_exam_id = null;
if ($prev_exam_row = $prev_exam_result->fetch_assoc()) {
    $prev_exam_id = $prev_exam_row['exam_id'];
}

// Fetch previous mean scores
$prev_mean_scores = [];
if ($prev_exam_id) {
    $prev_mean_sql = "SELECT * FROM exam_mean_scores WHERE exam_id = ? AND class = ?";
    $prev_mean_stmt = $conn->prepare($prev_mean_sql);
    $prev_mean_stmt->bind_param("is", $prev_exam_id, $grade);
    $prev_mean_stmt->execute();
    $prev_mean_result = $prev_mean_stmt->get_result();

    if ($prev_mean_row = $prev_mean_result->fetch_assoc()) {
        foreach ($subject_totals as $subject => $_) {
            $prev_mean_scores[$subject] = $prev_mean_row[$subject] ?? "-";
        }
        $prev_mean_total_marks = $prev_mean_row['total_mean'] ?? "-";
    } else {
        foreach ($subject_totals as $subject => $_) {
            $prev_mean_scores[$subject] = "-";
        }
        $prev_mean_total_marks = "-";
    }
} else {
    foreach ($subject_totals as $subject => $_) {
        $prev_mean_scores[$subject] = "-";
    }
    $prev_mean_total_marks = "-";
}

// Compute deviation (current mean - previous mean)
$deviation_scores = [];
foreach ($subject_totals as $subject => $_) {
    $prev_mean_value = is_numeric($prev_mean_scores[$subject]) ? $prev_mean_scores[$subject] : null;
    $current_mean_value = is_numeric($mean_scores[$subject]) ? $mean_scores[$subject] : null;

    if ($prev_mean_value !== null && $current_mean_value !== null) {
        $deviation_scores[$subject] = round($current_mean_value - $prev_mean_value, 2);
    } else {
        $deviation_scores[$subject] = "-";
    }
}

// Compute deviation for total mean score
if (is_numeric($prev_mean_total_marks) && is_numeric($mean_total_marks)) {
    $total_mean_deviation = round($mean_total_marks - $prev_mean_total_marks, 2);
} else {
    $total_mean_deviation = "-";
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
                <h1>Grade: <?php echo htmlspecialchars($title); ?></h1>
                <h1>Tutor: <?php echo htmlspecialchars($tutor); ?></h1>
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
                    <?php foreach ($subject_totals as $subject => $_): ?>
                        <th colspan="2"> <?php echo $subject; ?> </th>
                    <?php endforeach; ?>
                    <th rowspan="2">Total Marks</th>
                </tr>
                <tr>
                    <?php foreach ($subject_totals as $_): ?>
                        <th>Marks</th>
                        <th>PL</th>
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
                            $update_sql = "UPDATE exam_results SET total_marks = ?, position = ? WHERE student_id = ? AND exam_id = ?";
                            $update_stmt = $conn->prepare($update_sql);
                            $update_stmt->bind_param("iiii", $total_marks, $rank, $student_id, $exam_id);
                            $update_stmt->execute();
                        } ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <?php foreach ($subject_totals as $subject => $_): ?>
                                <td><?php echo htmlspecialchars($student[$subject] ?? '-'); ?></td>
                                <td><?php echo getPerformanceLevel($student[$subject], $performance_levels); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo htmlspecialchars($student['Total_marks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Mean Scores</th>
                    <?php foreach ($mean_scores as $mean): ?>
                        <td colspan="2"><?php echo $mean; ?></td>
                    <?php endforeach; ?>
                    <td colspan="2"><?php echo $mean_total_marks; ?></td>
                </tr>
                <tr>
                    <th colspan="2">Previous Mean Scores</th>
                    <?php foreach ($prev_mean_scores as $prev_mean): ?>
                        <td colspan="2"><?php echo $prev_mean; ?></td>
                    <?php endforeach; ?>
                    <td colspan="2"><?php echo $prev_mean_total_marks; ?></td>
                </tr>

                <tr>
                    <th colspan="2">Deviation</th>
                    <?php foreach ($deviation_scores as $deviation): ?>
                        <td colspan="2" style="color: <?php echo ($deviation > 0) ? 'green' : (($deviation < 0) ? 'red' : 'black'); ?>">
                            <?php echo ($deviation !== "-") ? (($deviation > 0) ? "+".$deviation : $deviation) : "-"; ?>
                        </td>
                    <?php endforeach; ?>
                    <td colspan="2" style="color: <?php echo ($total_mean_deviation > 0) ? 'green' : (($total_mean_deviation < 0) ? 'red' : 'black'); ?>">
                        <?php echo ($total_mean_deviation !== "-") ? (($total_mean_deviation > 0) ? "+".$total_mean_deviation : $total_mean_deviation) : "-"; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
