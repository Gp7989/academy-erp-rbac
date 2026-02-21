<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../helpers/auth_helper.php';
require_once '../database/db.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
requireLogin();

if (!isAdmin()) {
    die("Unauthorized");
}

$userId = (int)$_POST['user_id'];
$permissionId = (int)$_POST['permission_id'];

$stmt = $conn->prepare("
    INSERT IGNORE INTO user_permissions (user_id, permission_id)
    VALUES (?, ?)
");
$stmt->bind_param("ii", $userId, $permissionId);
$stmt->execute();

header("Location: users_list.php");
exit;