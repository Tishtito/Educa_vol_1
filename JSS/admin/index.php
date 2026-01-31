<?php
    session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }

    // Create a connection
    require_once "db/database.php";

    $grades = ["grade_1", "grade_2", "grade_3"];
    $mssList = [];

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
    <title>JSS Admin</title>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-code-alt'></i>
            <div class="logo-name"><span>JSS</span>Admin</div>
        </a>
        <ul class="side-menu">
            <li class="active"><a href="index.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
            <li><a href="analysis.php"><i class='bx bx-analyse'></i>Analytics</a></li>
            <li><a href="users.php"><i class='bx bx-group'></i>Teachers</a></li>
            <li><a href="students.php"><i class='bx bx-book-reader'></i>Students</a></li>
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
                    <h1>Dashboard</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">
                                Analytics
                            </a></li>
                        /
                        <li><a href="#" class="active">Home</a></li>
                    </ul>
                </div>
            </div>

            <div class="header">
                <div class="left">
                    <h2>Mean Score</h2>
                </div>
            </div>

            <!-- Insights -->
            <?php
                $total_students = 0;
                $sql = "SELECT COUNT(*) AS student_count FROM students";
                $result = $conn->query($sql);

                if ($result && $row = $result->fetch_assoc()) {
                    $total_students = $row['student_count'];
                }
            ?>
            <ul class="insights">
                <li>
                    <i class='bx bx-calendar-check'></i>
                    <span class="info">
                        <p>Total Number of Students</p>
                        <h3>
                            <?php echo htmlspecialchars($total_students); ?>                   
                        </h3>
                    </span>
                </li>

                <?php
                    $total_examiners = 0;
                    $sql = "SELECT COUNT(*) AS examiner_count FROM examiners";
                    $result = $conn->query($sql);

                    if ($result && $row = $result->fetch_assoc()) {
                        $total_examiners = $row['examiner_count'];
                    }
                ?>
                <li><i class='bx bx-show-alt'></i>
                    <span class="info">
                        <p>Total Number of Teachers</p>
                        <h3>
                        <?php echo htmlspecialchars($total_examiners); ?>
                        </h3>
                    </span>
                </li>
            </ul>
                
            <!-- End of Insights -->

            <div class="bottom-data">
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-receipt'></i>
                        <h3>Top Students</h3>
                        <i class='bx bx-filter'></i>
                        <i class='bx bx-search'></i>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Date</th>
                                <th>Total Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            require_once "calculations/checkexams.php";
                            foreach ($check_exams as $exam): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['Name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['date']); ?></td>
                                <td><?php echo htmlspecialchars($exam['Totalstudents']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Reminders -->
                <div class="reminders">
                    <div class="header">
                        <i class='bx bx-note'></i>
                        <h3>MSS</h3>
                        <i class='bx bx-filter'></i>
                        
                    </div>
                    <ul class="task-list">   
                        <?php foreach ($exams as $exam): ?>
                            <a href="mss_details.php?exam_id=<?php echo urlencode($exam['exam_id']); ?>">
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
</body>

</html>