<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   $user_id = $_SESSION['examiner_id'];
      
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
   <title>Select Exam</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>

<header class="header">
   
   <section class="flex">

      <a href="../home.php" class="logo">Educa.</a>

      <!--<form action="#" method="post" class="search-form">
         <input type="text" name="search_box" required placeholder="search courses..." maxlength="100">
         <button type="submit" class="fas fa-search"></button>
      </form>-->

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
</div>

<section class="courses">

   <h1 class="heading">Select Exam </h1>

   <div class="box-container">
   <?php
      // Query to get all exams
      $query = "SELECT exam_id, exam_name, date_created FROM exams";
      $result = mysqli_query($conn, $query);

      if ($result && mysqli_num_rows($result) > 0) {
         while ($exam = mysqli_fetch_assoc($result)) {
            // Render each exam with a link to set the exam_id in the session
            echo '
            <div class="box">
               <div class="tutor">
                  <img src="photos/user.png" alt="">
                  <div class="info">
                     <h3>By Admin</h3>
                     <span>' . date("F j, Y", strtotime($exam['date_created'])) . '</span>
                  </div>
               </div>
               <h3 class="title">' . htmlspecialchars($exam['exam_name']) . '</h3>
               <a href="select_exam.php?exam_id=' . $exam['exam_id'] . '" class="inline-btn">Select Exam</a>
            </div>';
         }
      } else {
         echo '<p>No exams found.</p>';
      }
      ?>
   </div>
  
</section>
 

<script src="js/script.js"></script>
</body>
</html>