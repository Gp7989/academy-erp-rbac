<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();

$userId = currentUserId();

/* ---------- PERMISSION CHECK ---------- */
if (!hasPermission('view')) {
    die("You are not allowed to view academy forms.");
}

/* ---------- FETCH USER FORMS ---------- */
$stmt = $conn->prepare("
    SELECT id, first_name, last_name, grand_total, created_at
    FROM admissions
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Academy Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>My Academy Forms</h3>

    <?php if (hasPermission('create')): ?>
        <a href="academy_form.php" class="btn btn-success">
            + Create New Form
        </a>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Student Name</th>
            <th>Grand Total</th>
            <th>Submitted On</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
                <td>₹<?= $row['grand_total'] ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>

                    <!-- VIEW (always if view permission exists) -->
                    <a href="../dashboard/view_admission.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                        View
                    </a>

                    <!-- EDIT -->
                    <?php if (hasPermission('edit')): ?>
                        <a href="../dashboard/edit_form.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                            Edit
                        </a>
                    <?php endif; ?>

                    <!-- DELETE -->
                    <?php if (hasPermission('delete')): ?>
                        <a href="../dashboard/delete_form.php?id=<?= $row['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure?')">
                            Delete
                        </a>
                    <?php endif; ?>

                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" class="text-center">No forms submitted yet.</td>
        </tr>
    <?php endif; ?>

    </tbody>
</table>

<a href="../dashboard/dashboard.php" class="btn btn-secondary">
    ← Back to Dashboard
</a>

</div>
</body>
</html>
