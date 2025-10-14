<?php
    session_start();
    
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }
    require_once "db/database.php";
    include 'calculations/calculatemean.php';

    // Fetch all exams
    $sql = "SELECT exam_id, exam_name FROM exams ORDER BY date_created DESC"; // Adjust table and column names as per your database schema
    $result = $conn->query($sql);

    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    $exams = [];
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <title>Upper Classes Admin</title>
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
            <li class="active"><a href="analysis.php"><i class='bx bx-analyse'></i>Analytics</a></li>
            <li><a href="users.php"><i class='bx bx-group'></i>Teachers</a></li>
            <li><a href="reports.php"><i class='bx bxs-report'></i>Reports</a></li>
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
                    <ul class="breadcrumb">
                        <li><a href="#">
                                Analytics
                            </a></li>
                        /
                        <li><a href="#" class="active">Home</a></li>
                    </ul>
                </div>
            </div>

            <!-- Insights -->
            
                
            <!-- End of Insights -->

            <div class="bottom-data">
            <!-- Reminders -->
                <div class="reminders">
                    <div class="header">
                        <i class='bx bx-note'></i>
                        <h3>Class Marklists</h3>
                        <i class='bx bx-filter'></i>
                    </div>
                    
                    <ul class="task-list">   
                        <?php foreach ($exams as $exam): ?>
                            <a href="exam_details.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>">
                                <li class="completed">
                                    <div class="task-title">
                                        <i class='bx bx-book'></i>
                                        <p><?php echo htmlspecialchars($exam['exam_name']); ?><span></span></p>
                                    </div>
                                    <i class='bx bx-dots-vertical-rounded'></i>
                                </li>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="reminders">
                    <div class="header">
                        <i class='bx bx-note'></i>
                        <h3>Stream Marklists</h3>
                        <i class='bx bx-filter'></i>
                    </div>
                    
                    <ul class="task-list">   
                        <?php foreach ($exams as $exam): ?>
                            <a href="stream_details.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>">
                                <li class="completed">
                                    <div class="task-title">
                                        <i class='bx bx-book'></i>
                                        <p><?php echo htmlspecialchars($exam['exam_name']); ?><span></span></p>
                                    </div>
                                    <i class='bx bx-dots-vertical-rounded'></i>
                                </li>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <!-- End of Reminders-->
            </div>
        </main>
    </div>
    <script src="js/index.js"></script>
    <?php
        $conn->close();
    ?>
</body>

</html>