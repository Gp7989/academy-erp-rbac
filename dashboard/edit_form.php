<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();

if (!hasPermission('edit')) {
    die("You are not allowed to edit forms.");
}

$userId = currentUserId();
$formId = intval($_GET['id'] ?? 0);

/* ---------------- FETCH FORM ---------------- */
if (isAdmin()) {
    // Admin can edit any form
    $stmt = $conn->prepare("SELECT * FROM admissions WHERE id = ?");
    $stmt->bind_param("i", $formId);
} else {
    // User can edit ONLY own form
    $stmt = $conn->prepare(
        "SELECT * FROM admissions WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $formId, $userId);
}

$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();

if (!$form) {
    die("Form not found or access denied.");
}

/* ---------------- UPDATE FORM ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = $_POST['first_name'];
    $last  = $_POST['last_name'];
    $addr  = $_POST['address'];

    $stmt = $conn->prepare("
        UPDATE admissions
        SET first_name = ?, last_name = ?, address = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $first, $last, $addr, $formId);
    $stmt->execute();

    header("Location: ../academy/my_forms.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="card p-4 shadow">

<h3>Edit Academy Form</h3>

<form method="POST">

<div class="mb-3">
    <label>First Name</label>
    <input class="form-control" name="first_name"
           value="<?= htmlspecialchars($form['first_name']) ?>" required>
</div>

<div class="mb-3">
    <label>Last Name</label>
    <input class="form-control" name="last_name"
           value="<?= htmlspecialchars($form['last_name']) ?>" required>
</div>

<div class="mb-3">
    <label>Address</label>
    <textarea class="form-control" name="address" required><?= htmlspecialchars($form['address']) ?></textarea>
</div>

<button class="btn btn-primary">Update</button>
<a href="../academy/my_forms.php" class="btn btn-secondary">Cancel</a>

</form>
</div>
</div>

</body>
</html>
