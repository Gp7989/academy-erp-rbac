<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();
if (!hasPermission('delete_form')) {
    die("You are not allowed to delete forms.");
}

$formId = intval($_GET['id'] ?? 0);
$ownerId = tenantOwnerId() ?? resolveTenantOwnerId(currentUserId());

$stmt = $conn->prepare("DELETE FROM admissions WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $formId, $ownerId);
$stmt->execute();

header("Location: ../dashboard/dashboard.php");
exit();
