<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/permission_helper.php';
require_once __DIR__ . '/../database/db.php';

requireLogin();

if (!hasPermission('create_form')) {
    die('You are not allowed to create forms.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$address = $_POST['address'];
$admission_fee = $_POST['admission_fee'];
$coaching_fee = $_POST['coaching_fee'];
$total_fee = $_POST['total_fee'];
$sgst = $_POST['sgst'];
$cgst = $_POST['cgst'];
$igst = $_POST['igst'];
$grand_total = $_POST['grand_total'];

$userId = currentUserId();
$ownerId = tenantOwnerId() ?? resolveTenantOwnerId($userId);

$stmt = $conn->prepare("INSERT INTO admissions (
    user_id, owner_id, first_name, middle_name, last_name, address,
    admission_fee, coaching_fee, total_fee, sgst, cgst, igst, grand_total
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "iissssddddddd",
    $userId,
    $ownerId,
    $first_name,
    $middle_name,
    $last_name,
    $address,
    $admission_fee,
    $coaching_fee,
    $total_fee,
    $sgst,
    $cgst,
    $igst,
    $grand_total
);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['admission_success'] = true;
    header("Location: academy_form_success.php");
    exit();
}

die('Unable to save admission form.');
