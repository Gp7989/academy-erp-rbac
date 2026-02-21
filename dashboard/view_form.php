<?php
require_once '../helpers/auth_helper.php';
require_once '../helpers/permission_helper.php';
require_once '../database/db.php';

requireLogin();
if (!hasPermission('view_form')) {
    die("Unauthorized");
}

$formId = intval($_GET['id'] ?? 0);
$ownerId = tenantOwnerId() ?? resolveTenantOwnerId(currentUserId());

$stmt = $conn->prepare("SELECT * FROM admissions WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $formId, $ownerId);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    die("Form not found or access denied.");
}
