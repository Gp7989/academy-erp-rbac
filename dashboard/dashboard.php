<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();

if (!hasPermission('view_form')) {
    die("You are not allowed to view forms.");
}

$userId = currentUserId();
$ownerId = tenantOwnerId() ?? resolveTenantOwnerId($userId);

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

$showLoginPopup = false;
if (isset($_SESSION['login_success'])) {
    $showLoginPopup = true;
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2>Welcome, <?= htmlspecialchars($_SESSION["user_name"]) ?> 👋</h2>
                <p class="mb-0">Your email: <?= htmlspecialchars($_SESSION["user_email"]) ?></p>
            </div>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">⚙️</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item" href="../settings/add_user.php">Add User</a></li>
                        <li><a class="dropdown-item" href="../settings/manage_roles.php">Manage Roles</a></li>
                        <li><a class="dropdown-item" href="../settings/users_list.php">Users List</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="../settings/change_password.php">Change Password</a></li>
                    <li>  <a href="../logout/logout.php" class="dropdown-item">Logout</a></li>
                </ul>
            </div>
        </div>

      
        <?php if (hasPermission('create_form')): ?>
            <div style="margin-top:20px;"><a href="../academy/academy_form.php" class="btn btn-primary">Academy form</a></div>
        <?php endif; ?>
    </div>

    <div class="mt-4 card shadow p-4">
        <h4>Workspace Admissions</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Submitted By</th>
                    <th>Grand Total</th>
                    <th>Submitted On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['submitted_by']) ?></td>
                        <td>₹<?= htmlspecialchars($row['grand_total']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <a href="view_admission.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-info">View</a>
                            <?php if (hasPermission('edit_form')): ?>
                                <a href="edit_form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php endif; ?>
                            <?php if (hasPermission('delete_form')): ?>
                                <a href="delete_form.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No forms submitted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
