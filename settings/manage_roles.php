<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();
if (!isAdmin()) {
    die('Access denied.');
}

$ownerId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_role') {
        $roleName = trim($_POST['role_name'] ?? '');
        if ($roleName !== '') {
            $stmt = $conn->prepare("INSERT INTO roles (owner_id, role_name) VALUES (?, ?)");
            $stmt->bind_param("is", $ownerId, $roleName);
            $stmt->execute();
        }
    }

    if ($action === 'delete_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ? AND owner_id = ? AND role_name <> 'Owner'");
        $stmt->bind_param("ii", $roleId, $ownerId);
        $stmt->execute();
    }

    if ($action === 'assign_permissions') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $permissionIds = $_POST['permission_ids'] ?? [];

        $check = $conn->prepare("SELECT id FROM roles WHERE id = ? AND owner_id = ? LIMIT 1");
        $check->bind_param("ii", $roleId, $ownerId);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            die('Invalid role for tenant.');
        }

        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();

        $insert = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permissionIds as $permissionId) {
            $pid = (int) $permissionId;
            $insert->bind_param("ii", $roleId, $pid);
            $insert->execute();
        }
    }

    header('Location: manage_roles.php');
    exit;
}

$rolesStmt = $conn->prepare("SELECT id, role_name FROM roles WHERE owner_id = ? ORDER BY role_name");
$rolesStmt->bind_param("i", $ownerId);
$rolesStmt->execute();
$roles = $rolesStmt->get_result();
$permissions = $conn->query("SELECT id, permission_name FROM permissions ORDER BY permission_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4 mb-4">
        <h4>Create Tenant Role</h4>
        <form method="POST" class="d-flex gap-2">
            <input type="hidden" name="action" value="create_role">
            <input type="text" name="role_name" class="form-control" placeholder="manager / accountant / staff" required>
            <button class="btn btn-success">Create Role</button>
        </form>
    </div>

    <div class="card shadow p-4">
        <h4>Existing Roles</h4>
        <table class="table table-bordered">
            <thead><tr><th>Role</th><th>Assign Permissions</th><th>Delete</th></tr></thead>
            <tbody>
            <?php while ($role = $roles->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($role['role_name']) ?></td>
                    <td>
                        <form method="POST" class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="hidden" name="action" value="assign_permissions">
                            <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                            <?php foreach ($permissions as $permission): ?>
                                <label class="form-check-label me-2">
                                    <input class="form-check-input" type="checkbox" name="permission_ids[]" value="<?= (int) $permission['id'] ?>">
                                    <?= htmlspecialchars($permission['permission_name']) ?>
                                </label>
                            <?php endforeach; ?>
                            <button class="btn btn-sm btn-primary">Save Permissions</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete_role">
                            <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                            <button class="btn btn-sm btn-danger">Delete Role</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../dashboard/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>
