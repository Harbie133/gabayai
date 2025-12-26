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
$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$prescription = isset($_POST['prescription']) ? trim($_POST['prescription']) : '';
$meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : '';

if ($appointment_id == 0) {
    echo json_encode(["success" => false, "message" => "Invalid appointment ID"]);
    exit();
}

// Verify appointment belongs to this doctor
$check_sql = "SELECT appointment_id FROM appointments WHERE appointment_id = ? AND doctor_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $appointment_id, $doctor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Appointment not found"]);
    exit();
}
$check_stmt->close();

// Update appointment
$sql = "UPDATE appointments SET status = ?, notes = ?, prescription = ?, meeting_link = ? WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $status, $notes, $prescription, $meeting_link, $appointment_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Appointment updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update appointment"]);
}

$stmt->close();
$conn->close();
?>
