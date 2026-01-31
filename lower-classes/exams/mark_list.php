<?php
session_start();
require_once "db/database.php";

// Ensure the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Fetch user's assigned class and the selected exam ID
$class_assigned = $_SESSION["class_assigned"];
$exam_id = $_SESSION["exam_id"];
$user_id = $_SESSION['id'];
$exam_name = "";

$sql = "SELECT name FROM users WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();

// Validate parameters
if ($exam_id === 0) {
    die("Invalid or missing parameters.");
}

// Fetch the exam name
$sql = "SELECT exam_name FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $exam_name = $row['exam_name'];
} else {
    die("Exam not found.");
}

$title = ucwords(str_replace('_', ' ', $class_assigned));

// SQL Query to Fetch Student Marks and Performance Levels
$sql = "
    SELECT 
        s.student_id,
        s.name AS Name,

        er.English,
        (SELECT ab FROM point_boundaries WHERE er.English BETWEEN min_marks AND max_marks LIMIT 1) AS PL_English,

        er.Math,
        (SELECT ab FROM point_boundaries WHERE er.Math BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Math,

        er.Kiswahili,
        (SELECT ab FROM point_boundaries WHERE er.Kiswahili BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Kiswahili,

        er.Creative,
        (SELECT ab FROM point_boundaries WHERE er.Creative BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Creative,

        er.Enviromental,
        (SELECT ab FROM point_boundaries WHERE er.Enviromental BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Enviromental,

        er.Religious,
        (SELECT ab FROM point_boundaries WHERE er.Religious BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Religious,

        (
            COALESCE(er.English,0) + COALESCE(er.Math,0) + COALESCE(er.Kiswahili,0) +
            COALESCE(er.Creative,0) + COALESCE(er.Enviromental,0) + COALESCE(er.Religious,0)
        ) AS Total_marks

    FROM student_classes sc
    JOIN students s 
        ON sc.student_id = s.student_id
    LEFT JOIN exam_results er
        ON sc.student_class_id = er.student_class_id
        AND er.exam_id = ?
    WHERE sc.class = ?
    ORDER BY Total_marks DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $exam_id, $class_assigned);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
$subject_totals = [
    'English' => 0, 'Math' => 0, 'Kiswahili' => 0, 'Creative' => 0,
    'Enviromental' => 0, 'Religious' => 0
];
$subject_counts = [
    'English' => 0, 'Math' => 0, 'Kiswahili' => 0, 'Creative' => 0,
    'Enviromental' => 0, 'Religious' => 0
];
$total_score = 0;
$total_students = 0;

while ($row = $result->fetch_assoc()) {
    $students[] = $row;

    foreach ($subject_totals as $subject => &$total) {
        if ($row[$subject] !== null) {
            $total += $row[$subject];
            $subject_counts[$subject]++;
        }
    }

    // Consider only students with all marks entered
    if (array_sum(array_map(fn($sub) => $row[$sub] !== null, array_keys($subject_totals))) == count($subject_totals)) {
        $total_score += $row['Total_marks'];
        $total_students++;
    }
}

// Calculate Mean Scores for Each Subject
$subject_means = array_map(fn($total, $count) => $count > 0 ? round($total / $count, 2) : '-', $subject_totals, $subject_counts);

// Calculate the Total Mean
$total_mean = ($total_students > 0) ? round($total_score / $total_students, 2) : 0;
$stmt->close();
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

<h2>Mark List - <?php echo htmlspecialchars($exam_name); ?></h2>
<h2>Grade <?php echo htmlspecialchars($title); ?></h2>
<h2>Class Tutor: <?php echo htmlspecialchars($user['name']); ?></h2>
<p><strong>Total Mean Score:</strong> <?php echo $total_mean; ?></p>
<table>
<thead>
        <tr>
            <th>Rank</th>
            <th>Name</th>
            <?php foreach (array_keys($subject_totals) as $subject): ?>
                <th colspan="2"><?php echo $subject; ?></th>
            <?php endforeach; ?>
            <th>Total</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <?php foreach ($subject_totals as $subject => $_): ?>
                <th>Marks</th>
                <th>PL</th>
            <?php endforeach; ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $rank = 1;
        foreach ($students as $row) {
            echo "<tr>
                <td>" . $rank++ . "</td>
                <td>" . htmlspecialchars($row['Name']) . "</td>";

            foreach ($subject_totals as $subject => $_) {
                echo "<td>" . htmlspecialchars($row[$subject] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['PL_' . $subject] ?? '-') . "</td>";
            }

            echo "<td>" . htmlspecialchars($row['Total_marks']) . "</td>
            </tr>";
        }
        ?>
        <!-- Subject Mean Row -->
        <tr style="font-weight: bold; background-color: #f9f9f9;">
            <td colspan="2">Mean Score</td>
            <?php foreach ($subject_means as $mean): ?>
                <td colspan="2"><?php echo $mean; ?></td>
            <?php endforeach; ?>
            <td><?php echo $total_mean; ?></td>
        </tr>
    </tbody>
</table>
<br><br>
<!-- Print Button -->
<button onclick="printPage()" class="btn btn-secondary mb-3">Print</button>

</body>
</html>
