<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <link rel="stylesheet" href="css/form.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
    
    <div class="form-container">
        <form method="post" action="" class="form">
        <h3>Create New Exam</h3><hr>
        <?php
            // Include database connection file
            include 'db/database.php';
            
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $exam_name = $_POST['name'];
                
                // Prepare and bind with MySQLi
                $query = "INSERT INTO exams (exam_name) VALUES (?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 's', $exam_name);
            
                // Execute the statement and provide feedback
                if (mysqli_stmt_execute($stmt)) {
                    echo "<script>swal('Success', 'Exam created successfully!', 'success').then(function() {
                           window.location.href = 'settings.php'; // Redirect after the alert
                        });</script>";
                } else {
                    echo "<script>swal('Error', 'Failed to create exam. Please try again.', 'error').then(function() {
                           window.location.href = 'exams.php'; // Redirect after the alert
                        });</script>";
                }
            
                // Close statement
                mysqli_stmt_close($stmt);
            }
        ?>
            <div class="input">
                <label for="name">Name of Exam</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <input type="submit" class="form-btn" value="Create Exam">
            <input type="button" onclick="window.location.href='settings.php'" class="form-btn" value="Cancel">

        </form>
    </section>
    
<script src="js/index.js"></script>
    
</script>

</body>
</html>
