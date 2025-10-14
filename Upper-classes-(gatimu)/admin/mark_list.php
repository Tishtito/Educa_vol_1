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

// Fetch the tutor name
$sql = "SELECT name FROM users WHERE class_assigned = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $grade);
$stmt->execute();
$result = $stmt->get_result();
$tutor = "Class teacher not found";
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
    exam_results.Creative, 
    exam_results.Enviromental, 
    exam_results.Religious,
    (
        exam_results.English + 
        exam_results.Math + 
        exam_results.Kiswahili + 
        exam_results.Creative + 
        exam_results.Enviromental +
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
$stmt->bind_param("is", $exam_id, $grade);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
$valid_students = 0;
$subject_totals = ["English" => 0, "Kiswahili" => 0, "Math" => 0, "Creative" => 0, "Enviromental" => 0,"Religious" => 0];
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

function updateStudentResults($conn, $exam_id, $students) {
    foreach ($students as $index => $student) {
        $position = $index + 1; // Rank starts from 1
        $total_marks = $student['Total_marks'];
        $student_id = $student['student_id'];

        $update_query = "
            UPDATE exam_results 
            SET Total_marks = ?, position = ? 
            WHERE exam_id = ? AND student_id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            die("Error preparing query: " . $conn->error);
        }
        $stmt->bind_param("iiii", $total_marks, $position, $exam_id, $student_id);
        $stmt->execute();
    }
}

if (!empty($students)) {
    updateStudentResults($conn, $exam_id, $students);
}

// Fetch performance levels dynamically for each subject
$pl_query = "SELECT subject, min_point, max_point, ab FROM point_boundaries";
$pl_result = $conn->query($pl_query);
$performance_levels = [];

while ($pl_row = $pl_result->fetch_assoc()) {
    $subject = $pl_row['subject'];
    if (!isset($performance_levels[$subject])) {
        $performance_levels[$subject] = [];
    }
    $performance_levels[$subject][] = [
        'min_point' => $pl_row['min_point'],
        'max_point' => $pl_row['max_point'],
        'ab' => $pl_row['ab']
    ];
}

function getPerformanceLevel($score, $subject, $performance_levels) {
    if ($score === null || $score === "") {
        return "-";
    }

    if (!isset($performance_levels[$subject])) {
        return "-"; // No boundaries found for this subject
    }

    foreach ($performance_levels[$subject] as $pl) {
        if ($score >= $pl['min_point'] && $score <= $pl['max_point']) {
            return $pl['ab'];
        }
    }
    return "-";
}

// Fetch performance levels for total marks
$pl_total_query = "SELECT min_point, max_point, ab FROM totalmarks_boundaries";
$pl_total_result = $conn->query($pl_total_query);
$performance_levels_total = [];
while ($pl_row = $pl_total_result->fetch_assoc()) {
    $performance_levels_total[] = $pl_row;
}

function getPerformanceLevel1($score, $performance_levels) {
    if ($score === null) return "-"; // Show "-" if blank
    foreach ($performance_levels as $pl) {
        if ($score >= $pl['min_point'] && $score <= $pl['max_point']) {
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
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="container my-5">
        <h1>Mark List - <?php echo htmlspecialchars($exam_name); ?></h1>
        <h1>Grade: <?php echo htmlspecialchars($title); ?></h1>
        <h1>Tutor: <?php echo htmlspecialchars($tutor); ?></h1>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th rowspan="2">Rank</th>
                    <th rowspan="2">Name</th>
                    <?php foreach ($subject_totals as $subject => $_): ?>
                        <th colspan="2"> <?php echo $subject; ?> </th>
                    <?php endforeach; ?>
                    <th colspan="2">Total Marks</th>
                </tr>
                <tr>
                    <?php foreach ($subject_totals as $_): ?>
                        <th>Marks</th>
                        <th>PL</th>
                    <?php endforeach; ?>
                    <th>TOTAL</th>
                    <th>PL</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php $rank = 1;
                    foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <?php foreach ($subject_totals as $subject => $_): ?>
                                <td><?php echo htmlspecialchars($student[$subject]); ?></td>
                                <td><?php echo getPerformanceLevel($student[$subject], $subject, $performance_levels); ?></td>
                            <?php endforeach; ?>
                            <td><?php echo htmlspecialchars($student['Total_marks']); ?></td>
                            <td><?php echo getPerformanceLevel1($student['Total_marks'], $performance_levels_total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Mean Scores</th>
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
