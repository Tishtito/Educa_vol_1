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
        if (!isset($_SESSION["exam_id"], $_SESSION["marks_out_of4"])) {
            echo "<script>
                swal({
                    title: 'Caution!',
                    text: 'Marks out of or exam not set.',
                    icon: 'warning'
                }).then(() => window.location.href = '../home.php');
            </script>";
            exit;
        }

        $exam_id      = $_SESSION["exam_id"];
        $marks_out_of = $_SESSION["marks_out_of4"];

        $student_class_id = "";
        $name  = "";
        $marks = "";
        $errorMessage = "";

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            if (!isset($_GET["student_class_id"])) {
                header("Location: ../subjects/math.php");
                exit;
            }

            $student_class_id = (int) $_GET["student_class_id"];

            $sql = "
                SELECT s.name, er.Math, sc.class
                FROM student_classes sc
                JOIN students s ON sc.student_id = s.student_id
                LEFT JOIN exam_results er
                    ON sc.student_class_id = er.student_class_id
                    AND er.exam_id = ?
                WHERE sc.student_class_id = ?
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $exam_id, $student_class_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $class_name = $row['class']; 

            if (!$row) {
                die("Student record not found.");
            }

            $name  = $row["name"];
            $marks = $row["Math"] ?? "";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $student_class_id = (int) $_POST["student_class_id"];
            $marks = $_POST["marks"];

            if (!is_numeric($marks) || $marks < 0 || $marks > $marks_out_of) {
                $errorMessage = "Marks must be between 0 and $marks_out_of.";
            } else {

                $percentage = ($marks / $marks_out_of) * 100;

                // Check if record exists
                $sql = "SELECT 1 FROM exam_results WHERE student_class_id = ? AND exam_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $student_class_id, $exam_id);
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;

                if ($exists) {
                    $sql = "UPDATE exam_results
                            SET Math = ?
                            WHERE student_class_id = ? AND exam_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("dii", $percentage, $student_class_id, $exam_id);
                } else {
                    $sql = "INSERT INTO exam_results (exam_id, student_class_id, Math)
                            VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iid", $exam_id, $student_class_id, $percentage);
                }

                if ($stmt->execute()) {
                    echo "<script>
                        swal({
                            title: 'Success!',
                            text: 'Marks updated successfully.',
                            icon: 'success'
                        }).then(() => window.location.href = '../subjects/math.php');
                    </script>";
                    exit;
                }

                $errorMessage = "Database error: " . $stmt->error;
            }
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
            <input type="hidden" name="student_class_id" value="<?= htmlspecialchars($student_class_id) ?>">
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
                    <a class="btn btn-outline-primary mb-2" href="../subjects/math.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
