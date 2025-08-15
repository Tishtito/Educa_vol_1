<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator - Adjust Grades</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>
<div class="container">
    <h1 class="my-4">Adjust Grade Boundaries</h1>
    <form method="post">
    <?php
        require_once "db/database.php";

        // Fetch existing grade boundaries
        $sql = "SELECT * FROM totalmarks_boundaries";
        $result = $conn->query($sql);

        if (!$result) {
            die("Query failed: " . $conn->error);
        }

        // Process form submission to update grade boundaries
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_POST['grades'] as $id => $gradeData) {
                $grade = $gradeData['grade'];
                $min_marks = (int)$gradeData['min_marks'];
                $max_marks = (int)$gradeData['max_marks'];

                $update_sql = "UPDATE totalmarks_boundaries 
                            SET grade = '$grade', min_marks = $min_marks, max_marks = $max_marks
                            WHERE id = $id";

                if (!$conn->query($update_sql)) {
                    echo "Error updating grade: " . $connection->error;
                }
            }

            echo "<script>
                    swal({
                        title: 'Good job!',
                        text: 'Teacher successfully Registered!',
                        icon: 'success',
                        button: 'OK',
                    }).then(function() {
                        window.location.href = 'settings.php'; // Redirect after the alert
                    });
                </script>";
            exit;
        }

        $conn->close();
    ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Min Marks</th>
                    <th>Max Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <input type="text" name="grades[<?php echo $row['id']; ?>][grade]" 
                                   value="<?php echo $row['grade']; ?>" class="form-control" required>
                        </td>
                        <td>
                            <input type="number" name="grades[<?php echo $row['id']; ?>][min_marks]" 
                                   value="<?php echo $row['min_marks']; ?>" class="form-control" required>
                        </td>
                        <td>
                            <input type="number" name="grades[<?php echo $row['id']; ?>][max_marks]" 
                                   value="<?php echo $row['max_marks']; ?>" class="form-control" required>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">Update Grades</button>
        <button type="button" onclick="window.location.href='users.php'" class="btn btn-primary">Cancel</button>
    </form>
</div>
</body>
</html>
