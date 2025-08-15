<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class</title>
    <link rel="stylesheet" href="css/form.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
    
    <div class="form-container">
        <form method="post" action="" class="form">
        <h3>Create New Class</h3><hr>
        <?php
            // Include database connection file
            include 'db/database.php';
            
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $class_name = $_POST['name'];
                $grade = $_POST['grade'];
                
                if (empty($class_name) || empty($grade)) {
                    echo "<script>swal('Error', 'All fields are required!', 'error');</script>";
                } else {
                    // Prepare and bind with MySQLi
                    $query = "INSERT INTO classes (class_name, grade) VALUES (?,?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'si', $class_name, $grade);
                
                    // Execute the statement and provide feedback
                    if (mysqli_stmt_execute($stmt)) {
                        echo "<script>swal('Success', 'Class created successfully!', 'success').then(function() {
                            window.location.href = 'classes.php'; // Redirect after the alert
                            });</script>";
                    } else {
                        echo "<script>swal('Error', 'Failed to create class. Please try again.', 'error').then(function() {
                            window.location.href = 'classes.php'; // Redirect after the alert
                            });</script>";
                    }
                
                    // Close statement
                    mysqli_stmt_close($stmt);
                }
            }
        ?>
            <div class="input">
                <label for="name">Name of Class</label>
                <p><b>N/B</b>  include  '_'   e.g 7_east</p>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>

            <div class="input">
                <label for="grade">Grade</label>
                <select name="grade" id="grade" class="form-control" required>
                    <option value="">-- Select Grade --</option>
                    <option value="7">Grade 7</option>
                    <option value="8">Grade 8</option>
                    <option value="9">Grade 9</option>
                </select>
            </div>
            
            <input type="submit" class="form-btn" value="Create Class">
            <input type="button" onclick="window.location.href='classes.php'" class="form-btn" value="Cancel">

        </form>
    </section>
    
<script src="js/index.js"></script>
    
</script>

</body>
</html>
