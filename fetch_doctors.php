<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

// Get all available doctors from doctor_profile table
$sql = "SELECT id, full_name, specialization 
        FROM doctor_profile 
        WHERE availability = 'Available' 
        ORDER BY full_name ASC";

$result = $conn->query($sql);

$doctors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = [
            'id' => $row['id'],
            'name' => $row['full_name'],
            'specialty' => $row['specialization'] ?? 'General Practice'
        ];
    }
}

echo json_encode($doctors);

$conn->close();
?>
