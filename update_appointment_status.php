<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['appointment_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$appointment_id = $input['appointment_id'];
$status = $input['status'];
$doctor_id = $_SESSION['doctor_id'];

// Validate status
$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update appointment status (verify it belongs to this doctor)
$query = "UPDATE appointments SET status = ?, updated_at = NOW() 
          WHERE id = ? AND doctor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $status, $appointment_id, $doctor_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No appointment found or no changes made']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
