<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "../database/db.php";
require_once "../helpers/permission_helper.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid Request.");
}

$email = trim($_POST["email"] ?? '');
$password = $_POST["password"] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "
        <h2>Email does not exist</h2>
        <p>Please sign up first or go back to login.</p>
        <a href='login.php'><button>Back to Login</button></a>
    ";
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {
    die("Error: Incorrect password.");
}

$_SESSION['login_success'] = true;
$_SESSION["user_id"] = (int) $user["id"];
$_SESSION["user_name"] = $user["full_name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["tenant_owner_id"] = resolveTenantOwnerId((int) $user['id']);

header("Location: ../dashboard/dashboard.php");
exit();
