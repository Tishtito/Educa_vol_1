<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }
   require_once "../db/database.php";

   $user_id = $_SESSION['id'];

   $sql = "SELECT name FROM class_teachers WHERE id = ?";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Math</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="../css/Tpanel.css">
</head>
<body>
    
<header class="header">
   
   <section class="flex">

      <a href="home.php" class="logo">Educa.</a>

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
      <img src="../photos/user1.png" class="image" alt="">
         <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
         <p class="role">Class Teacher</p>
         <a href="profile.php" class="btn">view profile</a>
         <div class="flex-btn">
            <?php if (!isset($user)) { ?>
            <a href="../login.php" class="option-btn">Login</a>
            <?php } else { ?>
            <a href="../logout.php" class="option-btn">Logout</a>
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
      <img src="../photos/user1.png" class="image" alt="">
      <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
      <p class="role">Class Teacher</p>
      <a href="../profile.php" class="btn">view profile</a>
   </div>

   <nav class="navbar">
      <a href="../home.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="../learning_area.php"><i class="fas fa-book-open"></i><span>Learning Area</span></a>
      <a href="../students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
      <a href="../mark_list.php"><i class="fas fa-award"></i><span>Mark List</span></a>
      <a href="../points_table.php"><i class="fas fa-thumbtack"></i><span>Rubric</span></a>
      <a href="https://"><i class="fas fa-laptop-code"></i><span>Examiner Portal</span></a>
   </nav>
   
</div>




<section class="marks-table">
   <h1 class="heading">Mathematics</h1>
   <?php
       // Ensure necessary session variables are set
      if (!isset($_SESSION["class_assigned"]) || !isset($_SESSION["exam_id"])) {
         die("Class or exam not assigned. Please log in and try again.");
      }

      $class_assigned = $_SESSION["class_assigned"];
      $exam_id = $_SESSION["exam_id"];

      // Query to join students and exam_results table
      $sql = "
         SELECT 
            students.student_id AS student_id, 
            students.name AS student_name, 
            exam_results.Math 
         FROM 
            students 
         LEFT JOIN 
            exam_results 
         ON 
            students.student_id = exam_results.student_id AND exam_results.exam_id = ?
         WHERE 
            students.class = ?
      ";

      $stmt = $conn->prepare($sql);

      if (!$stmt) {
            die("Error preparing query: " . $conn->error);
      }

      $stmt->bind_param("is", $exam_id, $class_assigned);
      $stmt->execute();
      $result = $stmt->get_result();
   ?>
      <div class="box-container">
         <table class="content-table">
            <thead>
               <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Marks</th>
               </tr>
            </thead>
            <tbody>
               <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                     <tr>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Math'] ?? '-'); ?></td>
                     </tr>
                  <?php endwhile; ?>
               <?php else: ?>
                  <tr>
                     <td colspan="4" style="text-align: center;">No students found in this class.</td>
                  </tr>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
</section>


<footer class="footer">

   &copy; copyright @ 2024 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<?php
   $stmt->close();
   $conn->close();
?>

<script src="../js/script.js"></script>

</body>
</html>