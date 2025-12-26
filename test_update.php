<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Test</h2>";

// Check session
echo "<h3>Session Data:</h3>";
echo "Doctor ID: " . ($_SESSION['doctor_id'] ?? 'NOT SET') . "<br>";
echo "Logged In: " . ($_SESSION['doctor_logged_in'] ?? 'NOT SET') . "<br>";
echo "Username: " . ($_SESSION['doctor_username'] ?? 'NOT SET') . "<br>";

// Check database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<h3>Database Connection: OK</h3>";

// Check if doctor exists
if (isset($_SESSION['doctor_id'])) {
    $doctor_id = $_SESSION['doctor_id'];
    $sql = "SELECT * FROM doctor_profile WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<h3>Current Profile Data:</h3>";
        $doctor = $result->fetch_assoc();
        echo "<pre>";
        print_r($doctor);
        echo "</pre>";
    } else {
        echo "<h3>ERROR: No doctor found with ID: $doctor_id</h3>";
    }
}

// Test update
if (isset($_GET['test']) && $_GET['test'] == 'update') {
    $doctor_id = $_SESSION['doctor_id'];
    $test_name = "Test Name " . time();
    
    $sql = "UPDATE doctor_profile SET full_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $test_name, $doctor_id);
    
    if ($stmt->execute()) {
        echo "<h3 style='color:green'>✅ TEST UPDATE SUCCESSFUL! Name changed to: $test_name</h3>";
        echo "<a href='test_update.php'>Refresh to see changes</a>";
    } else {
        echo "<h3 style='color:red'>❌ UPDATE FAILED: " . $stmt->error . "</h3>";
    }
}

echo "<br><br><a href='test_update.php?test=update' style='background:#3d5a80;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Click to Test Update</a>";
echo "<br><br><a href='doctor_profile.html'>Back to Profile</a>";

$conn->close();
?>
