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
            require_once 'db/database.php';

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $exam_name = $_POST['name'];
                $exam_type = $_POST['exam_type'];
                $term = $_POST['term'];
            
                // Validate fields
                if (empty($exam_name) || empty($exam_type) || empty($term)) {
                    echo "<script>swal('Error', 'All fields are required!', 'error');</script>";
                } else {
                    // Insert into database
                    $query = "INSERT INTO exams (exam_name, exam_type, term) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'sss', $exam_name, $exam_type, $term);
            
                    if (mysqli_stmt_execute($stmt)) {
                        echo "<script>swal('Success', 'Exam created successfully!', 'success').then(function() {
                               window.location.href = 'settings.php'; 
                            });</script>";
                    } else {
                        echo "<script>swal('Error', 'Failed to create exam. Please try again.', 'error');</script>";
                    }
            
                    mysqli_stmt_close($stmt);
                }
            }
        ?>
        <div class="input">
            <label for="name">Name of Exam</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="input">
            <label for="exam_type">Exam Type</label>
            <select name="exam_type" id="exam_type" class="form-control" required>
                <option value="">-- Select Exam Type --</option>
                <option value="Opener">Opener</option>
                <option value="Mid-Term">Mid-Term</option>
                <option value="End-Term">End of Term</option>
            </select>
        </div>

        <div class="input">
            <label for="term">Select Term</label>
            <select name="term" id="term" class="form-control" required>
                <option value="">-- Select Term --</option>
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>
        </div>

        <input type="submit" class="form-btn" value="Create Exam">
        <input type="button" onclick="window.location.href='settings.php'" class="form-btn" value="Cancel">
    </form>
</div>
    
<script src="js/index.js"></script>
    
</script>

</body>
</html>
