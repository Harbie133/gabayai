<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

// Check if patient is logged in
$patient_id = null;
if (isset($_SESSION['user_id'])) {
    $patient_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
} elseif (isset($_SESSION['id'])) {
    $patient_id = $_SESSION['id'];
}

if (!$patient_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

// Verify appointment belongs to this patient
$check_sql = "SELECT appointment_id, status FROM appointments WHERE appointment_id = ? AND patient_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $appointment_id, $patient_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Appointment not found"]);
    exit();
}

// Update status to cancelled
$sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Appointment cancelled successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to cancel appointment"]);
}

$stmt->close();
$conn->close();
?>
