<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Get counts for each status
$stats = [
    'pending' => 0,
    'confirmed' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

foreach ($statuses as $status) {
    $sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $doctor_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats[$status] = $row['count'];
    $stmt->close();
}

echo json_encode([
    "success" => true,
    "pending" => $stats['pending'],
    "confirmed" => $stats['confirmed'],
    "in_progress" => $stats['in_progress'],
    "completed" => $stats['completed'],
    "cancelled" => $stats['cancelled']
]);

$conn->close();
?>
