<?php
   session_start();

   if (!isset($_SESSION["id"])) {
      header("Location: index.php");
      exit();
   }

   require_once "db/database.php";

   $class_assigned = $_SESSION['class_assigned']; 
   $title = ucwords(str_replace('_', ' ', $class_assigned));
   $user_id = $_SESSION['id'];

   $sql = "SELECT name FROM class_teachers WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();
   
   // SQL query to count the total number of students in the assigned class
   $sql = "SELECT COUNT(*) AS total_students FROM students WHERE class = ?";

   // Prepare the statement
   $stmt = $conn->prepare($sql);

   if (!$stmt) {
      die("Error preparing query: " . $conn->error);
   }
   $stmt->bind_param("s", $class_assigned);
   $stmt->execute();
   $result = $stmt->get_result();

   if ($result) {
      $row = $result->fetch_assoc();
      $total_students = $row['total_students'];
   } else {
      echo "Error: " . $conn->error;
   }

      $stmt->close();
      $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
</head>
<body>

<header class="header">
   
   <section class="flex">

      <a href="home.html" class="logo">Educa.</a>

      <form action="search.html" method="post" class="search-form">
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
      <a href="https://"><i class="fas fa-laptop-code"></i><span>Examiner Portal</span></a>
   </nav>

</div>

<section class="user-profile">

   <h1 class="heading">your profile</h1>

   <div class="info">

      <div class="user">
         <img src="photos/user1.png" alt="">
         <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
         <p>Teacher</p>
         <a href="update.html" class="inline-btn">update profile</a>
      </div>
   
      <div class="box-container">
   
         <div class="box">
            <div class="flex">
               <i class="fas fa-bookmark"></i>
               <div>
                  <span><?php echo htmlspecialchars($title); ?></span>
                  <p>Number of Pupils <span><?php echo htmlspecialchars($total_students); ?></span></p>
               </div>
            </div>
            <a href="students.php" class="inline-btn">view Pupils</a>
         </div>
   
      </div>
   </div>

</section>


<footer class="footer">

   &copy; copyright @ 2024 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

   
</body>
</html>




