<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die("Invalid request.");
}

$login = trim($_POST["login"] ?? "");
$password = $_POST["password"] ?? "";

if ($login == "" || $password == "") {
    die("Username/email and password are required.");
}

$sql = "SELECT * FROM users WHERE email = ? OR name = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $login, $login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Incorrect username/email or password.");
}

$user = mysqli_fetch_assoc($result);
$storedPassword = $user["password"];

$passwordIsCorrect = password_verify($password, $storedPassword);


if (!$passwordIsCorrect && hash_equals($storedPassword, $password)) {
    $passwordIsCorrect = true;

    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "si", $newHash, $user["id"]);
    mysqli_stmt_execute($update);
}

if (!$passwordIsCorrect) {
    die("Incorrect username/email or password.");
}

$role = strtolower(trim($user["Role"] ?? "Customer"));

$_SESSION["user_id"] = $user["id"];
$_SESSION["name"] = $user["name"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $role;

if ($role == "admin") {
    header("Location: admin-dashboard.php");
    exit();
}

header("Location: dashboard.php");
exit();
?>
