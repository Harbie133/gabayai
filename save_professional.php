<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect form data
$specialization_id = $_POST['specialization'];
$years_experience = $_POST['years_experience'];
$clinic = $_POST['clinic'];
$address = $_POST['address'];

// Insert into doctor_profession table
$sql = "INSERT INTO doctor_profession (specialization_id, years_experience, clinic, address) 
        VALUES ('$specialization_id', '$years_experience', '$clinic', '$address')";

if ($conn->query($sql) === TRUE) {
    echo "Doctor professional details saved successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
