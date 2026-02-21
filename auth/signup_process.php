<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include "../database/db.php";
require_once '../helpers/password_helper.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid Request.");
}

$_SESSION['old'] = $_POST;

$full_name = trim($_POST["full_name"] ?? '');
$email = trim($_POST["email"] ?? '');
$phone = trim($_POST["phone"] ?? '');
$password = $_POST["password"] ?? '';
$confirm_password = $_POST["confirm_password"] ?? '';

if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: signup.php");
    exit;
}

$passwordErrors = validateStrongPassword($password);
if (!empty($passwordErrors)) {
    $_SESSION['error'] = implode("<br>", $passwordErrors);
    header("Location: signup.php");
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("Error: Email already exists. Please use a different email.");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, owner_id) VALUES (?, ?, ?, ?, NULL)");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);
    $stmt->execute();

    $ownerUserId = (int) $conn->insert_id;

    $roleName = 'Owner';
    $stmt = $conn->prepare("INSERT INTO roles (owner_id, role_name) VALUES (?, ?)");
    $stmt->bind_param("is", $ownerUserId, $roleName);
    $stmt->execute();
    $ownerRoleId = (int) $conn->insert_id;

    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $ownerUserId, $ownerRoleId);
    $stmt->execute();

    $permRes = $conn->query("SELECT id FROM permissions");
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    while ($perm = $permRes->fetch_assoc()) {
        $permissionId = (int) $perm['id'];
        $stmt->bind_param("ii", $ownerRoleId, $permissionId);
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['signup_success'] = true;
    header("Location: login.php");
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    die("Error inserting data: " . $e->getMessage());
}
