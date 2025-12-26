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
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id == 0) {
    echo json_encode(["success" => false, "message" => "Invalid appointment ID"]);
    exit();
}

// Get appointment without patient join
$sql = "SELECT * FROM appointments WHERE appointment_id = ? AND doctor_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    
    // Extract patient name from notes
    $patient_name = 'Guest Patient';
    if (!empty($appointment['notes'])) {
        if (preg_match('/Patient:\s*([^|]+)/', $appointment['notes'], $matches)) {
            $patient_name = trim($matches[1]);
        }
    }
    
    $appointment['patient_name'] = $patient_name;
    
    echo json_encode(["success" => true, "appointment" => $appointment]);
} else {
    echo json_encode(["success" => false, "message" => "Appointment not found"]);
}

$stmt->close();
$conn->close();
?>
