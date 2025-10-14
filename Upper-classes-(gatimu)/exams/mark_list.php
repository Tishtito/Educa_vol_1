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

// Fetch teacher's name
$sql = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Validate parameters
if (empty($exam_id)) {
    die("Invalid or missing parameters.");
}

// Fetch the exam name
$sql = "SELECT exam_name FROM exams WHERE exam_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$exam_data = $result->fetch_assoc();
$exam_name = $exam_data['exam_name'] ?? "Unknown Exam";
$stmt->close();

$title = ucwords(str_replace('_', ' ', $class_assigned));

// Compute and Store Total Marks and Rank
$sql = "
    UPDATE exam_results er
    JOIN (
        SELECT student_id, 
            COALESCE(English, 0) + COALESCE(Math, 0) + COALESCE(Kiswahili, 0) +
            COALESCE(Creative, 0) + COALESCE(SciTech, 0) + COALESCE(AgricNutri, 0) + 
            COALESCE(SST, 0) + COALESCE(CRE, 0) AS total_marks
        FROM exam_results
        WHERE exam_id = ?
    ) tm ON er.student_id = tm.student_id
    SET er.total_marks = tm.total_marks";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$stmt->close();

// Initialize ranking
$conn->query("SET @rank = 0");

// Update Rank
$sql = "
    UPDATE exam_results er
    JOIN (
        SELECT student_id, total_marks, (@rank := @rank + 1) AS position
        FROM exam_results
        WHERE exam_id = ?
        ORDER BY total_marks DESC
    ) r ON er.student_id = r.student_id
    SET er.position = r.position";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$stmt->close();

// Fetch student marks and performance levels
$sql = "
    SELECT 
        s.student_id, s.name AS Name, 
        er.English, (SELECT ab FROM point_boundaries WHERE er.English BETWEEN min_marks AND max_marks LIMIT 1) AS PL_English,
        er.Math, (SELECT ab FROM point_boundaries WHERE er.Math BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Math,
        er.Kiswahili, (SELECT ab FROM point_boundaries WHERE er.Kiswahili BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Kiswahili,
        er.Creative, (SELECT ab FROM point_boundaries WHERE er.Creative BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Creative,
        er.SciTech, (SELECT ab FROM point_boundaries WHERE er.SciTech BETWEEN min_marks AND max_marks LIMIT 1) AS PL_SciTech,
        er.AgricNutri, (SELECT ab FROM point_boundaries WHERE er.AgricNutri BETWEEN min_marks AND max_marks LIMIT 1) AS PL_AgricNutri,
        er.SST, (SELECT ab FROM point_boundaries WHERE er.SST BETWEEN min_marks AND max_marks LIMIT 1) AS PL_SST,
        er.CRE, (SELECT ab FROM point_boundaries WHERE er.CRE BETWEEN min_marks AND max_marks LIMIT 1) AS PL_CRE,
        er.total_marks
    FROM 
        students s
    LEFT JOIN 
        exam_results er ON s.student_id = er.student_id AND er.exam_id = ?
    WHERE 
        s.class = ?
    ORDER BY 
        er.total_marks DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("is", $exam_id, $class_assigned);
$stmt->execute();
$result = $stmt->get_result();

// Calculate mean scores
$students = [];
$subject_totals = [];
$subject_counts = [];
$total_score = 0;
$total_students = 0;

$subjects = ['English', 'Math', 'Kiswahili', 'Creative', 'SciTech', 'AgricNutri', 'SST', 'CRE'];

foreach ($subjects as $subject) {
    $subject_totals[$subject] = 0;
    $subject_counts[$subject] = 0;
}

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

$subject_means = array_map(fn($total, $count) => $count > 0 ? round($total / $count, 2) : 0, $subject_totals, $subject_counts);
$total_mean = ($total_students > 0) ? round($total_score / $total_students, 2) : 0;

// Fetch previous mean scores
$sql = "SELECT * FROM exam_mean_scores WHERE class = ? AND exam_id < ? ORDER BY exam_id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $class_assigned, $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$prev_means = $result->fetch_assoc();
$stmt->close();

$prev_subject_means = array_map(fn($sub) => $prev_means[$sub] ?? 0, $subjects);
$prev_total_mean = $prev_means['total_mean'] ?? 0;

// Calculate deviations
$deviation_subjects = array_map(fn($current, $previous) => round($current - $previous, 2), $subject_means, $prev_subject_means);
$total_mean_deviation = round($total_mean - $prev_total_mean, 2);
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

    <div class="row">
        <div class="column left">
            <h2>Mark List - <?php echo htmlspecialchars($exam_name); ?></h2>
            <h2>Grade <?php echo htmlspecialchars($title); ?></h2>
            <h2>Class Tutor: <?php echo htmlspecialchars($user['name']); ?></h2>
            <p><strong>Total Mean Score:</strong> <?php echo $total_mean; ?></p>
        </div>

        <div class="column right">
            <img src="photos/logo.png" alt="" width="250px" height="200px">
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th rowspan="2">Rank</th>
                <th rowspan="2">Name</th>
                <?php foreach (array_keys($subject_totals) as $subject): ?>
                    <th colspan="2"><?php echo $subject; ?></th>
                <?php endforeach; ?>
                <th rowspan="2">Total Marks</th>
            </tr>
            <tr>
                <?php foreach ($subject_totals as $subject => $_): ?>
                    <th>Marks</th>
                    <th>PL</th>
                <?php endforeach; ?>
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
            
                echo "<td>" . htmlspecialchars($row['total_marks']) . "</td>";
            
                echo "</tr>";
            }
            ?>
            <!-- Subject Mean Row -->
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="2">Mean Score</td>
                <?php foreach ($subject_means as $mean): ?>
                    <td colspan="2"><?php echo $mean; ?></td>
                <?php endforeach; ?>
                <td colspan="2"><?php echo $total_mean; ?></td>
            </tr>

            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="2">Previous Mean</td>
                <?php foreach ($prev_subject_means as $mean): ?>
                    <td colspan="2"><?php echo $mean; ?></td>
                <?php endforeach; ?>
                <td colspan="2"><?php echo $prev_total_mean; ?></td>
            </tr>

            <tr style="font-weight: bold; background-color: #f2dede;">
                <td colspan="2">Deviation</td>
                <?php foreach ($deviation_subjects as $deviation): ?>
                    <td colspan="2"><?php echo $deviation; ?></td>
                <?php endforeach; ?>
                <td colspan="2"><?php echo $total_mean_deviation; ?></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
