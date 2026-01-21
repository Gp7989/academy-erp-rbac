<?php
session_start();
include "../database/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $first_name = $_POST['first_name'];
  $middle_name = $_POST['middle_name'];
  $last_name = $_POST['last_name'];
  $address     = $_POST['address'];

  $admission_fee = $_POST['admission_fee'];
  $coaching_fee  = $_POST['coaching_fee'];
  $total_fee     = $_POST['total_fee'];
  $sgst         = $_POST['sgst'];
  $cgst         = $_POST['cgst'];
  $igst         = $_POST['igst'];
  $grand_total   = $_POST['grand_total'];

  $userId = $_SESSION['user_id'];
  $insert_query =" INSERT INTO admissions (user_id,first_name, middle_name,last_name,address,admission_fee,coaching_fee,total_fee,sgst,cgst,igst,grand_total) VALUES ('$userId', '$first_name', '$middle_name', '$last_name', '$address','$admission_fee', '$coaching_fee', '$total_fee', '$sgst', '$cgst', '$igst', '$grand_total')";

  mysqli_query($conn , $insert_query);
}

// yahan next step me data process hoga
echo "Form received successfully.";
