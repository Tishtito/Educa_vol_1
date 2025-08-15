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
         <p class="role">Class Teacher</p>
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
      <p class="role">Class Teacher</p>
      <a href="profile.php" class="btn">view profile</a>
   </div>

   <nav class="navbar">
      <a href="home.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="learning_area.php"><i class="fas fa-book-open"></i><span>Learning Area</span></a>
      <a href="students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
      <a href="mark_list.php"><i class="fas fa-award"></i><span>Mark List</span></a>
      <a href="points_table.php"><i class="fas fa-thumbtack"></i><span>Rubric</span></a>
      <a href=" "><i class="fas fa-laptop-code"></i><span>Examiner Portal</span></a>
   </nav>
   
</div>


<section class="form-container">

   <form action="" method="post">
   <?php
    $class_assigned = $_SESSION['class_assigned'] ?? null;
    $exam_id = $_SESSION['exam_id'] ?? null;
    
    if (!$exam_id) {
        die("Error: No exam selected.");
    }
    
    // Handle the form submission
    if (isset($_POST['submit'])) {
        $student_name = $_POST['name'];
    
        // Step 1: Insert the new student into the `students` table
        $sql = "INSERT INTO students (name, class) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
    
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
    
        $stmt->bind_param("ss", $student_name, $class_assigned);
    
        if ($stmt->execute()) {
            // Step 2: Get the last inserted student ID
            $student_id = $conn->insert_id;
    
            // Debug: Check if the student ID was retrieved correctly
            if (!$student_id) {
                die("Error retrieving student ID: " . $conn->error);
            }
    
            // Step 3: Insert a new record into the `exam_results` table
            $insertExamResults = "INSERT INTO exam_results (student_id, exam_id, English, Kiswahili, Math, Creative, SciTech, AgricNutri, SST, CRE)
                     VALUES (?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)";
            $stmt2 = $conn->prepare($insertExamResults);
    
            if ($stmt2 === false) {
                die("Error preparing exam results statement: " . $conn->error);
            }
    
            $stmt2->bind_param("ii", $student_id, $exam_id);
    
            // Check if the insertion into `exam_results` is successful
            if ($stmt2->execute()) {
                echo "<script>
                        swal({
                            title: 'Good job!',
                            text: 'Student and exam results added!',
                            icon: 'success',
                            button: 'OK'
                        }).then(function() {
                            window.location.href = 'students.php';
                        });
                      </script>";
            } else {
                echo "<div class='alert-danger'>Error inserting exam results: " . $stmt2->error . "</div>";
            }
        } else {
            echo "<div class='alert-danger'>Error adding student: " . $stmt->error . "</div>";
        }
    }
    ?>
    ?>
      <h3>Add Student</h3>
      <p>Students name <span>*</span></p>
      <input type="text" name="name" placeholder="Enter Students name" required maxlength="50" class="box">
      <input type="submit" value="register new" name="submit" class="btn">
      <button class="option-btn" type="button" onclick="window.location.href = 'students.php'">Cancel</button>
   </form>

</section>


<footer class="footer">

   &copy; copyright @ 2024 by <span>mr. web designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<?php
   $conn->close();
?>
   
</body>
</html>