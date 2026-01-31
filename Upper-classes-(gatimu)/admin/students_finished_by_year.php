<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db/database.php';

/* -------------------------------------------------
   Validate Year
------------------------------------------------- */
if (!isset($_GET['year']) || !preg_match('/^\d{4}$/', $_GET['year'])) {
    die("Invalid year supplied.");
}

$year = $_GET['year'];

/* -------------------------------------------------
   Fetch Finished Students for the Selected Year
------------------------------------------------- */
$sql = "SELECT student_id, name, class, finished_at
        FROM students
        WHERE status = 'Finished'
          AND YEAR(updated_at) = ?
        ORDER BY updated_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finished Students - <?php echo htmlspecialchars($year); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="#" class="logo">
        <i class='bx bx-code-alt'></i>
        <div class="logo-name"><span>Upper</span>Admin</div>
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

<!-- Main Content -->
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
                <h1>Finished Students – <?php echo htmlspecialchars($year); ?></h1>
                <ul class="breadcrumb">
                    <li><a href="students_finished.php">Completed</a></li>
                    /
                    <li><a href="#" class="active"><?php echo htmlspecialchars($year); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Students Table -->
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
                                <th>Name</th>
                                <th>Class</th>
                                <th>Finished On</th>
                                <th rowspan="2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php $count = 1; ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td><?php echo date('Y', strtotime($student['finished_at'])); ?></td>
                                    <td><a href=''><span class='status process'>edit</span></a></td>
                                    <td><a href="student_all_results.php?student_id=<?php echo $student['student_id']; ?>"><span class="status delete">All Results</span></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">
                                    No students finished in <?php echo htmlspecialchars($year); ?>.
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
