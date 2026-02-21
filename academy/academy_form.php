<?php
require_once '../helpers/auth_helper.php';
require_once '../helpers/permission_helper.php';

requireLogin();
if (!hasPermission('create_form')) {
    die('Access denied.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academy form</title>
</head>
<body>
    <h1>Academy form </h1>
    <hr>
   <div>
    <form action="academy_form_process.php" method = "POST">
      <div><label>First Name</label><br><input type="text" name="first_name" required></div><br>
      <div><label>Middle Name</label><br><input type="text" name="middle_name"></div><br>
      <div><label>Last Name</label><br><input type="text" name="last_name" required></div><br>
      <div><label>Address</label><br><textarea name="address" required></textarea></div><br><hr>
      <table width="100%" cellpadding="6" cellspacing="0">
        <tr><th align="left">Description</th><th align="right">Amount (Rs)</th></tr>
        <tr><td>Admission Fee :</td><td align="right"><input type="number" name="admission_fee" required></td></tr>
        <tr><td>Coaching Fee  :</td><td align="right"><input type="number" name="coaching_fee" required></td></tr>
        <tr><td align="right"><b>Total-Fee</b></td><td align="right"><input name="total_fee" type="number" readonly></td></tr>
        <tr><td align="right">SGST@9%</td><td align="right"><input type="number" name="sgst" readonly></td></tr>
        <tr><td align="right">CGST@9%</td><td align="right"><input type="number" name="cgst" readonly></td></tr>
        <tr><td align="right">IGST@18%</td><td align="right"><input type="number" name="igst" readonly></td></tr>
        <tr><td align="right"><strong>Grand Total</strong></td><td align="right"><input name="grand_total" type="number" readonly></td></tr>
      </table><br>
      <button type="Submit" style="float:right;">SUBMIT</button>
    </form>
   </div>
   <script src="../assests/js/academy_form.js"></script>
</body>
</html>
