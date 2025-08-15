<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "db/database.php";

// Parameters
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$grade = isset($_GET['grade']) ? $conn->real_escape_string($_GET['grade']) : '';
if ($exam_id === 0 || empty($grade)) die("Invalid or missing parameters.");

// Get exam name
$stmt = $conn->prepare("SELECT exam_name FROM exams WHERE exam_id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam_name = $result->fetch_assoc()['exam_name'] ?? die("Exam not found.");

// Get tutor
$stmt = $conn->prepare("SELECT name FROM class_teachers WHERE class_assigned = ?");
$stmt->bind_param("s", $grade);
$stmt->execute();
$result = $stmt->get_result();
$tutor = $result->fetch_assoc()['name'] ?? "Class teacher not found";

$title = ucwords(str_replace('_', ' ', $grade));
$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'Technical', 'Agriculture', 'SST', 'Science', 'Religious'];

// Fetch marks and PLs
$sql = "SELECT s.student_id, s.name AS Name";
foreach ($subjects as $subject) {
    $sql .= ", er.$subject, (SELECT ab FROM point_boundaries WHERE er.$subject BETWEEN min_marks AND max_marks LIMIT 1) AS PL_$subject";
}
$sql .= ", (" . implode(" + ", array_map(fn($s) => "COALESCE(er.$s, 0)", $subjects)) . ") AS total_marks 
         FROM students s
         LEFT JOIN exam_results er ON s.student_id = er.student_id AND er.exam_id = ?
         WHERE s.class = ?
         ORDER BY total_marks DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $exam_id, $grade);
$stmt->execute();
$result = $stmt->get_result();

// Calculate stats
$students = [];
$subject_totals = array_fill_keys($subjects, 0);
$subject_counts = array_fill_keys($subjects, 0);
$total_score = 0;
$total_students = 0;

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
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

$mean_scores = [];
foreach ($subjects as $index => $subject) {
    $count = $subject_counts[$subject] ?? 0;
    $total = $subject_totals[$subject] ?? 0;
    $mean_scores[$subject] = $count > 0 ? round($total / $count, 2) : 0;
}
$mean_total_marks = $total_students > 0 ? round($total_score / $total_students, 2) : 0;

// Save or update mean scores
$check = $conn->prepare("SELECT 1 FROM exam_mean_scores WHERE exam_id = ? AND class = ?");
$check->bind_param("is", $exam_id, $grade);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // UPDATE case
    $sql = "UPDATE exam_mean_scores SET " . implode(" = ?, ", $subjects) . " = ?, total_mean = ? WHERE exam_id = ? AND class = ?";
    $stmt = $conn->prepare($sql);
    
    $types = str_repeat('d', count($subjects)) . "d" . "is"; // d for subjects, 1 d for total_mean, i for exam_id, s for class
    $values = array_merge(array_values($mean_scores), [$mean_total_marks, $exam_id, $grade]);

    $stmt->bind_param($types, ...$values);
} else {
    // INSERT case
    $sql = "INSERT INTO exam_mean_scores (exam_id, class, " . implode(", ", $subjects) . ", total_mean) 
            VALUES (?, ?, " . rtrim(str_repeat("?, ", count($subjects)), ", ") . ", ?)";
    $stmt = $conn->prepare($sql);

    $types = "is" . str_repeat("d", count($subjects)) . "d"; // i for exam_id, s for class, d for each subject, d for total
    $values = array_merge([$exam_id, $grade], array_values($mean_scores), [$mean_total_marks]);

    $stmt->bind_param($types, ...$values);
}

$stmt->execute();


// Get previous exam
$prev_exam_id = null;
$stmt = $conn->prepare("SELECT exam_id FROM exams WHERE exam_id < ? ORDER BY exam_id DESC LIMIT 1");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $prev_exam_id = $row['exam_id'];

// Get previous means
$prev_mean_scores = array_fill_keys($subjects, "-");
$prev_mean_total_marks = "-";

if ($prev_exam_id) {
    $stmt = $conn->prepare("SELECT * FROM exam_mean_scores WHERE exam_id = ? AND class = ?");
    $stmt->bind_param("is", $prev_exam_id, $grade);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        foreach ($subjects as $subject) {
            $prev_mean_scores[$subject] = $row[$subject] ?? "-";
        }
        $prev_mean_total_marks = $row['total_mean'] ?? "-";
    }
}

// Deviation
$deviation_scores = [];
foreach ($subjects as $subject) {
    $prev = (isset($prev_mean_scores[$subject]) && is_numeric($prev_mean_scores[$subject])) ? $prev_mean_scores[$subject] : null;
    $curr = (isset($mean_scores[$subject]) && is_numeric($mean_scores[$subject])) ? $mean_scores[$subject] : null;

    $deviation_scores[$subject] = ($prev !== null && $curr !== null) 
        ? round($curr - $prev, 2) 
        : "-";
}

// Total mean deviation
$total_mean_deviation = (isset($prev_mean_total_marks) && isset($mean_total_marks) && is_numeric($prev_mean_total_marks) && is_numeric($mean_total_marks)) 
    ? round($mean_total_marks - $prev_mean_total_marks, 2) 
    : "-";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark List</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .row { display: flex; }
        .column { padding: 10px; }
        .left { width: 80%; }
        .right { width: 20%; }
        @media print { button { display: none !important; } }
    </style>
    <script>function printPage() { window.print(); }</script>
</head>
<body>
    <button onclick="printPage()">Print</button>
    <div class="container my-5">
        <div class="row">
            <div class="column left">
                <h1>Mark List - <?= htmlspecialchars($exam_name) ?></h1>
                <h1>Grade: <?= htmlspecialchars($title) ?></h1>
                <h1>Tutor: <?= htmlspecialchars($tutor) ?></h1>
            </div>
            <div class="column right">
                <img src="images/logo.png" alt="Logo" width="250" height="200">
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Rank</th>
                    <th rowspan="2">Name</th>
                    <?php foreach ($subjects as $subject): ?>
                        <th colspan="2"><?= $subject ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2">Total Marks</th>
                </tr>
                <tr>
                    <?php foreach ($subjects as $_): ?>
                        <th>Marks</th>
                        <th>PL</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($students)): $rank = 1; ?>
                <?php foreach ($students as $student): ?>
                    <?php
                        $student_id = $student['student_id'];
                        $total_marks = $student['total_marks'] ?? 0;
                        $stmt = $conn->prepare("UPDATE exam_results SET total_marks = ?, position = ? WHERE student_id = ? AND exam_id = ?");
                        $stmt->bind_param("iiii", $total_marks, $rank, $student_id, $exam_id);
                        $stmt->execute();
                    ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= htmlspecialchars($student['Name']) ?></td>
                        <?php foreach ($subjects as $subject): ?>
                            <td><?= $student[$subject] ?? '-' ?></td>
                            <td><?= $student['PL_' . $subject] ?? '-' ?></td>
                        <?php endforeach; ?>
                        <td><?= $total_marks ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="2">Mean Scores</th>
                    <?php foreach ($mean_scores as $mean): ?>
                        <td colspan="2"><?= $mean ?></td>
                    <?php endforeach; ?>
                    <td colspan="2"><?= $mean_total_marks ?></td>
                </tr>
                <tr><th colspan="2">Previous Mean Scores</th>
                    <?php foreach ($prev_mean_scores as $prev): ?>
                        <td colspan="2"><?= $prev ?></td>
                    <?php endforeach; ?>
                    <td colspan="2"><?= $prev_mean_total_marks ?></td>
                </tr>
                <tr><th colspan="2">Deviation</th>
                    <?php foreach ($deviation_scores as $dev): ?>
                        <td colspan="2" style="color: <?= $dev > 0 ? 'green' : ($dev < 0 ? 'red' : 'black') ?>">
                            <?= $dev !== "-" ? ($dev > 0 ? "+$dev" : $dev) : "-" ?>
                        </td>
                    <?php endforeach; ?>
                    <td colspan="2" style="color: <?= $total_mean_deviation > 0 ? 'green' : ($total_mean_deviation < 0 ? 'red' : 'black') ?>">
                        <?= $total_mean_deviation !== "-" ? ($total_mean_deviation > 0 ? "+$total_mean_deviation" : $total_mean_deviation) : "-" ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
