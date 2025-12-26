<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in", "session" => $_SESSION]);
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$today = date('Y-m-d');

// Get ONLY TODAY's appointments
$sql = "SELECT 
            a.patient_id,
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.notes,
            a.symptoms,
            a.consultation_type,
            a.status
        FROM appointments a
        WHERE a.doctor_id = ?
        AND a.appointment_date = CURDATE()
        ORDER BY a.appointment_time ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query error: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];

while ($row = $result->fetch_assoc()) {
    // Extract patient name from notes
    $patient_name = 'Patient #' . $row['patient_id'];
    if (!empty($row['notes'])) {
        // Pattern: "Patient: harbei | Contact: ..."
        if (preg_match('/Patient:\s*([^|]+)/', $row['notes'], $matches)) {
            $patient_name = trim($matches[1]);
        }
    }
    
    $patients[] = [
        'id' => $row['patient_id'],
        'name' => $patient_name,
        'appointment_id' => $row['appointment_id'],
        'appointment_date' => $row['appointment_date'],
        'appointment_time' => $row['appointment_time'],
        'symptoms' => $row['symptoms'],
        'status' => $row['status'],
        'consultation_type' => $row['consultation_type']
    ];
}

if (count($patients) === 0) {
    echo json_encode([
        "success" => false,
        "patients" => [],
        "count" => 0,
        "message" => "No appointments scheduled for today (" . $today . ")",
        "doctor_id" => $doctor_id,
        "today" => $today
    ]);
} else {
    echo json_encode([
        "success" => true,
        "patients" => $patients,
        "count" => count($patients),
        "doctor_id" => $doctor_id,
        "today" => $today
    ]);
}

$stmt->close();
$conn->close();
?>
