<?php
    session_start();
    
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
    }

    require_once "db/database.php";

    // Fetch classes and their grade
    $sql = "
        SELECT class_id, class_name, grade
        FROM classes
    ";

    $result = $conn->query($sql);

    if (!$result) {
    die("Invalid query: " . $conn->error);
    }

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <title>Manage Classes</title>
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
            <li><a href="reports.php"><i class='bx bxs-report'></i>Reports</a></li>
            <li class="active"><a href="settings.php"><i class='bx bx-cog'></i>Settings</a></li>
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
                    <h1>Classes</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">
                                Settings
                            </a></li>
                        /
                        <li><a href="#" class="active">classes</a></li>
                    </ul>
                </div>
            </div>
            
            <!--Table for teachers-->
            <div class="bottom-data">
                <div class="orders">
                    <div class="header">
                        <i class='bx bx-receipt'></i>
                        <h3>List Of Classes</h3>
                        <i class='bx bx-filter'></i>
                        <i class='bx bx-search'></i>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            // Loop through the results and display the data in the table
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td><p>" . htmlspecialchars($row['class_name']) . "</p></td>
                                        <td><a href='#' class='delete-link' data-id='" . htmlspecialchars($row['class_id']) . "'><span class='status delete'>delete</span></a></td>                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Reminders -->
                <div class="reminders">
                    <div class="header">
                        <i class='bx bx-note'></i>
                        <h3>Add New Class</h3>
                        <i class='bx bx-filter'></i>
                        
                    </div>
    
                    <ul class="task-list">
                        <a href="add_class.php">
                            <li class="completed">
                                <div class="task-title">
                                    <i class='bx bx-plus'></i>
                                    <p>Add<span></span></p>
                                </div>
                                <i class='bx bx-dots-vertical-rounded'></i>
                            </li>
                        </a>
                    </ul>
                </div>
                <!-- End of Reminders-->
            </div>
        </main>

    </div>

    <?php
        $conn->close();
    ?>
    
    <script src="js/index.js"></script>
    <script>
    // Event listener for delete links
    document.querySelectorAll('.delete-link').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior

            // Get the teacher ID from the data attribute
            var classId = this.getAttribute('data-id');

            // Trigger the SweetAlert confirmation
            swal({
                title: "Caution!",
                text: "Are you sure you want to delete?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then(function(isConfirmed) {
                if (isConfirmed) {
                    // Redirect to the delete page if confirmed
                    window.location.href = "delete_class.php?id=" + classId;
                }
            });
        });
    });
</script>
</body>

</html>