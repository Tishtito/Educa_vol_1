<?php
    session_start();
    
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }

    require_once 'db/database.php';

    // Fetch unique grades from the students table
    $sql = "SELECT DISTINCT class FROM students ORDER BY class ASC";
    $result = $conn->query($sql);

    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row['class'];
    }

    // Get the exam ID from the query parameter
    $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

    if ($exam_id === 0) {
        die("Invalid or missing exam ID.");
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <title>Reports</title>
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
            <li class="active"><a href="reports.php"><i class='bx bxs-report'></i>Reports</a></li>
            <li><a href="settings.php"><i class='bx bx-cog'></i>Settings</a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <?php if (!isset($user)) { ?>
                <a href="logout.php" class="logout">
                    <i class='bx bx-log-out-circle'></i>
                    Logout
                </a>
                <?php } else { ?>
                <a href="login.php" class="logout">
                <i class='bx bx-log-in-circle'></i>
                    Login
                </a>
                <?php } ?>
            </li>
        </ul>
    </div>
    <!-- End of Sidebar -->

    <!-- Main Content -->
    <div class="content">
        <!-- Navbar -->
        <nav>
            <i class='bx bx-menu'></i>
            <form action="#">
                <div class="form-input">
                    <input type="search" placeholder="Search...">
                    <button class="search-btn" type="submit"><i class='bx bx-search'></i></button>
                </div>
            </form>
            <input type="checkbox" id="theme-toggle" hidden>
            <label for="theme-toggle" class="theme-toggle"></label>
            <a href="#" class="notif">
                <i class='bx bx-bell'></i>
                <span class="count">12</span>
            </a>
            <a href="#" class="profile">
                <img src="images/logo.png">
            </a>
        </nav>

        <!-- End of Navbar -->

        <main>
            <div class="header">
                <div class="left">
                <h1>Reports</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">
                                Reports
                            </a></li>
                        /
                        <li><a href="#" class="active">Opener</a></li>
                    </ul>
                </div>
            </div>

            <!-- Insights -->
            <ul class="insights">

            <?php foreach ($grades as $grade): ?>
                <a href="report_table2.php?exam_id=<?php echo urlencode($exam_id); ?>&grade=<?php echo urlencode($grade); ?>">
                    <li>
                        <i class='bx bx-show-alt'></i>
                        <span class="info-2">
                            <p><?php echo htmlspecialchars($grade); ?></p>
                        </span>
                    </li>
                </a>
            <?php endforeach; ?>

            <!--
                <li><i class='bx bx-line-chart'></i>
                    <span class="info-2">
                        <p>Searches</p>
                    </span>
                </li>
                <li><i class='bx bx-dollar-circle'></i>
                    <span class="info-2">
                        <p>Total Sales</p>
                    </span>
                </li>
            -->
                
            </ul>
        </main>

    </div>



    <script src="js/index.js"></script>
</body>

</html>