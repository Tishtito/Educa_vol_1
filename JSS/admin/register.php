<?php
// Include config file
require_once "db/database.php";

// Define variables and initialize with empty values
$name = $username = $password = $confirm_password = "";
$name_err = $username_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate full name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your full name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        $sql = "SELECT examiner_id FROM examiners WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check input errors before inserting into database
    if (empty($name_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO examiners (name, username, password) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_name, $param_username, $param_password);
            $param_name = $name;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);

            if (mysqli_stmt_execute($stmt)) {
                // Get the last inserted examiner ID
                $examiner_id = mysqli_insert_id($conn);

                // Handle assigned subjects
                if (!empty($_POST['subjects'])) {
                    $subject_sql = "INSERT INTO examiner_subjects (examiner_id, subject_id) VALUES (?, ?)";
                    if ($subject_stmt = mysqli_prepare($conn, $subject_sql)) {
                        foreach ($_POST['subjects'] as $subject_id) {
                            mysqli_stmt_bind_param($subject_stmt, "ii", $examiner_id, $subject_id);
                            mysqli_stmt_execute($subject_stmt);
                        }
                        mysqli_stmt_close($subject_stmt);
                    }
                }

                // Handle assigned classes
                if (!empty($_POST['classes'])) {
                    $class_sql = "INSERT INTO examiner_classes (examiner_id, class_id) VALUES (?, ?)";
                    if ($class_stmt = mysqli_prepare($conn, $class_sql)) {
                        foreach ($_POST['classes'] as $class_id) {
                            mysqli_stmt_bind_param($class_stmt, "ii", $examiner_id, $class_id);
                            mysqli_stmt_execute($class_stmt);
                        }
                        mysqli_stmt_close($class_stmt);
                    }
                }

                // Redirect to login page
                header("location: login.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register Examiner</title>
   <link rel="stylesheet" href="css/form.css">
</head>
<body>
<div class="form-container">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <h3>REGISTER EXAMINER</h3>
        <p>Please fill this form to create an account.</p>
        
        <input type="text" name="name" required placeholder="Enter your name" value="<?php echo $name; ?>">
        <span><?php echo $name_err; ?></span>
        
        <input type="text" name="username" required placeholder="Enter your username" value="<?php echo $username; ?>">
        <span><?php echo $username_err; ?></span>
        
        <input type="password" name="password" required placeholder="Enter your password">
        <span><?php echo $password_err; ?></span>
        
        <input type="password" name="confirm_password" required placeholder="Confirm your password">
        <span><?php echo $confirm_password_err; ?></span>
        
        <div class="class-box">
            <h4>Select Subjects</h4>
            <div class="class-subjects">
                <?php
                $subjects_query = "SELECT * FROM subjects";
                $subjects_result = $conn->query($subjects_query);
                while ($subject = $subjects_result->fetch_assoc()) {
                    echo "<label class='class'><input type='checkbox' name='subjects[]' value='{$subject['subject_id']}'> 
                    <span class='checkmark'></span>{$subject['name']}</label><br>";
                }
                ?>
            </div>
        </div>

        <div class="class-box">
            <h4>Assign Classes</h4>
            <div class="class-subjects">
                <?php
                $classes_query = "SELECT * FROM classes";
                $classes_result = $conn->query($classes_query);
                while ($class = $classes_result->fetch_assoc()) {
                    echo "<label  class='class'><input type='checkbox' name='classes[]' value='{$class['class_id']}'> 
                    <span class='checkmark'></span>{$class['class_name']}</label><br>";
                }
                ?>
            </div>
        </div>

        <input type="submit" class="form-btn" value="Submit">
        <input type="button" class="form-btn" onclick="window.location.href='users.php'" value="Cancel">
    </form>
</div>
</body>
</html>
