<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }
   require_once "../db/database.php";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Technical</title>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
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
      <a href="https://"><i class="fas fa-chalkboard-teacher"></i><span>Class-Teacher Portal</span></a>
   </nav>
   
</div>




<section class="marks-table">
   <h1 class="heading">Pre-Technical</h1>
   <?php
      // Initialize session value if not set
      if (!isset($_SESSION['marks_out_of8'])) {
         $_SESSION['marks_out_of8'] = null;
      }

      if (isset($_POST['set_marks_out_of8'])) {
         $_SESSION['marks_out_of8'] = intval($_POST['marks_out_of8']); // Store the new value

         // Show SweetAlert after setting the value
         echo "<script>
            setTimeout(function() {
                  swal({
                     title: 'Good job!',
                     text: 'Marks Out Of set to " . $_SESSION['marks_out_of8'] . "',
                     icon: 'success',
                     button: 'OK'
                  }).then(function() {
                     window.location.href = '#'; // Reload page to reflect changes
                  });
            }, 100);
         </script>";
      }
   ?>
   <form method="post">
      <h2>Marks out of: 
         <input type="number" name="marks_out_of8" value="<?php echo $_SESSION['marks_out_of8']; ?>" required>
         <button type="submit" name="set_marks_out_of8" class="inline-btn">Set</button>
      </h2>
   </form>

   <?php
      // Capture query parameters and set sessions
      if (isset($_GET['subject_id']) && isset($_GET['class_id']) && isset($_GET['class_name'])) {
         $_SESSION['subject_id'] = intval($_GET['subject_id']);
         $_SESSION['class_id'] = intval($_GET['class_id']);
         $_SESSION['class_name'] = htmlspecialchars($_GET['class_name']);
      } else {
         die("Required parameters are missing. Please go back and select a class.");
      }

      // Example: Use sessions to fetch students and display the content for the subject
      $class_name = $_SESSION['class_name'];
      $subject_id = $_SESSION['subject_id'];
      $exam_id = $_SESSION['exam_id'] ?? null; // Optional if needed for results


      // Query to join students and exam_results table
      $sql = "
         SELECT 
            students.student_id AS student_id, 
            students.name AS student_name, 
            exam_results.Technical 
         FROM 
            students 
         LEFT JOIN 
            exam_results 
         ON 
            students.student_id = exam_results.student_id AND exam_results.exam_id = ?
         WHERE 
            students.class = ?
            ORDER BY students.name ASC
      ";

      $stmt = $conn->prepare($sql);
      if (!$stmt) {
         die("Error preparing query: " . $conn->error);
      }
      $stmt->bind_param("is", $exam_id, $class_name);
      $stmt->execute();
      $result = $stmt->get_result();
   ?>
      <div class="box-container">
         <table class="content-table">
            <thead>
               <tr>
                  <th>Name</th>
                  <th>Marks</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
               <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                     <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['Technical'] ?? '-'); ?></td>
                        <td>
                           <a class="option-btn" href="../editmarks/edittech.php?student_id=<?php echo htmlspecialchars($row['student_id']); ?>&exam_id=<?php echo htmlspecialchars($exam_id); ?>">
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

   &copy; copyright @ 2025 by <span>Tishtito designer</span> | all rights reserved!

</footer>

<?php
   $stmt->close();
   $conn->close();
?>

<script src="../js/script.js"></script>

</body>
</html>