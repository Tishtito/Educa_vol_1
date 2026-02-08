<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'db/database.php';

/* --------------------------------
   Validate class
-------------------------------- */
if (!isset($_GET['class']) || empty($_GET['class'])) {
    die("Invalid class selected.");
}

$class = $_GET['class'];

/* --------------------------------
   Fetch Active Students
-------------------------------- */
$sql = "SELECT student_id, name, class, created_at
        FROM students
        WHERE status = 'Active'
          AND class = ?
          AND deleted_at IS NULL
        ORDER BY name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $class);
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
    <title>Active Students - <?php echo htmlspecialchars($class); ?></title>
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
                <h1>Active Students</h1>
                <ul class="breadcrumb">
                    <li><a href="students_active.php">Active</a></li>
                    /
                    <li><a href="#" class="active"><?php echo htmlspecialchars($class); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="bottom-data">
            <div class="orders">
                <div class="header">
                    <i class='bx bx-receipt'></i>
                    <h3>Active Students in <?php echo htmlspecialchars($class); ?></h3>
                    <i class='bx bx-filter'></i>
                    <i class='bx bx-search'></i>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Joined On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($students)): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['class']); ?></td>
                                <td><?php echo date('d M Y', strtotime($student['created_at'])); ?></td>
                                <td>
                                    <a href="student_profile.php?student_id=<?php echo $student['student_id']; ?>">
                                        <span class="status process">View</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">
                                No active students found in this class.
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
