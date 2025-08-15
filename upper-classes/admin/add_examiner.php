<?php
session_start();
    
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
// Include config file
require_once "db/database.php";

// Define variables and initialize with empty values
$name = $username = $password = $confirm_password = $class = "";
$name_err = $username_err = $password_err = $confirm_password_err = $class_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate full name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter your full name.";
    } else{
        $name = trim($_POST["name"]);
    }

    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT examiner_id FROM examiners WHERE username = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = trim($_POST["username"]);

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate selected subjects
    if (empty($_POST["subjects"])) {
        $class_err = "Please select at least one subject.";
    } else {
        $subjects = $_POST["subjects"];
    }

    // Check input errors before inserting into the database
    if (empty($name_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($class_err)) {
        // Prepare an insert statement for the examiners table
        $sql = "INSERT INTO examiners (name, username, password) VALUES (?, ?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables
            mysqli_stmt_bind_param($stmt, "sss", $param_name, $param_username, $param_password);

            // Set parameters
            $param_name = $name;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Get the last inserted examiner ID
                $examiner_id = mysqli_insert_id($conn);

                // Insert assigned subjects into examiner_subjects table
                $sql_subjects = "INSERT INTO examiner_subjects (examiner_id, subject_id) VALUES (?, ?)";
                if ($stmt_subjects = mysqli_prepare($conn, $sql_subjects)) {
                    foreach ($subjects as $subject) {
                        // Assume subject_id is fetched based on the subject name
                        $subject_id = fetchSubjectIdByName($conn, $subject);

                        // Bind variables and execute
                        mysqli_stmt_bind_param($stmt_subjects, "ii", $examiner_id, $subject_id);
                        mysqli_stmt_execute($stmt_subjects);
                    }
                    mysqli_stmt_close($stmt_subjects);
                }

                // Redirect to a success page
                header("location: users.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($conn);
}

// Function to fetch subject_id by subject name
function fetchSubjectIdByName($conn, $subject_name) {
    $sql = "SELECT subject_id FROM subjects WHERE name = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $subject_name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $subject_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $subject_id;
    }
    return null; // Return null if subject is not found
}

?>

 
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>register form</title>
   <link rel="stylesheet" href="css/form.css">
</head>
<body>
    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <h3>Sign Up</h3>
            <p>Please fill this form to create an account.</p>
                
            <input type="text" name="name"  required placeholder="enter your name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
            <span class="invalid-feedback"><?php echo $name_err; ?></span>
            
            <input type="text" name="username" required placeholder="enter your username" class="form-control  <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
            <span class="invalid-feedback"><?php echo $username_err; ?></span>

            <input type="password" name="password" required placeholder="enter your password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
            <span class="invalid-feedback"><?php echo $password_err; ?></span>
            
            <input type="password" name="confirm_password" required placeholder="confirm your password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>

            <div class="class-box">
                <h4>Choose Subjects</h4>
                <div class="class-subjects">
                    <label class="class" for="math">
                        <input type="checkbox" id="math" name="subjects[]" value="Math">
                        <span class="checkmark"></span>Math
                    </label>
                    <label class="class" for="english">
                        <input type="checkbox" id="english" name="subjects[]" value="English">
                        <span class="checkmark"></span>English
                    </label>
                    <label class="class" for="kiswahili">
                        <input type="checkbox" id="kiswahili" name="subjects[]" value="Kiswahili">
                        <span class="checkmark"></span>Kiswahili
                    </label>
                    <!-- Add other subjects similarly -->
                </div>

                  
                <div class="class-subjects">
                    <label class="class" for="creative arts">Creative Arts
                        <input type="checkbox" id="creative arts" name="subjects[]" value="creative arts"/>
                        <span class="checkmark"></span>
                    </label>
                        
                    <label class="class" for="SST">Social Studies
                        <input type="checkbox" id="SST" name="subjects[]" value="SST"/>
                        <span class="checkmark"></span>
                    </label>
                    
                    <label class="class" for="CRE">CRE
                        <input type="checkbox" id="CRE" name="subjects[]" value="CRE"/>
                        <span class="checkmark"></span>
                    </label>                    
                </div>

                <div class="class-subjects">
                    <label class="class" for="agricnutri">Agriculture & Nutrition
                        <input type="checkbox" id="agricnutri" name="subjects[]" value="agricnutri"/>
                        <span class="checkmark"></span>
                    </label>

                    <label class="class" for="scitech">Science & Technology
                        <input type="checkbox" id="scitech" name="subjects[]" value="scitech"/>
                        <span class="checkmark"></span>
                    </label>
                </div>
            </div>
            
            <input type="submit" class="form-btn" value="Submit">
            <input type="button" class="form-btn" onclick="window.location.href='users.php'" value="Cancel">
        </form>
    </div>    
</body>
</html>     