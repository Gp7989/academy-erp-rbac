<?php
require_once '../helpers/auth_helper.php';
require_once '../helpers/permission_helper.php';
require_once '../database/db.php';

requireLogin();

if (!hasPermission('view_form')) {
    die("You are not allowed to view forms.");
}

$ownerId = tenantOwnerId() ?? resolveTenantOwnerId(currentUserId());

$stmt = $conn->prepare(" 
    SELECT a.id, a.first_name, a.last_name, a.grand_total, a.created_at, u.full_name AS submitted_by
    FROM admissions a
    JOIN users u ON u.id = a.user_id
    WHERE a.owner_id = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
<div class="card shadow p-4">
<h3>Workspace Forms</h3>
<table class="table table-bordered table-striped">
<thead class="table-dark"><tr><th>ID</th><th>Student</th><th>Submitted By</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?= (int) $row['id'] ?></td>
<td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
<td><?= htmlspecialchars($row['submitted_by']) ?></td>
<td>₹<?= htmlspecialchars($row['grand_total']) ?></td>
<td><?= htmlspecialchars($row['created_at']) ?></td>
<td>
<a href="../dashboard/view_admission.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-info">View</a>
<?php if (hasPermission('edit_form')): ?><a href="../dashboard/edit_form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a><?php endif; ?>
<?php if (hasPermission('delete_form')): ?><a href="../dashboard/delete_form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-danger">Delete</a><?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody></table>
<a href="../dashboard/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div></div>
</body>
</html>
