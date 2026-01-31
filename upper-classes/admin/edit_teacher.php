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
                $sql = "SELECT * FROM class_teachers WHERE id = ?";
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
                    $update_sql = "UPDATE class_teachers SET name = ?, password = ?, class_assigned = ? WHERE id = ?";
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
        ?>
            <div class="input">
                <label for="name">Name</label>
                <input type="text" name="name" id="name"  value="<?php echo htmlspecialchars($teacher['name']); ?>" class="form-control" required>

                <label for="password">New Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="leave blank to keep current password">
            </div>

            <div class="class-box">
                <div class="input">
                    <label for="class">Choose Class</label>
                    <select name="class" id="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php
                            // Fetch classes from the database
                            $classes_sql = "SELECT class_id, class_name FROM classes ORDER BY grade, class_name";
                            $classes_result = $conn->query($classes_sql);
                            
                            if ($classes_result && $classes_result->num_rows > 0) {
                                while ($class_row = $classes_result->fetch_assoc()) {
                                    $selected = ($teacher['class_assigned'] == $class_row['class_name']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($class_row['class_name']) . "' $selected>" . htmlspecialchars($class_row['class_name']) . "</option>";
                                }
                            } else {
                                echo "<option value=''>No classes available</option>";
                            }
                            
                            // Close database connection
                            $conn->close();
                        ?>
                    </select>
                </div>
            
                <input type="submit" class="form-btn" value="Update Teacher">
                <input type="button" onclick="window.location.href='users.php'" class="form-btn" value="Cancel">
            </div>
            
            <input type="submit" class="form-btn" value="Update Teacher">
            <input type="button" onclick="window.location.href='users.php'" class="form-btn" value="Cancel">

        </form>
    </section>
    
<script src="js/index.js"></script>
    
</script>

</body>
</html>
