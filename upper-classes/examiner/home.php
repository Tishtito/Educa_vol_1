<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   $user_id = $_SESSION['examiner_id'];
   $exam_id = $_SESSION["exam_id"];
   
   $sql = "SELECT name FROM examiners WHERE examiner_id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();
?>



<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Examiner Dashboard</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
<?php

   /*if (isset($_SESSION['class_assigned'])) {
      $class_assigned = $_SESSION['class_assigned'];
  } else {
      // If not set, display the alert and redirect
      echo "<script>
              swal({
                  title: 'Class not assigned!',
                  text: 'Visit Admin for assistance!',
                  icon: 'warning',
                  button: 'OK',
              }).then(function() {
                  window.location.href = 'logout.php'; // Redirect after the alert
              });
            </script>";
      exit(); // Stop further execution of the script
  }
   
   */
?>

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
         <p class="role">Examiner</p>
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
      <p class="role">Examiner</p>
      <a href="profile.php" class="btn">view profile</a>
   </div>

   <nav class="navbar">
      <a href="home.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href=" "><i class="fas fa-chalkboard-teacher"></i><span>Class-Teacher Portal</span></a>
   </nav>
   </nav>

</div>

<?php
   require_once 'db/database.php';

   $examiner_id = $_SESSION['examiner_id'];

   // Fetch subjects assigned to the examiner
   $sql_subjects = "
      SELECT subjects.subject_id, subjects.name
      FROM examiner_subjects
      JOIN subjects ON examiner_subjects.subject_id = subjects.subject_id
      WHERE examiner_subjects.examiner_id = ?";
   $stmt_subjects = $conn->prepare($sql_subjects);
   $stmt_subjects->bind_param("i", $examiner_id);
   $stmt_subjects->execute();
   $result_subjects = $stmt_subjects->get_result();

   $subjects = [];
   while ($row = $result_subjects->fetch_assoc()) {
      $subjects[] = $row;
   }

   // Fetch classes assigned to the examiner
   $sql_classes = "
      SELECT classes.class_id, classes.class_name
      FROM examiner_classes
      JOIN classes ON examiner_classes.class_id = classes.class_id
      WHERE examiner_classes.examiner_id = ?";
   $stmt_classes = $conn->prepare($sql_classes);
   $stmt_classes->bind_param("i", $examiner_id);
   $stmt_classes->execute();
   $result_classes = $stmt_classes->get_result();

   if ($result_classes->num_rows === 0) {
      echo "<script>
              swal({
                  title: 'Class not assigned!',
                  text: 'Visit Admin for assistance!',
                  icon: 'warning',
                  button: 'OK',
              }).then(function() {
                  window.location.href = 'logout.php'; // Redirect after the alert
              });
            </script>";
      exit();
   }

   $classes = [];
   while ($row = $result_classes->fetch_assoc()) {
      $classes[] = $row;
   }

   // Subject details: Map subject names to image files and links
   $subject_details = [
      "Kiswahili" => ["image" => "photos/kiswahili.jpg"],
      "Math" => ["image" => "photos/math.png"],
      "English" => ["image" => "photos/english.jpg"],
      "creative" => ["image" => "photos/creative.jpg"],
      "CRE" => ["image" => "photos/religious.jpg"],
      "science and technology" => ["image" => "photos/science.png"],
      "social studies" => ["image" => "photos/sst.png"],
      "agriculture and nutrition" => ["image" => "photos/agriculture.jpg"],
      "CA,SST,CRE" => ["image" => "photos/ca-sst.jpeg"],
      "Integrated Science" => ["image" => "photos/integration.jpg"]
   ];
?>

<section class="courses">
   <div class="box-container">
      <?php
         foreach ($subjects as $subject) {
            $subject_name = htmlspecialchars($subject['name']);
            $subject_id = $subject['subject_id'];
            $subject_image = isset($subject_details[$subject_name]) ? $subject_details[$subject_name]['image'] : 'photos/default.jpg';

            foreach ($classes as $class) {
               $class_name = htmlspecialchars($class['class_name']);
               $class_id = $class['class_id'];

               // Link to a session-setting script
               $link = "subjects/$subject_name.php?subject_id=$subject_id&class_id=$class_id&class_name=$class_name";

               echo "
               <div class='box'>
                  <div class='thumb'>
                     <img src='$subject_image' alt='$subject_name Image'>
                  </div>
                  <h3 class='title'>$subject_name - $class_name</h3>
                  <a href='$link' class='inline-btn'>View Students</a>
               </div>";
            }
         }
      ?>
   </div>
</section>






<!--footer section -->
<footer class="footer">

   &copy; copyright @ 2025 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

<?php
   $stmt->close();
   $conn->close();
?>

</body>
</html>