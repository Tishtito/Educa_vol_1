<?php
// Start session
session_start();

// Include database connection
require_once "db/database.php";

// Check if 'examiner_id' is in the URL
if (!isset($_GET['examiner_id'])) {
    header("Location: users.php?message=No examiner selected for editing");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Examiner</title>
    <link rel="stylesheet" href="css/form.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>
<body>

<?php
$examiner_id = $_GET['examiner_id'];

// Fetch examiner details
$sql = "SELECT * FROM examiners WHERE examiner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $examiner_id);
$stmt->execute();
$result = $stmt->get_result();
$examiner = $result->fetch_assoc();

// Fetch examiner's assigned subjects
$assigned_subjects = []; // Initialize as empty array
$subject_sql = "SELECT subject_id FROM examiner_subjects WHERE examiner_id = ?";
$subject_stmt = $conn->prepare($subject_sql);
$subject_stmt->bind_param("i", $examiner_id);
$subject_stmt->execute();
$subject_result = $subject_stmt->get_result();
while ($row = $subject_result->fetch_assoc()) {
    $assigned_subjects[] = $row['subject_id'];
}

// Fetch examiner's assigned classes
$assigned_classes = []; // Initialize as empty array
$class_sql = "SELECT class_id FROM examiner_classes WHERE examiner_id = ?";
$class_stmt = $conn->prepare($class_sql);
$class_stmt->bind_param("i", $examiner_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
while ($row = $class_result->fetch_assoc()) {
    $assigned_classes[] = $row['class_id'];
}

// If form is submitted, update details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $password = $_POST['password'];
    $subjects = $_POST['subjects'] ?? []; // Handle subjects as an array
    $classes = $_POST['classes'] ?? []; // Handle classes as an array

    // Hash password if provided
    $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $examiner['password'];

    // Update examiner's details
    $update_sql = "UPDATE examiners SET name = ?, password = ? WHERE examiner_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $name, $hashed_password, $examiner_id);

    if ($update_stmt->execute()) {
        // Update subjects
        $conn->query("DELETE FROM examiner_subjects WHERE examiner_id = $examiner_id");
        foreach ($subjects as $subject_id) {
            $insert_subject_sql = "INSERT INTO examiner_subjects (examiner_id, subject_id) VALUES (?, ?)";
            $insert_subject_stmt = $conn->prepare($insert_subject_sql);
            $insert_subject_stmt->bind_param("ii", $examiner_id, $subject_id);
            $insert_subject_stmt->execute();
        }

        // Update classes
        $conn->query("DELETE FROM examiner_classes WHERE examiner_id = $examiner_id");
        foreach ($classes as $class_id) {
            $insert_class_sql = "INSERT INTO examiner_classes (examiner_id, class_id) VALUES (?, ?)";
            $insert_class_stmt = $conn->prepare($insert_class_sql);
            $insert_class_stmt->bind_param("ii", $examiner_id, $class_id);
            $insert_class_stmt->execute();
        }

        echo "<script>
                swal({
                    title: 'Good job!',
                    text: 'Examiner updated successfully!',
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
?>

<div class="form-container">
    <form method="post" action="" class="form">
        <h3>Edit Examiner Information</h3><hr>
        <div class="input">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($examiner['name']); ?>" required>

            <label for="password">New Password</label>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep the current password">
        </div>

        <div class="class-box">
            <h4>Assign Subjects</h4>
            <div class="class-subjects">
                <?php
                // Fetch all available subjects
                $subjects_query = "SELECT * FROM subjects";
                $subjects_result = $conn->query($subjects_query);

                while ($subject = $subjects_result->fetch_assoc()):
                    $isChecked = in_array($subject['subject_id'], $assigned_subjects) ? 'checked' : '';
                ?>
                    <label class="class" for="subject-<?php echo htmlspecialchars($subject['subject_id']); ?>">
                        <input type="checkbox" id="subject-<?php echo htmlspecialchars($subject['subject_id']); ?>" name="subjects[]" value="<?php echo htmlspecialchars($subject['subject_id']); ?>" <?php echo $isChecked; ?>>
                        <span class="checkmark"></span><?php echo htmlspecialchars($subject['name']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="class-box">
            <h4>Assign Classes</h4>
            <div class="class-subjects">
                <?php
                // Fetch all available classes
                $classes_query = "SELECT * FROM classes";
                $classes_result = $conn->query($classes_query);

                while ($class = $classes_result->fetch_assoc()):
                    $isChecked = in_array($class['class_id'], $assigned_classes) ? 'checked' : '';
                ?>
                    <label class="class" for="class-<?php echo htmlspecialchars($class['class_id']); ?>">
                        <input type="checkbox" id="class-<?php echo htmlspecialchars($class['class_id']); ?>" name="classes[]" value="<?php echo htmlspecialchars($class['class_id']); ?>" <?php echo $isChecked; ?>>
                        <span class="checkmark"></span><?php echo htmlspecialchars($class['class_name']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>

        <input type="submit" class="form-btn" value="Update Examiner">
        <input type="button" onclick="window.location.href='users.php'" class="form-btn" value="Cancel">
    </form>
</div>

    <script src="js/index.js"></script>
</body>
</html>
