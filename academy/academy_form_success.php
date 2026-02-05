<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['admission_success'])) {
    header("Location: academy_form.php");
    exit();
}

// one-time success flag
unset($_SESSION['admission_success']);
?>
<?php
require_once "../database/db.php";

$userId = $_SESSION['user_id'];

// latest admission record for this user
$query = "
SELECT *
FROM admissions
WHERE user_id = '$userId'
ORDER BY id DESC
LIMIT 1
";

$result = mysqli_query($conn, $query);
$admission = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admission Success</title>
</head>
<body>
    <?php if ($admission): ?>

<hr>

<h3>Admission Receipt</h3>

<p><strong>Name:</strong>
    <?php
        echo $admission['first_name'] . ' ' .
             $admission['middle_name'] . ' ' .
             $admission['last_name'];
    ?>
</p>

<p><strong>Address:</strong> <?php echo $admission['address']; ?></p>

<table width="100%" cellpadding="6" cellspacing="0" border="1">
    <tr>
        <th align="left">Description</th>
        <th align="right">Amount (Rs)</th>
    </tr>

    <tr>
        <td>Admission Fee</td>
        <td align="right"><?php echo $admission['admission_fee']; ?></td>
    </tr>

    <tr>
        <td>Coaching Fee</td>
        <td align="right"><?php echo $admission['coaching_fee']; ?></td>
    </tr>

    <tr>
        <td align="right"><strong>Total Fee</strong></td>
        <td align="right"><?php echo $admission['total_fee']; ?></td>
    </tr>

    <tr>
        <td align="right">SGST @9%</td>
        <td align="right"><?php echo $admission['sgst']; ?></td>
    </tr>

    <tr>
        <td align="right">CGST @9%</td>
        <td align="right"><?php echo $admission['cgst']; ?></td>
    </tr>

    <tr>
        <td align="right">IGST @18%</td>
        <td align="right"><?php echo $admission['igst']; ?></td>
    </tr>

    <tr>
        <td align="right"><strong>Grand Total</strong></td>
        <td align="right"><strong><?php echo $admission['grand_total']; ?></strong></td>
    </tr>
</table>

<?php else: ?>
    <p>No admission data found.</p>
<?php endif; ?>

    <h2>Admission Form Submitted Successfully</h2>

    <p>Your admission details have been saved.</p>
    <br><br>

<form action="academy_form_pdf.php" method="post">
    <button type="submit">Download PDF</button>
</form>
<br><br>

    <a href="academy_form.php">Add New Admission</a>
     <br><br>
  <a href="../dashboard/dashboard.php"> <button>GO BACK</button></a>
</body>
</html>
