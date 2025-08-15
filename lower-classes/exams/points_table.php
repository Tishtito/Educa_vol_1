<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
 }

   require_once "db/database.php";

   //Fetch users class assigned data
   $class_assigned = $_SESSION["class_assigned"];

   //Fetch Users data
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <link rel="stylesheet" href="css/Tpanel.css">
</head>
<body>
<header class="header">
   
   <section class="flex">

      <a href="home.php" class="logo">Educa.</a>

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


<section class="marks-table">

    <?php
        require_once "db/database.php";

        // Fetch students and their marks for the selected exam
        $exam_id = $_SESSION['exam_id']; // Assume exam_id is stored in the session
        $sql = "
            SELECT 
                students.student_id AS student_id,
                students.name AS Name,
                COALESCE(exam_results.Math, 0) AS Math,
                COALESCE(exam_results.English, 0) AS English,
                COALESCE(exam_results.Kiswahili, 0) AS Kiswahili,
                COALESCE(exam_results.Creative, 0) AS Creative,
                COALESCE(exam_results.Religious, 0) AS Religious,
                COALESCE(exam_results.Enviromental, 0) AS Enviromental
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
        $stmt->bind_param("is", $exam_id, $class_assigned);
        $stmt->execute();
        $result = $stmt->get_result();

        // Function to calculate the grade based on the marks
        function calculateGrade($marks) {
            global $conn;
            if ($marks === NULL || $marks === '') {
                return '-'; // No marks provided
            }
            $sql = "SELECT grade FROM point_boundaries WHERE ? BETWEEN min_marks AND max_marks LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $marks);
            $stmt->execute();
            $result = $stmt->get_result();
            $grade = $result->fetch_assoc();
            return $grade ? $grade['grade'] : 'N/A'; // Return grade or N/A if not found
        }
    ?>

    <h1 class="heading">Points Scored</h1>
    <div class="box-container">
        <table class="content-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Math</th>
                    <th>English</th>
                    <th>Kiswahili</th>
                    <th>Creative</th>
                    <th>Religious</th>
                    <th>Enviromental</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['Name']); ?></td>
                            <td><?php echo calculateGrade($student['Math']); ?></td>
                            <td><?php echo calculateGrade($student['English']); ?></td>
                            <td><?php echo calculateGrade($student['Kiswahili']); ?></td>
                            <td><?php echo calculateGrade($student['Creative']); ?></td>
                            <td><?php echo calculateGrade($student['Religious']); ?></td>
                            <td><?php echo calculateGrade($student['Enviromental']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No students found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

 
    
<script src="js/script.js"></script>
</body>
</html>