<?php
require_once '../helpers/auth_helper.php';
require_once '../helpers/permission_helper.php';
require_once '../database/db.php';

requireLogin();
if (!hasPermission('view_form')) {
    die("You are not allowed to view this admission.");
}

$admissionId = intval($_GET['id'] ?? 0);
$ownerId = tenantOwnerId() ?? resolveTenantOwnerId(currentUserId());

$stmt = $conn->prepare("SELECT a.*, u.full_name AS submitted_by FROM admissions a JOIN users u ON u.id = a.user_id WHERE a.id = ? AND a.owner_id = ?");
$stmt->bind_param("ii", $admissionId, $ownerId);
$stmt->execute();
$admission = $stmt->get_result()->fetch_assoc();

if (!$admission) {
    die("Admission not found or access denied.");
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>View Admission</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container mt-5"><div class="card shadow p-4"><h3 class="mb-4">Admission Details</h3>
<p><strong>Submitted By:</strong> <?= htmlspecialchars($admission['submitted_by']) ?></p>
<table class="table table-bordered">
<tr><th>Student Name</th><td><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']) ?></td></tr>
<tr><th>Address</th><td><?= nl2br(htmlspecialchars($admission['address'])) ?></td></tr>
<tr><th>Grand Total</th><td><strong>₹<?= htmlspecialchars($admission['grand_total']) ?></strong></td></tr>
<tr><th>Submitted On</th><td><?= htmlspecialchars($admission['created_at']) ?></td></tr>
</table>
<div class="mt-4 d-flex gap-2">
<?php if (hasPermission('edit_form')): ?><a href="edit_form.php?id=<?= (int) $admission['id'] ?>" class="btn btn-warning">Edit</a><?php endif; ?>
<?php if (hasPermission('delete_form')): ?><a href="delete_form.php?id=<?= (int) $admission['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this admission?');">Delete</a><?php endif; ?>
<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a></div></div></div></body></html>
