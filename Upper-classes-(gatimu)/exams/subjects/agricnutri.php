<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "../db/database.php";

   $user_id = $_SESSION['id'];
   $exam_id        = $_SESSION['exam_id'] ?? null;
   $class_assigned = $_SESSION['class_assigned'] ?? null;
   $class_name     = $_SESSION['class_name'] ?? null;
   $subject        = "AgricNutri";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrated Learing Area</title>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="../css/Tpanel.css">
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
      <img src="../photos/user1.png" class="image" alt="">
      <h3 class="name"><?php echo htmlspecialchars($user['name']); ?></h3>
         <p class="role">Teacher</p>
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
      <p class="role">Teacher</p>
      <a href="../profile.php" class="btn">view profile</a>
   </div>

   <nav class="navbar">
      <a href="../home.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="../learning_area.php"><i class="fas fa-book-open"></i><span>Learning Area</span></a>
      <a href="../students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
      <a href="../mark_list.php"><i class="fas fa-award"></i><span>Mark List</span></a>
      <a href="../points_table.php"><i class="fas fa-thumbtack"></i><span>Rubric</span></a>
   </nav>
</div>




<section class="marks-table">
   <h1 class="heading">Agric & Nutri</h1>
   <?php
      if (!isset($_SESSION['marks_out_of6'])) {
         $_SESSION['marks_out_of6'] = null;
      }

      if ($_SESSION['marks_out_of6'] === null) {
         $sql = "SELECT marks_out_of FROM marks_out_of WHERE exam_id = ? AND subject = ?";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("is", $exam_id, $subject);
         $stmt->execute();
         $res = $stmt->get_result();
         if ($row = $res->fetch_assoc()) {
            $_SESSION['marks_out_of6'] = $row['marks_out_of'];
         }
         $stmt->close();
      }

      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_marks_out_of6'])) {
         $_SESSION['marks_out_of6'] = (int) $_POST['marks_out_of6'];

         $sql = "INSERT INTO marks_out_of (exam_id, subject, marks_out_of)
                  VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE marks_out_of = VALUES(marks_out_of)";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("isi", $exam_id, $subject, $_SESSION['marks_out_of6']);
         $stmt->execute();
         $stmt->close();

         echo "<script>
               setTimeout(function() {
                  swal({
                     title: 'Good job!',
                     text: 'Marks Out Of set to " . $_SESSION['marks_out_of6'] . "',
                     icon: 'success',
                     button: 'OK'
                  }).then(function() {
                     window.location.href = '#';
                  });
               }, 100);
            </script>
         ";
      } 
   ?>
   <form method="post">
      <h2>Marks out of: 
         <input type="number" name="marks_out_of6" value="<?php echo htmlspecialchars($_SESSION['marks_out_of6']); ?>" required>
         <button type="submit" name="set_marks_out_of6" class="inline-btn">Set</button>
      </h2>
   </form>

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
               s.student_id,
               s.name AS student_name,
               sc.student_class_id,
               er.AgricNutri
            FROM students s
            JOIN student_classes sc
               ON s.student_id = sc.student_id
            LEFT JOIN exam_results er
               ON sc.student_class_id = er.student_class_id
               AND er.exam_id = ?
            WHERE sc.class = ?
            ORDER BY s.name ASC
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
                  <th>Name</th>
                  <th>Marks(%)</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
               <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                     <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['AgricNutri'] ?? '-'); ?></td>
                        <td>
                           <a class="option-btn" href="../editmarks/editagricnutri.php?student_class_id=<?= $row['student_class_id'] ?>">
                              Edit
                           </a>
                        </td>
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

   &copy; copyright @ 2026 by <span>T&T designer</span> | all rights reserved!

</footer>

<?php
   $stmt->close();
   $conn->close();
?>

<script src="../js/script.js"></script>

</body>
</html>