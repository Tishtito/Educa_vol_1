<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher</title>
    <link rel="stylesheet" href="css/form.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
    
    <div class="form-container">
        <form method="post" action="" class="form">
        <h3>Edit Teacher Information</h3><hr>
        <?php
            // Start session to check if the user is logged in (optional)
            session_start();

            // Include database connection
            require_once "db/database.php";

            // Check if 'id' is passed in the URL
            if (isset($_GET['id'])) {
                $teacher_id = $_GET['id'];

                // Fetch current teacher details
                $sql = "SELECT * FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $teacher_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $teacher = $result->fetch_assoc();

                // If form is submitted, update the teacher's data
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $name = $_POST['name'];
                    $password = $_POST['password'];
                    $class_assigned = $_POST['class'];

                    // Hash the password before updating (optional, if the password was updated)
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    } else {
                        $hashed_password = $teacher['password']; // Keep the old password if not updated
                    }

                    // Update the teacher's information in the database
                    $update_sql = "UPDATE users SET name = ?, password = ?, class_assigned = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssi", $name, $hashed_password, $class_assigned, $teacher_id);

                    if ($update_stmt->execute()) {
                        // Redirect after successful update
                        echo "<script>
                                swal({
                                    title: 'Good job!',
                                    text: 'Teacher updated successfully!',
                                    icon: 'success',
                                    button: 'OK',
                                }).then(function() {
                                    window.location.href = 'users.php'; // Redirect after the alert
                                });
                            </script>";
                        exit();
                    } else {
                        echo "<script>
                                swal({
                                    title: 'Error!',
                                    text: 'Something went wrong!',
                                    icon: 'error',
                                    button: 'OK',
                                }).then(function() {
                                    window.location.href = 'users.php'; // Redirect after the alert
                                });
                            </script>" . $conn->error;
                    }
                }
            } else {
                // Redirect if no teacher ID is provided
                header("Location: users.php?message=No teacher selected for editing");
                exit();
            }

            $conn->close();
        ?>
            <div class="input">
                <label for="name">Name</label>
                <input type="text" name="name" id="name"  value="<?php echo htmlspecialchars($teacher['name']); ?>" class="form-control" required>

                <label for="password">New Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="leave blank to keep current password">
            </div>

            <div class="class-box">
                <h4> Select Class</h4>
                <div class="class-options">
                    <div class="class">
                        <label for="male">1 Blue</label>
                        <input type="radio" name="class" value="1_blue"/>
                    </div>

                    <div class="class">
                        <label for="male">1 Red</label>
                        <input type="radio" name="class" value="1_red" />
                    </div>

                    <div class="class">
                        <label for="male">2 Blue</label>
                        <input type="radio" name="class" value="2_blue"/>
                    </div>

                    <div class="class">
                        <label for="male">2 Red</label>
                        <input type="radio" name="class" value="2_red"/>
                    </div>
                    
                    <div class="class">
                        <label for="male">3 Blue</label>
                        <input type="radio" name="class" value="3_blue"/>
                    </div>
                    
                    <div class="class">
                        <label for="male">3 Red</label>
                        <input type="radio" name="class" value="3_red"/>
                    </div> 
                </div>
            </div>
            
            <input type="submit" class="form-btn" value="Update Teacher">
            <input type="button" onclick="window.location.href='users.php'" class="form-btn" value="Cancel">

        </form>
    </section>
    
<script src="js/index.js"></script>
    
</script>

</body>
</html>
