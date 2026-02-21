<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();
if (!isAdmin()) {
    die("Access denied.");
}

$ownerId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = (int) ($_POST['user_id'] ?? 0);

    $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND owner_id = ? LIMIT 1");
    $check->bind_param("ii", $targetUserId, $ownerId);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        die('Invalid user for this tenant.');
    }

    if ($action === 'change_role') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $roleCheck = $conn->prepare("SELECT id FROM roles WHERE id = ? AND owner_id = ?");
        $roleCheck->bind_param("ii", $roleId, $ownerId);
        $roleCheck->execute();
        if ($roleCheck->get_result()->num_rows === 0) {
            die('Invalid tenant role.');
        }

        $conn->query("DELETE FROM user_roles WHERE user_id = " . $targetUserId);
        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $targetUserId, $roleId);
        $stmt->execute();
    }

    if ($action === 'remove_role') {
        $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->bind_param("i", $targetUserId);
        $stmt->execute();
    }

    if ($action === 'remove_permission') {
        $permissionId = (int) ($_POST['permission_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?");
        $stmt->bind_param("ii", $targetUserId, $permissionId);
        $stmt->execute();
    }

    header('Location: users_list.php');
    exit;
}

$query = "
    SELECT u.id, u.full_name, u.email, r.id AS role_id, r.role_name
    FROM users u
    LEFT JOIN user_roles ur ON ur.user_id = u.id
    LEFT JOIN roles r ON r.id = ur.role_id AND r.owner_id = ?
    WHERE u.owner_id = ?
    ORDER BY u.full_name ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $ownerId, $ownerId);
$stmt->execute();
$result = $stmt->get_result();

$rolesStmt = $conn->prepare("SELECT id, role_name FROM roles WHERE owner_id = ? ORDER BY role_name");
$rolesStmt->bind_param("i", $ownerId);
$rolesStmt->execute();
$roles = $rolesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$permissions = $conn->query("SELECT id, permission_name FROM permissions ORDER BY permission_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5"><div class="card shadow p-4">
<h3 class="mb-4">Users Management (Tenant Admin)</h3>
<table class="table table-bordered table-striped">
<thead class="table-dark"><tr><th>Name</th><th>Email</th><th>Role</th><th>Permissions</th><th>Actions</th></tr></thead>
<tbody>
<?php if ($result->num_rows > 0): while ($user = $result->fetch_assoc()): $perms = getUserPermissions((int) $user['id']); ?>
<tr>
<td><?= htmlspecialchars($user['full_name']) ?></td><td><?= htmlspecialchars($user['email']) ?></td>
<td><?= htmlspecialchars($user['role_name'] ?? '—') ?></td>
<td><?= !empty($perms) ? htmlspecialchars(implode(', ', $perms)) : 'No permissions' ?></td>
<td>
<form method="POST" class="mb-2 d-flex gap-2">
<input type="hidden" name="action" value="change_role"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
<select name="role_id" class="form-select form-select-sm"><?php foreach ($roles as $role): ?><option value="<?= $role['id'] ?>" <?= ((int) $user['role_id'] === (int) $role['id']) ? 'selected' : '' ?>><?= htmlspecialchars($role['role_name']) ?></option><?php endforeach; ?></select>
<button class="btn btn-sm btn-primary">Change Role</button>
</form>
<form method="POST" class="mb-2 d-flex gap-2"><input type="hidden" name="action" value="remove_role"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><button class="btn btn-sm btn-warning">Remove Role</button></form>
<form method="POST" class="d-flex gap-2"><input type="hidden" name="action" value="remove_permission"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="permission_id" class="form-select form-select-sm"><?php foreach ($permissions as $permission): ?><option value="<?= $permission['id'] ?>"><?= htmlspecialchars($permission['permission_name']) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-danger">Remove Direct Permission</button></form>
</td>
</tr>
<?php endwhile; else: ?><tr><td colspan="5" class="text-center">No users found.</td></tr><?php endif; ?>
</tbody></table>
<a href="../dashboard/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div></div>
</body></html>
