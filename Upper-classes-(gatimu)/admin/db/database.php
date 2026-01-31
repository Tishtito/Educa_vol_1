<?php

$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "upper_classes1";
$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);
if (!$conn) {
    die("Something went wrong;");
}

?>