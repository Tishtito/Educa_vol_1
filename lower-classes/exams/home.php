<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   $class_assigned = $_SESSION['class_assigned'];
   $user_id = $_SESSION['id'];
   $title = ucwords(str_replace('_', ' ', $class_assigned));
   
   $sql = "SELECT name FROM users WHERE id = ?";
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
   <title>Teacher Dashboard - <?php echo htmlspecialchars($title); ?></title>
    <link rel="icon" type="image/x-icon" href="photos/logo.png">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
<?php

   if (isset($_SESSION['class_assigned'])) {
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
   require_once "calculation.php";
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
         <p class="role">Teacher</p>
         <a href="profile.php" class="btn">view profile</a>
         <div class="flex-btn">
            <?php if (!isset($user)) { ?>
            <a href="index.php" class="option-btn">Login</a>
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


<section class="courses">

   <h1 class="heading"><?php echo htmlspecialchars($title); ?></h1>

   <div class="box-container">

      <div class="box">
         <div class="thumb">
            <img src="photos/kiswahili.jpg" alt="">
         </div>
         <h3 class="title">Kiswahili</h3>
         <a href="subjects/kiswahili.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
         <div class="thumb">
            <img src="photos/math.png" alt="">
         </div>
         <h3 class="title">Math</h3>
         <a href="subjects/math.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
         <div class="thumb">
            <img src="photos/english.jpg" alt="">
         </div>
         <h3 class="title">English</h3>
         <a href="subjects/english.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
         <div class="thumb">
            <img src="photos/creative.jpg" alt="">
         </div>
         <h3 class="title">Creative Arts</h3>
         <a href="subjects/creative.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
         <div class="thumb">
            <img src="photos/religious.jpg" alt="">
         </div>
         <h3 class="title">Religious Activities</h3>
         <a href="subjects/religious.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
   
         <div class="thumb">
            <img src="photos/enviromental.jpg" alt="">
         </div>
         <h3 class="title">Enviromental</h3>
         <a href="subjects/enviromental.php" class="inline-btn">Enter Marks</a>
      </div>

      <div class="box">
   
         <div class="thumb">
            <img src="photos/creative.jpg" alt="">
         </div>
         <h3 class="title">Integrated Learning Area</h3>
         <a href="subjects/intergrated.php" class="inline-btn">Enter Marks</a>
      </div>

   </div>

   <div class="more-btn">
      <a href="learning_area.php" class="inline-option-btn">view all Learning Areas</a>
   </div>

</section>

<section class="home-grid">

   <h1 class="heading">Analysis</h1>

   <div class="box-container">
     <div class="box">
         <h3 class="title">Learning Areas Mean</h3>
         <p class="likes">English : <span><?php echo $english_mean; ?></span></p>
         <p class="likes">Kiswahili : <span><?php echo $kiswahili_mean; ?></span></p>
         <p class="likes">Math : <span><?php echo $math_mean; ?></span></p>
         <p class="likes">Creative : <span><?php echo $creative_mean; ?></span></p>
         <p class="likes">Religious : <span><?php echo $religious_mean; ?></span></p>
         <p class="likes">Enviromental : <span><?php echo $enviromental_mean; ?></span></p>
         <!--<a href="subjects/math.php" class="inline-btn">view Scores</a>-->
      </div>

      <!--<div class="box">
         <h3 class="title"></h3>
         <div class="flex">
            <a href="#"><i class="fas fa-code"></i><span>development</span></a>
            <a href="#"><i class="fas fa-chart-simple"></i><span>business</span></a>
            <a href="#"><i class="fas fa-pen"></i><span>design</span></a>
            <a href="#"><i class="fas fa-chart-line"></i><span>marketing</span></a>
            <a href="#"><i class="fas fa-music"></i><span>music</span></a>
            <a href="#"><i class="fas fa-camera"></i><span>photography</span></a>
            <a href="#"><i class="fas fa-cog"></i><span>software</span></a>
            <a href="#"><i class="fas fa-vial"></i><span>science</span></a>
         </div>
      </div>-->

      <div class="box">
         <h3 class="title">Mean Standard Score (MSS)</h3>
         <p class="likes">MSS: <span><?php echo round($meanScore, 2); ?></span></p>
         <a href="students.php" class="inline-btn">view Scores</a>
      </div>

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
   $connection->close();
?>

</body>
</html>