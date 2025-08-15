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
if($_SERVER["REQUEST_METHOD"] == "POST"){

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
        $sql = "SELECT id FROM class_teachers WHERE username = ?";

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

    // Validate class
    if(empty(trim($_POST["class"]))){
        $class_err = "Please select a class.";
    } else{
        $class = trim($_POST["class"]);
    }

    // Check input errors before inserting in database
    if(empty($name_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($class_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO class_teachers (name, username, password, class_assigned) VALUES (?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssss", $param_name, $param_username, $param_password, $param_class);

            // Set parameters
            $param_name = $name;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_class = $class; // Include the selected class

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: users.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($conn);
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
                <h4>Choose Grade</h4>
                <div class="class-options">
                    <div class="class">
                        <label for="male">4 Blue</label>
                        <input type="radio" name="class" value="4_blue" checked/>
                    </div>

                    <div class="class">
                        <label for="female">4 Red</label>
                        <input type="radio" name="class" value="4_red" checked/>
                    </div>
                    
                    <div class="class">
                        <label for="female">5 Blue</label>
                        <input type="radio" name="class" value="5_blue" checked/>
                    </div>
                    
                    <div class="class">
                        <label for="female">5 Red</label>
                        <input type="radio" name="class" value="5_red" checked/>
                    </div>

                    <div class="class">
                        <label for="female">6 Blue</label>
                        <input type="radio" name="class" value="6_blue" checked/>
                    </div>

                    <div class="class">
                        <label for="female">6 Red</label>
                        <input type="radio" name="class" value="6_red" checked/>
                    </div>
                </div>
            </div>
            
            <input type="submit" class="form-btn" value="Submit">
            <input type="button" class="form-btn" onclick="window.location.href='users.php'" value="Cancel">
        </form>
    </div>    
</body>
</html>     