<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once "../db/database.php";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Marks</title>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php
// Ensure the necessary session variables are set
if (!isset($_SESSION["exam_id"]) || !isset($_SESSION["marks_out_of1"])) {
    $_SESSION['marks_out_of1'] = null;

    echo "<script>
            setTimeout(function() {
                  swal({
                     title: 'Caution!',
                     text: 'You have not set Marks out of',
                     icon: 'warning',
                     button: 'OK'
                  }).then(function() {
                     window.location.href = '../home.php';
                  });
            }, 100);
         </script>";
}

$exam_id = $_SESSION["exam_id"];
$subject_id = $_SESSION['subject_id'];
$class_id = $_SESSION['class_id'];
$class_name = $_SESSION['class_name'] ?? '';
$marks_out_of = $_SESSION['marks_out_of1']; // Retrieve marks out of from session

$id = "";
$name = "";
$marks = "";

$errormessage = "";
$successMessage = "";

// Handle GET request (load form)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET["student_id"])) {
        header("Location: ../subjects/creative.php");
        exit;
    }

    $id = $_GET["student_id"];

    $sql = "SELECT s.name AS Name, er.Creative, sc.student_class_id
            FROM students s
            JOIN student_classes sc ON s.student_id = sc.student_id
            LEFT JOIN exam_results er 
                ON sc.student_class_id = er.student_class_id 
                AND er.exam_id = ?
            WHERE s.student_id = ? AND sc.class = ?";
        
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing query: " . $conn->error);
    }

    $stmt->bind_param("iis", $exam_id, $id, $class_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo "No marks found for this student in this class/exam.";
        exit;
    }

    $name = $row["Name"];
    $marks = $row["Creative"] ?? '';
    $student_class_id = $row["student_class_id"];

} else {
    // Handle POST request (save marks)
    $id = $_POST["id"];
    $name = $_POST["name"];
    $marks = $_POST["marks"];
    $student_class_id = $_POST["student_class_id"];

    do {
        if (empty($id) || empty($name) || $marks === "") {
            $errormessage = "All fields are required.";
            break;
        }

        if (!is_numeric($marks) || $marks < 0 || $marks > $marks_out_of) {
            $errormessage = "Marks must be a valid number between 0 and $marks_out_of.";
            break;
        }

        // Convert to percentage
        $percentage_marks = ($marks / $marks_out_of) * 100;

        // Check if record exists
        $sql = "SELECT * FROM exam_results WHERE student_class_id = ? AND exam_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errormessage = "Error preparing query: " . $conn->error;
            break;
        }
        $stmt->bind_param("ii", $student_class_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update
            $sql = "UPDATE exam_results SET Creative = ?, student_id = ? WHERE student_class_id = ? AND exam_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("diii", $percentage_marks, $id, $student_class_id, $exam_id);
        } else {
            // Insert
            $sql = "INSERT INTO exam_results (exam_id, student_id, student_class_id, Creative) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $exam_id, $id, $student_class_id, $percentage_marks);
        }

        if (!$stmt->execute()) {
            $errormessage = "Error updating marks: " . $stmt->error;
            break;
        }

        echo "<script>
            setTimeout(function() {
                swal({
                    title: 'Success!',
                    text: 'Marks updated successfully.',
                    icon: 'success',
                    button: 'OK'
                }).then(function() {
                    window.location.href = '../subjects/creative arts.php?subject_id=" . urlencode($_SESSION['subject_id']) . "&class_id=" . urlencode($_SESSION['class_id']) . "&class_name=" . urlencode($_SESSION['class_name']) . "';
                });
            }, 100);
        </script>";
        exit;

    } while (true);
}
?>
    <div class="container my-5">
        <h2>Enter Students Marks</h2>
        <form method="post">
        <?php if (!empty($errormessage)): ?>
            <div class='alert alert-warning alert-dismissible fade show' role='alert'>
                <strong><?php echo $errormessage; ?></strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        <?php endif; ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <input type="hidden" name="student_class_id" value="<?php echo htmlspecialchars($student_class_id); ?>">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Name</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($name); ?>" readonly>
                </div>
            </div>
            
            
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Marks (Out of <?php echo htmlspecialchars($marks_out_of); ?>)</label>
                <div class="col-sm-6">
                    <input type="number" class="form-control" name="marks" value="<?php echo htmlspecialchars($marks); ?>" min="0" required>
                </div>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class='row mb-3'>
                    <div class='offset-sm-3 col-sm-6'>
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <strong><?php echo $successMessage; ?></strong>
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-primary mb-2">Submit</button>
                </div>
                <div class="col-sm-3 d-grid">
                    <a class="btn btn-outline-primary mb-2" href="../home.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
