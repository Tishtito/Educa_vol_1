<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";

   $user_id = $_SESSION['id'];

   $sql = "SELECT name FROM users WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();

   $stmt->close();
   $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Learning Areas</title>
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

<section class="courses">

   <h1 class="heading">Learning Areas</h1>

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

   </div>

</section>


<footer class="footer">

   &copy; copyright @ 2024 by <span>mr. web designer</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

   
</body>
</html>