<?php
    session_start();
    
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }
    require_once "db/database.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <title>Upper Admin</title>
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
                        <li><a href="#" class="active">Home</a></li>
                    </ul>
                </div>
            </div>

            <!-- Insights -->
            
                
            <!-- End of Insights -->

            <div class="bottom-data">
                <!-- Students card -->
                <div class="reminders">
                    <div class="header">
                        <i class='bx bx-note'></i>
                        <h3>Students</h3>
                        <i class='bx bx-filter'></i>
                    </div>

                    <?php
                    // Fetch counts from database
                    $active_count = 0;
                    $finished_count = 0;

                    $result = $conn->query("SELECT status, COUNT(*) as total FROM students GROUP BY status");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            if (strtolower($row['status']) === 'active') {
                                $active_count = $row['total'];
                            } elseif (strtolower($row['status']) === 'finished') {
                                $finished_count = $row['total'];
                            }
                        }
                    }
                    ?>

                    <ul class="task-list">   
                        <!-- Active students -->
                        <a href="students_active.php">
                            <li class="completed">
                                <div class="task-title">
                                    <i class='bx bx-user'></i>
                                    <p>Active Students: <span><?php echo $active_count; ?></span></p>
                                </div>
                                <i class='bx bx-chevron-right'></i>
                            </li>
                        </a>

                        <!-- Finished students -->
                        <a href="students_finished.php">
                            <li class="completed">
                                <div class="task-title">
                                    <i class='bx bx-flag'></i>
                                    <p>Finished Students: <span><?php echo $finished_count; ?></span></p>
                                </div>
                                <i class='bx bx-chevron-right'></i>
                            </li>
                        </a>
                    </ul>
                </div>
                <!-- End of Students card -->
            </div>
        </main>
    </div>
    <script src="js/index.js"></script>
    <?php
        $conn->close();
    ?>
</body>

</html>