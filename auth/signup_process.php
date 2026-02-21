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
    $_SESSION['error'] = "Email already exists.";
    header("Location: signup.php");
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {

    // 1️⃣ Create user (temporarily no owner)
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, owner_id) VALUES (?, ?, ?, ?, NULL)");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);
    $stmt->execute();
    $userId = (int)$conn->insert_id;

    // 2️⃣ Make user self tenant owner
    $stmt = $conn->prepare("UPDATE users SET owner_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();

    // 3️⃣ Bootstrap permissions if table empty
    $permCheck = $conn->query("SELECT COUNT(*) as c FROM permissions")->fetch_assoc()['c'];
    if ($permCheck == 0) {

        $conn->query("
            INSERT INTO permissions (permission_name) VALUES
            ('create_form'),
            ('edit_form'),
            ('view_form'),
            ('delete_form')
        ");
    }

    // 4️⃣ Create Owner role for this tenant
    $stmt = $conn->prepare("INSERT INTO roles (owner_id, role_name) VALUES (?, 'Owner')");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $roleId = (int)$conn->insert_id;

    // 5️⃣ Assign all permissions to Owner role
    $permRes = $conn->query("SELECT id FROM permissions");
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    while ($perm = $permRes->fetch_assoc()) {
        $pid = (int)$perm['id'];
        $stmt->bind_param("ii", $roleId, $pid);
        $stmt->execute();
    }

    // 6️⃣ Assign role to user
    $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $roleId);
    $stmt->execute();

    $conn->commit();

    $_SESSION['signup_success'] = true;
    header("Location: login.php");
    exit();

} catch (Throwable $e) {
    $conn->rollback();
    die("Signup failed: " . $e->getMessage());
}