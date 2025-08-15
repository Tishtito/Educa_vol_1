<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   //fetch users class assigned details
   $class_assigned = $_SESSION["class_assigned"];

   $user_id = $_SESSION['id'];

   $sql = "SELECT name FROM users WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();

   $stmt->close();
   
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>home</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
</head>

<body>

<header class="header">
   
   <section class="flex">

      <a href="../home.php" class="logo">Educa.</a>

      <form action="#" method="post" class="search-form">
         <input type="text" name="search_box" required placeholder="search courses..." maxlength="100">
         <button type="submit" class="fas fa-search"></button>
      </form>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="search-btn" class="fas fa-search"></div>
         <div id="user-btn" class="fas fa-user"></div>
         <div id="toggle-btn" class="fas fa-sun"></div>
      </div>

      <div class="profile">
         <img src="images/pic-1.jpg" class="image" alt="">
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

<section class="teacher-profile">

    <?php
      require_once "db/database.php";

      if (isset($_GET['id'])) {
         $student_id = $_GET['id'];
         $exam_id = $_SESSION['exam_id']; // Assuming exam_id is stored in session

         // Prepare and execute a query to get the student name and marks
         $sql = "
               SELECT 
                  students.name AS student_name,
                  exam_results.Math,
                  exam_results.English,
                  exam_results.Kiswahili,
                  exam_results.Creative,
                  exam_results.SciTech,
                  exam_results.AgricNutri,
                  exam_results.SST,
                  exam_results.CRE
               FROM 
                  students
               LEFT JOIN 
                  exam_results 
               ON 
                  students.student_id = exam_results.student_id AND exam_results.exam_id = ?
               WHERE 
                  students.student_id = ?";

         $stmt = $conn->prepare($sql);
         $stmt->bind_param("ii", $exam_id, $student_id);
         $stmt->execute();
         $result = $stmt->get_result();

         // Check if a student with the given ID was found
         if ($result->num_rows > 0) {
               $student = $result->fetch_assoc();
         } else {
               // Redirect or display an error if no student is found
               header("Location: students.php");
               exit;
         }

         $stmt->close();
      } else {
         // Redirect or display an error if no ID is provided
         header("Location: students.php");
         exit;
      }

      $conn->close();
    ?>

   <h1 class="heading">Student Performance</h1>

   <div class="details">
      <div class="tutor">
         <img src="photos/students.png" alt="">
         <h3><?php echo htmlspecialchars($student['student_name']); ?> <a href="editstudent.php?id=<?php echo $student_id; ?>&exam_id=<?php echo $_SESSION['exam_id']; ?>"><i class="fa-solid fa-pen"></i></a></h3>
         <span>Student</span>
      </div>
      <div class="flex">
      <p><b>Total Marks:</b> 
         <?php 
            $total_marks = $student['Math'] + $student['English'] + $student['Kiswahili'] + $student['Creative'] + $student['SciTech'] + $student['AgricNutri'] + $student['SST'] + $student['CRE'];
            echo htmlspecialchars($total_marks);
         ?>
      </p>
      </div>
   </div>

</section>

<section class="marks">

   <div class="box-container">

      <div class="box">
        <p><b>Math:</b> <span><?php echo htmlspecialchars($student['Math'] ?? '-'); ?></span></p>
        <p><b>English:</b><span><?php echo htmlspecialchars($student['English'] ?? '-'); ?></span></p>
        <p><b>Kiswahili:</b><span><?php echo htmlspecialchars($student['Kiswahili'] ?? '-'); ?></span></p>
        <p><b>Creative:</b><span><?php echo htmlspecialchars($student['Creative'] ?? '-'); ?></span></p>
        <p><b>Sci & Tech:</b><span><?php echo htmlspecialchars($student['SciTech'] ?? '-'); ?></span></p>
        <p><b>Agric & Nutri:</b><span><?php echo htmlspecialchars($student['AgricNutri'] ?? '-'); ?></span></p>
        <p><b>SST:</b><span><?php echo htmlspecialchars($student['SST'] ?? '-'); ?></span></p>
        <p><b>CRE:</b><span><?php echo htmlspecialchars($student['CRE'] ?? '-'); ?></span></p>
      </div>

   </div>

</section>





<!--footer section -->
<footer class="footer">

   &copy; copyright @ 2025 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>
</body>