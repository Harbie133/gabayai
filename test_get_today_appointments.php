<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Debugging Today's Appointments</h2>";

// Check session
if (!isset($_SESSION['doctor_id'])) {
    echo "<p style='color:red;'>❌ NOT LOGGED IN - Doctor ID not in session</p>";
    echo "<p>Session contents: <pre>" . print_r($_SESSION, true) . "</pre></p>";
    exit();
}

echo "<p style='color:green;'>✅ Logged in as Doctor ID: " . $_SESSION['doctor_id'] . "</p>";

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$doctor_id = $_SESSION['doctor_id'];
$today = date('Y-m-d');

echo "<p><strong>Today's Date:</strong> $today</p>";

// Test 1: Get ALL appointments for this doctor
echo "<h3>Test 1: ALL appointments for doctor</h3>";
$sql1 = "SELECT * FROM appointments WHERE doctor_id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $doctor_id);
$stmt1->execute();
$result1 = $stmt1->get_result();

echo "<p>Total appointments found: <strong>" . $result1->num_rows . "</strong></p>";

if ($result1->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Date</th><th>Time</th><th>Status</th><th>Type</th><th>Notes</th></tr>";
    while ($row = $result1->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_id'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['consultation_type'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['notes'], 0, 50)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Get today's appointments only
echo "<h3>Test 2: TODAY'S appointments only</h3>";
$sql2 = "SELECT * FROM appointments WHERE doctor_id = ? AND appointment_date = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("is", $doctor_id, $today);
$stmt2->execute();
$result2 = $stmt2->get_result();

echo "<p>Today's appointments found: <strong>" . $result2->num_rows . "</strong></p>";

if ($result2->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Date</th><th>Time</th><th>Status</th><th>Type</th><th>Notes</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_id'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['consultation_type'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['notes'], 0, 50)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>No appointments found for today. Try changing appointment dates in database to today's date.</p>";
}

$stmt1->close();
$stmt2->close();
$conn->close();
?>
