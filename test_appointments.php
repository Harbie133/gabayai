<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['doctor_id'])) {
    die("Not logged in. Doctor ID not found in session.");
}

$conn = new mysqli("localhost", "root", "", "gabayai");
$doctor_id = $_SESSION['doctor_id'];

echo "<h2>Checking Appointments for Doctor ID: $doctor_id</h2>";

$sql = "SELECT * FROM appointments WHERE doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Total appointments found: " . $result->num_rows . "</p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Date</th><th>Time</th><th>Status</th><th>Type</th><th>Notes</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['patient_id'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['appointment_time'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['consultation_type'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['notes'], 0, 100)) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: red;'>No appointments found! Check if:</p>";
    echo "<ul>";
    echo "<li>Doctor ID in session matches doctor_id in appointments table</li>";
    echo "<li>There are actually appointments in the database</li>";
    echo "</ul>";
}

$stmt->close();
$conn->close();
?>
