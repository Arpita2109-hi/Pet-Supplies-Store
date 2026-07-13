<?php

session_start();

include("db.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Invalid request.");
}

$email = trim($_POST["email"]);
$password = $_POST["password"];

if ($email == "" || $password == "") {
    die("Email and password are required.");
}

$email = mysqli_real_escape_string($conn, $email);

$checkSql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $checkSql);

if (!$result) {
    die("Login error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    die("Incorrect email or password.");
}

$user = mysqli_fetch_assoc($result);

if (!password_verify($password, $user["password"])) {
    die("Incorrect email or password.");
}

$_SESSION["user_id"] = $user["id"];
$_SESSION["name"] = $user["name"];
$_SESSION["email"] = $user["email"];

header("Location: dashboard.php");
exit();

?>