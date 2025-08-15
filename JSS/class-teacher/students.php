<?php
   session_start();

   if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
      header("location: index.php");
      exit;
   }

   require_once "db/database.php";
   

   //Fetch users class assigned data
   $class_assigned = $_SESSION["class_assigned"];

   //Fetch the user data
   $user_id = $_SESSION['id'];

   $sql = "SELECT name FROM class_teachers WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $user = $result->fetch_assoc();

   // Get the assigned class and selected exam ID from the session
   $class_assigned = $_SESSION['class_assigned'] ?? null;
   $exam_id = $_SESSION['exam_id'] ?? null;

   if (!$class_assigned) {
      die("Error: No class assigned.");
   }

   if (!$exam_id) {
      die("Error: No exam selected.");
   }

   // Initialize search query
   $search_term = $_POST['search_box'] ?? '';

   // Fetch students based on search input
   $sql = "
      SELECT 
         students.student_id AS student_id, 
         students.name,
         students.class, 
         exam_results.English, 
         exam_results.Kiswahili, 
         exam_results.Math, 
         exam_results.Creative, 
         exam_results.Religious, 
         exam_results.Agriculture,
         (exam_results.English + exam_results.Kiswahili + exam_results.Math + 
            exam_results.Creative + exam_results.Religious + exam_results.Agriculture) AS total_marks
      FROM students
      LEFT JOIN exam_results 
         ON students.student_id = exam_results.student_id 
         AND exam_results.exam_id = ?
      WHERE students.class = ?";

   // Modify query if search term is entered
   if (!empty($search_term)) {
      $sql .= " AND students.name LIKE ?";
   }

   // Append sorting
   $sql .= " ORDER BY students.name ASC";

   $stmt = $conn->prepare($sql);
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Students</title>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
   <link rel="stylesheet" href="css/Tpanel.css">
   <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <style>
      .dropdown {
         position: relative;
         display: inline-block;
         width: 100%;
         margin-bottom: 10px;
      }

      .dropdown-btn {
         width: 80%;
         text-align: center;
         font-size: 13px;
      }

      .dropdown-content {
         display: none;
         position: absolute;
         background-color: #f9f9f9;
         min-width: 160px;
         box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
         z-index: 1;
         width: 100%;
      }

      .dropdown-content a {
         color: black;
         padding: 12px 16px;
         text-decoration: none;
         display: block;
      }

      .dropdown-content a:hover {
         background-color: #f1f1f1;
      }

      .dropdown:hover .dropdown-content {
         display: block;
      }
   </style>
</head>
<body>

<header class="header">
   
   <section class="flex">

      <a href="home.php" class="logo">Educa.</a>

      <form action="#" method="post" class="search-form">
         <input type="text" name="search_box" required placeholder="Search Student..." maxlength="100" value="<?php echo htmlspecialchars($search_term); ?>">
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

<section class="home-grid">

   <h1 class="heading">Option</h1>

   <div class="box-container">
      <div class="box">
         <h3 class="title">New Student</h3>
         <img src="photos/add-user.png" alt=""><br>
         <a href="register.php" class="inline-btn">Add</a>
      </div>
   </div>
</section>

<h1 class="heading2">List of Students</h1>

<?php
   if (!empty($search_term)) {
      $search_param = "%" . $search_term . "%";
      $stmt->bind_param("iss", $exam_id, $class_assigned, $search_param);
   } else {
      $stmt->bind_param("is", $exam_id, $class_assigned);
   }

   $stmt->execute();
   $result = $stmt->get_result();
?>

<section class="teachers">
   <div class="box-container">
      <?php if ($result->num_rows > 0): ?>
         <?php while ($student = $result->fetch_assoc()): ?>
               <div class="box">
                  <div class="tutor">
                     <img src="photos/students.png" alt="">
                     <div>
                           <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                           <span>Student</span>
                           <div class="dropdown">
                              <button class="dropdown-btn inline-option-btn">Change Class</button>
                              <div class="dropdown-content">
                                    <?php 
                                    // Fetch all classes from the database
                                    $classes_query = $conn->query("SELECT * FROM classes");
                                    while ($class = $classes_query->fetch_assoc()): 
                                    ?>
                                       <a href="#" onclick="updateClass(<?php echo $student['student_id']; ?>, '<?php echo $class['class_name']; ?>', '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>')">
                                          <?php echo htmlspecialchars($class['class_name']); ?>
                                       </a>
                                    <?php endwhile; ?>
                              </div>
                           </div>
                     </div>
                  </div>
                  <p>Total Marks: <span><?php echo $student['total_marks']; ?></span></p>
                  <p>Math: <span><?php echo $student['Math']; ?></span></p>
                  <p>English: <span><?php echo $student['English']; ?></span></p>
                  <p>Kiswahili: <span><?php echo $student['Kiswahili']; ?></span></p>
                  <p>Current Class: <span><?php echo htmlspecialchars($student['class']); ?></span></p>
                  
                  <a href="student_profile.php?id=<?php echo $student['student_id']; ?>" class="inline-btn">View Profile</a>
                  
                  <a href="#" data-id="<?php echo $student['student_id']; ?>" class="inline-btn2">Delete Student</a>

                  <form id="delete-form-<?php echo $student['student_id']; ?>" action="delete_student.php" method="POST" style="display: none;">
                     <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                  </form>
                  
                  <form id="update-class-form-<?php echo $student['student_id']; ?>" action="update_class.php" method="POST" style="display: none;">
                     <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                     <input type="hidden" name="new_class" id="new-class-<?php echo $student['student_id']; ?>" value="">
                  </form>
               </div>
         <?php endwhile; ?>
      <?php else: ?>
         <p style="text-align: center;">No students found.</p>
      <?php endif; ?>
   </div>
</section>

<script>
   function updateClass(studentId, newClass, studentName) {
      Swal.fire({
         title: 'Change Class?',
         html: `Are you sure you want to change <b>${studentName}</b>'s class to <b>${newClass}</b>?`,
         icon: 'question',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Yes, change it!'
      }).then((result) => {
         if (result.isConfirmed) {
               // Show loading indicator
               Swal.fire({
                  title: 'Updating...',
                  html: 'Please wait while we update the class',
                  allowOutsideClick: false,
                  didOpen: () => {
                     Swal.showLoading();
                  }
               });
               
               // Submit the form via AJAX
               const formData = new FormData();
               formData.append('student_id', studentId);
               formData.append('new_class', newClass);
               
               fetch('update_class.php', {
                  method: 'POST',
                  body: formData
               })
               .then(response => response.json())
               .then(data => {
                  Swal.fire({
                     title: data.success ? 'Success!' : 'Error!',
                     text: data.message,
                     icon: data.success ? 'success' : 'error',
                     confirmButtonText: 'OK'
                  }).then(() => {
                     if (data.success) {
                           // Reload the page to see changes
                           location.reload();
                     }
                  });
               })
               .catch(error => {
                  Swal.fire({
                     title: 'Error!',
                     text: 'An error occurred while updating the class',
                     icon: 'error',
                     confirmButtonText: 'OK'
                  });
               });
         }
      });
   }

   // Event listener for delete links
   document.addEventListener('click', function(event) {
    if (event.target.classList.contains('inline-btn2')) {
        event.preventDefault();
        
        const studentId = event.target.getAttribute('data-id');
        const studentName = event.target.closest('.box').querySelector('h3').textContent;
        
        Swal.fire({
            title: 'Are you sure?',
            html: `You are about to delete <b>${studentName}</b> permanently!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                Swal.fire({
                    title: 'Deleting...',
                    html: 'Please wait while we delete the student',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit the form via AJAX for better user experience
                const formData = new FormData();
                formData.append('student_id', studentId);
                
                fetch('delete_student.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: data.success ? 'Deleted!' : 'Error!',
                        text: data.message,
                        icon: data.success ? 'success' : 'error',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (data.success) {
                            // Reload the page to see changes
                            location.reload();
                        }
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while deleting the student',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    }
});
</script>



<footer class="footer">

   &copy; copyright @ 2025 by <span>Tishtito</span> | all rights reserved!

</footer>

<!-- custom js file link  -->
<script src="js/script.js"></script>

   
</body>
</html>