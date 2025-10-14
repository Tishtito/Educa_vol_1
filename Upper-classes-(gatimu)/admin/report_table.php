<?php
require_once "db/database.php"; // Database connection

// Get class and exam from URL
$class = isset($_GET['grade']) ? $_GET['grade'] : '';
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : '';

if ($class && $exam_id) {
    // Fetch students in the selected class who have results in the selected exam
    $query = "SELECT students.student_id, students.name, students.class 
              FROM students
              INNER JOIN exam_results ON students.student_id = exam_results.student_id
              WHERE students.class = ? AND exam_results.exam_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $class, $exam_id); // 's' for string (class), 'i' for integer (exam_id)
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch students into an array
    $students = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Invalid class or exam selected.";
    exit();
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
            <div class="logo-name"><span>JSS</span>Ngure</div>
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
                        <li><a href="#" class="active">Mid-Term</a></li>
                    </ul>
                </div>
            </div>

            <!-- Insights -->
            
                
            <!-- End of Insights -->
            <div class="bottom-data">
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-receipt'></i>
                        <h3>Students</h3>
                        <span class="print" id="printAll">Print All</span>
                        <i class='bx bx-filter'></i>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td>
                                        <a href="one_exam_report.php?student_id=<?php echo $student['student_id']; ?>&exam_id=<?php echo $exam_id; ?>" class="view-report">
                                            <span class="status process">View</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Reminders -->
                
            <!-- End of Reminders-->
            </div>
        </main>
    </div>
    <script src="js/index.js"></script>
    <script>
        document.getElementById("printAll").addEventListener("click", function () {
            let students = <?php echo json_encode($students); ?>; // Get student data from PHP
            let examId = <?php echo json_encode($exam_id); ?>; // Get exam ID

            if (!students.length) {
                alert("No students to print!");
                return;
            }

            let printFrame = document.getElementById("printFrame");
            let frameDoc = printFrame.contentDocument || printFrame.contentWindow.document;

            // Start building the HTML content
            let content = "<html><head><title>All Reports</title>";
            content += '<link rel="stylesheet" href="css/report.css">'; // Add your CSS
            content += "</head><body>";

            // Loop through each student and create their report
            students.forEach(student => {
                content += '<iframe src="one_exam_report.php?student_id=' + student.student_id + '&exam_id=' + examId + '" style="width:100%; height:1550px; border:0;"></iframe>';
                content += '<div style="page-break-before: always;"></div>'; // Ensures each report is on a new page
            });

            content += "</body></html>";

            // Write the content to the iframe
            frameDoc.open();
            frameDoc.write(content);
            frameDoc.close();

            // Wait for the reports to load, then print
            setTimeout(() => {
                printFrame.contentWindow.print();
            }, 2000); // Adjust delay if needed
        });
    </script>
 <iframe id="printFrame" style="display: none;"></iframe>  
</body>
</html>
