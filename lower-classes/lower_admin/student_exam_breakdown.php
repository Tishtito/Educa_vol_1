<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db/database.php';

/* ------------------------------------
   Validate result_id
------------------------------------ */
if (!isset($_GET['result_id']) || !ctype_digit($_GET['result_id'])) {
    die("Invalid result ID.");
}

$result_id = (int) $_GET['result_id'];

/* ------------------------------------
   Fetch Exam Result + Student + Exam Info
------------------------------------ */
$sql = "SELECT 
            er.*,
            s.name AS student_name,
            s.class AS student_class,
            e.exam_name
        FROM exam_results er
        JOIN students s ON er.student_id = s.student_id
        LEFT JOIN exams e ON er.exam_id = e.exam_id
        WHERE er.result_id = ?
          AND er.deleted_at IS NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $result_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Exam result not found.");
}

$data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Breakdown</title>
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
                <h1>Exam Breakdown</h1>
                <ul class="breadcrumb">
                    <li><a href="students_finished.php">Finished Students</a></li>
                    /
                    <li><a href="student_all_results.php?student_id=<?php echo $data['student_id']; ?>">All Results</a></li>
                    /
                    <li><a href="#" class="active"><?php echo htmlspecialchars($data['exam_name']); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Student Info -->
        <div class="card">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($data['student_name']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($data['student_class']); ?></p>
            <p><strong>Exam:</strong> <?php echo htmlspecialchars($data['exam_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($data['created_at'])); ?></p>
        </div>

        <!-- Subject Breakdown -->
        <div class="bottom-data">
            <div class="orders">
                <div class="header">
                    <i class='bx bx-receipt'></i>
                    <h3>Subject Breakdown</h3>
                    <i class='bx bx-filter'></i>
                    <i class='bx bx-search'></i>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subjects = [
                            'Math' => $data['Math'],
                            'English' => $data['English'],
                            'Kiswahili' => $data['Kiswahili'],
                            'Technical' => $data['Technical'],
                            'Agriculture' => $data['Agriculture'],
                            'Creative' => $data['Creative'],
                            'Religious' => $data['Religious'],
                            'SST' => $data['SST'],
                            'Science' => $data['Science']
                        ];

                        foreach ($subjects as $subject => $mark):
                            if ($mark !== null):
                        ?>
                        <tr>
                            <td><?php echo $subject; ?></td>
                            <td><?php echo $mark; ?></td>
                        </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total Marks</th>
                            <th><?php echo $data['total_marks']; ?></th>
                        </tr>
                        <tr>
                            <th>Position</th>
                            <th><?php echo $data['position']; ?></th>
                        </tr>
                        <tr>
                            <th>Stream Position</th>
                            <th><?php echo $data['stream_position']; ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </main>
</div>

<script src="js/index.js"></script>
</body>
</html>

