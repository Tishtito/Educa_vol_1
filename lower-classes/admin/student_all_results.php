<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db/database.php';

/* ------------------------------------
   Validate student_id
------------------------------------ */
if (!isset($_GET['student_id']) || !ctype_digit($_GET['student_id'])) {
    die("Invalid student ID.");
}

$student_id = (int) $_GET['student_id'];

/* ------------------------------------
   Fetch Student Info
------------------------------------ */
$studentSql = "SELECT name, class FROM students WHERE student_id = ?";
$studentStmt = $conn->prepare($studentSql);
$studentStmt->bind_param("i", $student_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    die("Student not found.");
}

$student = $studentResult->fetch_assoc();
$studentStmt->close();

/* ------------------------------------
   Fetch Exam Results
------------------------------------ */
$sql = "SELECT er.*, e.exam_name
        FROM exam_results er
        LEFT JOIN exams e ON er.exam_id = e.exam_id
        WHERE er.student_id = ?
          AND er.deleted_at IS NULL
        ORDER BY er.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Results - <?php echo htmlspecialchars($student['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="#" class="logo">
        <i class='bx bx-code-alt'></i>
        <div class="logo-name"><span>JSS</span>Admin</div>
    </a>

    <ul class="side-menu">
        <li><a href="index.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
        <li><a href="analysis.php"><i class='bx bx-analyse'></i>Analytics</a></li>
        <li><a href="users.php"><i class='bx bx-group'></i>Teachers</a></li>
        <li class="active"><a href="students.php"><i class='bx bx-book-reader'></i>Students</a></li>
        <li><a href="reports.php"><i class='bx bxs-report'></i>Reports</a></li>
        <li><a href="settings.php"><i class='bx bx-cog'></i>Settings</a></li>
    </ul>

    <ul class="side-menu">
        <li>
            <a href="logout.php" class="logout">
                <i class='bx bx-log-out-circle'></i>
                Logout
            </a>
        </li>
    </ul>
</div>

<div class="content">

    <!-- Navbar -->
    <nav>
        <i class='bx bx-menu'></i>
        <form action="#">
            <div class="form-input">
                <input type="search" placeholder="Search...">
                <button class="search-btn" type="submit">
                    <i class='bx bx-search'></i>
                </button>
            </div>
        </form>
        <a href="#" class="profile">
            <img src="images/logo.png">
        </a>
    </nav>

    <main>

        <div class="header">
            <div class="left">
                <h1>Exam Results</h1>
                <ul class="breadcrumb">
                    <li><a href="students_finished.php">Finished Students</a></li>
                    /
                    <li><a href="#" class="active"><?php echo htmlspecialchars($student['name']); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></p>
        </div>


        <div class="bottom-data">
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-receipt'></i>
                        <h3>List Of Students</h3>
                        <i class='bx bx-filter'></i>
                        <i class='bx bx-search'></i>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam</th>
                                <th>Total Marks</th>
                                <th>Position</th>
                                <th>Stream Position</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($results)): ?>
                            <?php $i = 1; ?>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['exam_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
                                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                                    <td><?php echo htmlspecialchars($row['stream_position']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td><a href="student_exam_breakdown.php?result_id=<?php echo $row['result_id']; ?>"><span class='status process'>view</span></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">
                                    No exam results found for this student.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>

    </main>
</div>

<script src="js/index.js"></script>
</body>
</html>
