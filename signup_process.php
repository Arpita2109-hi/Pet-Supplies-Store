<?php

include("db.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Invalid request.");
}

$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$password = $_POST["password"];
$confirmPassword = $_POST["confirm_password"];

if ($name == "" || $email == "" || $password == "" || $confirmPassword == "") {
    die("All fields are required.");
}

if (strlen($name) < 4) {
    die("Username must be at least 4 characters long.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

if ($password != $confirmPassword) {
    die("Password and Confirm Password do not match.");
}

$name = mysqli_real_escape_string($conn, $name);
$email = mysqli_real_escape_string($conn, $email);

$checkSql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $checkSql);

if (!$result) {
    die("Error checking email: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
    die("Email already exists.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertSql = "INSERT INTO users (name, email, password)
              VALUES ('$name', '$email', '$hashedPassword')";

if (mysqli_query($conn, $insertSql)) {
    header("Location: signin.html?registered=1");
    exit();
} else {
    die("Account could not be created: " . mysqli_error($conn));
}

?>