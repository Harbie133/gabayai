<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "gabayai");
if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$sql = "SELECT 
            p.prescription_id,
            p.appointment_id,
            p.user_id,
            p.doctor_id,
            p.diagnosis,
            p.additional_notes,
            p.prescription_date,
            p.status,
            d.full_name as doctor_name
        FROM prescriptions p
        LEFT JOIN doctor_profile d ON p.doctor_id = d.id
        WHERE p.user_id = ?
        ORDER BY p.prescription_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$prescriptions = [];
while ($row = $result->fetch_assoc()) {
    $meds = [];
    $med_stmt = $conn->prepare("SELECT medication_id, medication_name, dosage, frequency, duration, instructions FROM medications WHERE prescription_id=?");
    $med_stmt->bind_param("i", $row['prescription_id']);
    $med_stmt->execute();
    $med_result = $med_stmt->get_result();
    while ($med_row = $med_result->fetch_assoc()) $meds[] = $med_row;
    $med_stmt->close();
    $row['medications'] = $meds;
    $prescriptions[] = $row;
}
$stmt->close();
$conn->close();

ob_end_clean();
echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
?>
