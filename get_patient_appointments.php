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

$sql = "SELECT a.*, d.full_name as doctor_name, d.specialization
        FROM appointments a
        LEFT JOIN doctor_profile d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode(["success" => true, "appointments" => $appointments]);

$stmt->close();
$conn->close();
?>
