<?php
// graduate_all.php
include 'db/database.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// Turn on full error reporting for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $class_assigned = $_SESSION['class_assigned'] ?? null;
    $target_class_id = $_POST['target_class'] ?? null;
    $academic_year = date("Y");

    if (!$target_class_id || !$class_assigned) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid class selection.",
            "class_assigned" => $class_assigned,
            "target_class" => $target_class_id
        ]);
        exit;
    }

    $debug = [
        'session_class_assigned' => $class_assigned,
        'post_target_class' => $target_class_id,
    ];

    // current timestamp for updated_at
    $now = date("Y-m-d H:i:s");
    // finished_at set to start of current year (year-only representation)
    $finished_at = date("Y-01-01 00:00:00");

    // ✅ If FINISHED option is selected
    if ($target_class_id === "FINISHED") {
        $sql = "SELECT student_id FROM students WHERE class = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $class_assigned);
        $stmt->execute();
        $result = $stmt->get_result();

        $count = 0;
        while ($student = $result->fetch_assoc()) {
            $student_id = (int)$student['student_id'];

            // Update status, class, updated_at and finished_at (store year-only as start-of-year datetime)
            $update = $conn->prepare("UPDATE students SET status = 'Finished', class = 'Completed', updated_at = ?, finished_at = ? WHERE student_id = ?");
            $update->bind_param("ssi", $now, $finished_at, $student_id);
            $update->execute();

            $count++;
        }

        echo json_encode([
            "success" => true,
            "message" => "$count students marked as Finished and class updated to Completed.",
            "debug" => $debug
        ]);
        exit;
    }

    // ✅ Normal graduation flow
    if (!ctype_digit((string)$target_class_id)) {
        throw new Exception("Target class id is not numeric: " . var_export($target_class_id, true));
    }

    $id = (int)$target_class_id;
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE class_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($target_class_name);
    if (!$stmt->fetch()) {
        throw new Exception("Target class not found for id: $id");
    }
    $stmt->close();

    $sql = "SELECT student_id FROM students WHERE class = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $class_assigned);
    $stmt->execute();
    $result = $stmt->get_result();

    $count = 0;
    while ($student = $result->fetch_assoc()) {
        $student_id = (int)$student['student_id'];

        // Update class, status and updated_at
        $update = $conn->prepare("UPDATE students SET class = ?, status = 'Active', updated_at = ? WHERE student_id = ?");
        $update->bind_param("ssi", $target_class_name, $now, $student_id);
        $update->execute();

        $insert = $conn->prepare("INSERT INTO student_classes (student_id, class, academic_year) VALUES (?, ?, ?)");
        $insert->bind_param("isi", $student_id, $target_class_name, $academic_year);
        $insert->execute();

        $count++;
    }

    echo json_encode([
        "success" => true,
        "message" => "$count students graduated to $target_class_name",
        "debug" => $debug
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "DEV ERROR: " . $e->getMessage(),
        "trace" => $e->getTraceAsString()
    ]);
    exit;
}
?>
