<?php
// Database connection
$servername = "localhost"; 
$username = "root";        // XAMPP default user
$password = "";            // XAMPP default password is empty
$dbname = "gabayai";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Collect form data
$fullName = $_POST['fullName'];
$email    = $_POST['email'];
$phone    = $_POST['phone'];
$dob      = $_POST['dob'];
$gender   = $_POST['gender'];
$address  = $_POST['address'];

// Insert query
$sql = "INSERT INTO doctor_info (fullName, email, phone, dob, gender, address) 
        VALUES ('$fullName', '$email', '$phone', '$dob', '$gender', '$address')";

if ($conn->query($sql) === TRUE) {
  echo "<script>alert('Information saved successfully!'); window.location.href='DoctorDashboard.html';</script>";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
