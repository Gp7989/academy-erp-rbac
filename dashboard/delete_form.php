<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();

if (!hasPermission('delete')) {
    die("You are not allowed to delete forms.");
}

$userId = currentUserId();
$formId = intval($_GET['id'] ?? 0);

/* ---------- DELETE LOGIC ---------- */
if (isAdmin()) {
    // Admin can delete any form
    $stmt = $conn->prepare("DELETE FROM admissions WHERE id = ?");
    $stmt->bind_param("i", $formId);
} else {
    // User can delete ONLY own form
    $stmt = $conn->prepare(
        "DELETE FROM admissions WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $formId, $userId);
}

$stmt->execute();

header("Location: ../academy/my_forms.php");
exit();
