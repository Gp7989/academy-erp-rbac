<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

/* ---------- SECURITY ---------- */
requireLogin();

if (!isAdmin()) {
    die("Access denied.");
}

/* ---------- FETCH USERS WITH ROLES ---------- */
$query = "
    SELECT 
        u.id,
        u.full_name,
        u.email,
        r.role_name
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.id
    LEFT JOIN roles r ON r.id = ur.role_id
    ORDER BY u.full_name ASC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="card shadow p-4">

<h3 class="mb-4">Users Management (Admin)</h3>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Permissions</th>
        </tr>
    </thead>
    <tbody>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($user = $result->fetch_assoc()): ?>

            <?php
                // Get permissions using existing helper
                $perms = getUserPermissions($user['id']);
            ?>

            <tr>
                <td><?= htmlspecialchars($user['full_name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['role_name'] ?? '—' ?></td>
                <td>
                    <?= !empty($perms) ? implode(', ', $perms) : 'No permissions' ?>
                </td>
            </tr>

        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" class="text-center">No users found.</td>
        </tr>
    <?php endif; ?>

    </tbody>
</table>

<a href="../dashboard/dashboard.php" class="btn btn-secondary">
    ← Back to Dashboard
</a>

</div>
</div>

</body>
</html>
