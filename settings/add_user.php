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
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = (int) ($_POST['role'] ?? 0);
    $perms = $_POST['permissions'] ?? [];

    $stmt = $conn->prepare("SELECT id FROM roles WHERE id = ? AND owner_id = ? LIMIT 1");
    $stmt->bind_param("ii", $role, $ownerId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        die('Invalid role selected for this workspace.');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, owner_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $phone, $pass, $ownerId);
        $stmt->execute();
        $userId = (int) $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $role);
        $stmt->execute();

        $permStmt = $conn->prepare("INSERT IGNORE INTO user_permissions (user_id, permission_id) VALUES (?, ?)");
        foreach ($perms as $pid) {
            $permissionId = (int) $pid;
            $permStmt->bind_param("ii", $userId, $permissionId);
            $permStmt->execute();
        }

        $conn->commit();
        header("Location: ../dashboard/dashboard.php");
        exit();
    } catch (Throwable $e) {
        $conn->rollback();
        die('Failed to create user: ' . $e->getMessage());
    }
}

$rolesStmt = $conn->prepare("SELECT * FROM roles WHERE owner_id = ? ORDER BY role_name ASC");
$rolesStmt->bind_param("i", $ownerId);
$rolesStmt->execute();
$roles = $rolesStmt->get_result();
$permissions = $conn->query("SELECT * FROM permissions ORDER BY permission_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
<div class="card p-4 shadow">
<h3>Add User (Admin)</h3>
<form method="POST">
<input class="form-control mb-2" name="name" placeholder="Full Name" required>
<input class="form-control mb-2" name="email" placeholder="Email" required>
<input class="form-control mb-2" name="phone" placeholder="Phone Number" required>
<input class="form-control mb-2" name="password" type="password" placeholder="Password" required>
<select class="form-control mb-3" name="role" required>
    <?php while ($r = $roles->fetch_assoc()): ?>
        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
    <?php endwhile; ?>
</select>
<label>Extra Permissions</label><br>
<?php while ($p = $permissions->fetch_assoc()): ?>
    <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>">
    <?= htmlspecialchars($p['permission_name']) ?><br>
<?php endwhile; ?>
<br>
<button class="btn btn-success">Create User</button>
</form>
</div>
</div>
</body>
</html>
