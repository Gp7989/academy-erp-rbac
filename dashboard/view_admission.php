<?php
// --------------------
// BASIC SETUP
// --------------------
require_once '../helpers/auth_helper.php';
require_once '../helpers/permission_helper.php';
require_once '../database/db.php';

requireLogin();

// Permission check
if (!hasPermission('view')) {
    die("You are not allowed to view this admission.");
}

// --------------------
// GET & VALIDATE ID
// --------------------
if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$admissionId = intval($_GET['id']);
$userId = currentUserId();

// --------------------
// FETCH ADMISSION (ownership check)
// --------------------
$stmt = $conn->prepare("
    SELECT *
    FROM admissions
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $admissionId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$admission = $result->fetch_assoc();

if (!$admission) {
    die("Admission not found or access denied.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Admission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">

        <h3 class="mb-4">Admission Details</h3>

        <table class="table table-bordered">
            <tr>
                <th>Student Name</th>
                <td><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']) ?></td>
            </tr>

            <tr>
                <th>Address</th>
                <td><?= nl2br(htmlspecialchars($admission['address'])) ?></td>
            </tr>

            <tr>
                <th>Admission Fee</th>
                <td>₹<?= $admission['admission_fee'] ?></td>
            </tr>

            <tr>
                <th>Coaching Fee</th>
                <td>₹<?= $admission['coaching_fee'] ?></td>
            </tr>

            <tr>
                <th>Total Fee</th>
                <td>₹<?= $admission['total_fee'] ?></td>
            </tr>

            <tr>
                <th>SGST</th>
                <td>₹<?= $admission['sgst'] ?></td>
            </tr>

            <tr>
                <th>CGST</th>
                <td>₹<?= $admission['cgst'] ?></td>
            </tr>

            <tr>
                <th>IGST</th>
                <td>₹<?= $admission['igst'] ?></td>
            </tr>

            <tr>
                <th>Grand Total</th>
                <td><strong>₹<?= $admission['grand_total'] ?></strong></td>
            </tr>

            <tr>
                <th>Submitted On</th>
                <td><?= $admission['created_at'] ?></td>
            </tr>
        </table>

        <div class="mt-4 d-flex gap-2">

            <?php if (hasPermission('edit')): ?>
                <a href="edit_form.php?id=<?= $admission['id'] ?>"
                   class="btn btn-warning">
                    Edit
                </a>
            <?php endif; ?>

            <?php if (hasPermission('delete')): ?>
                <a href="delete_form.php?id=<?= $admission['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this admission?');">
                    Delete
                </a>
            <?php endif; ?>

            <a href="dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>

        </div>

    </div>
</div>

</body>
</html>
