<?php
   session_start();
    
   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   $user_id = $_SESSION['id'];
   
   $sql = "SELECT name FROM class_teachers WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();

   //Class assigned for Title
   $class_assigned = isset($_SESSION['class_assigned']) ? $_SESSION['class_assigned'] : 'Class not assigned';
   $title = ucwords(str_replace('_', ' ', $class_assigned));
   
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>register</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>

<header class="header">
   
   <section class="flex">

      <a href="../home.php" class="logo">Educa.</a>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="search-btn" class="fas fa-search"></div>
         <div id="user-btn" class="fas fa-user"></div>
         <div id="toggle-btn" class="fas fa-sun"></div>
      </div>

      <div class="profile">
         <img src="photos/user1.png" class="image" alt="">
         <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
         <p class="role">Teacher</p>
         <a href="profile.php" class="btn">view profile</a>
         <div class="flex-btn">
            <?php if (!isset($user)) { ?>
            <a href="login.php" class="option-btn">Login</a>
            <?php } else { ?>
            <a href="logout.php" class="option-btn">Logout</a>
            <?php } ?>
         </div>
      </div>

   </section>

</header>

<div class="side-bar">

   <div id="close-btn">
      <i class="fas fa-times"></i>
   </div>

   <div class="profile">
      <img src="photos/user1.png" class="image" alt="">
      <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
      <p class="role">Teacher</p>
      <a href="profile.php" class="btn">view profile</a>
   </div>

   <nav class="navbar">
      <a href="home.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="learning_area.php"><i class="fas fa-book-open"></i><span>Learning Area</span></a>
      <a href="students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
      <a href="mark_list.php"><i class="fas fa-award"></i><span>Mark List</span></a>
      <a href="points_table.php"><i class="fas fa-thumbtack"></i><span>Rubric</span></a>
   </nav>
   
</div>


<section class="form-container">

    <?php
        // Ensure student ID is retrieved correctly
        if (isset($_GET['id'])) {
            $student_id = $_GET['id']; 
        } elseif (isset($_POST['student_id'])) {
            $student_id = $_POST['student_id'];
        } else {
            die("Error: Student ID is missing.");
        }

        // Fetch student details
        $sql = "SELECT name FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if (!$student) {
            die("Error: Student not found.");
        }

        $existing_name = $student['name'];

        // Handle the form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
            $new_name = $_POST['name'];

            // Debugging: Check if values are set
            if (empty($student_id) || empty($new_name)) {
                die("Error: Missing student ID or name.");
            }

            // Update the student's name
            $sql = "UPDATE students SET name = ? WHERE student_id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }

            $stmt->bind_param("si", $new_name, $student_id);

            if ($stmt->execute()) {
                echo "<script>
                        swal({
                            title: 'Success!',
                            text: 'Student name updated successfully!',
                            icon: 'success',
                            button: 'OK'
                        }).then(function() {
                            window.location.href = 'student_profile.php?id=" . $student_id . "';
                        });
                    </script>";
            } else {
                echo "<div class='alert-danger'>Error updating student: " . $stmt->error . "</div>";
            }
        }
        ?>

        <form action="" method="post">
            <h3>Edit Student Details</h3>
            <p>Student's Name <span>*</span></p>
            <input type="text" name="name" value="<?php echo htmlspecialchars($existing_name); ?>" required maxlength="50" class="box">
            <input type="submit" value="Update" name="submit" class="btn">
            <button class="option-btn" type="button" onclick="window.location.href = 'student_profile.php?id=<?php echo $student_id; ?>'">Cancel</button>
        </form>

</section>


<footer class="footer">

   &copy; copyright @ 2025 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<?php
   $conn->close();
?>
   
</body>
</html>