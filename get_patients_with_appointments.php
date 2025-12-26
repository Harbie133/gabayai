<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Get ALL appointments for this doctor (removed filters)
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
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query error: " . $conn->error,
        "sql" => $sql
    ]);
    exit();
}

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
$seen_combinations = [];

while ($row = $result->fetch_assoc()) {
    // Extract patient name from notes
    $patient_name = 'Patient #' . $row['patient_id'];
    if (!empty($row['notes'])) {
        if (preg_match('/Patient:\s*([^|]+)/', $row['notes'], $matches)) {
            $patient_name = trim($matches[1]);
        }
    }
    
    // Create unique key to avoid duplicates
    $unique_key = $row['appointment_id'];
    
    if (!in_array($unique_key, $seen_combinations)) {
        $seen_combinations[] = $unique_key;
        
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
}

// Debug info
$debug_info = [
    "doctor_id" => $doctor_id,
    "total_found" => count($patients),
    "query_executed" => true
];

if (count($patients) === 0) {
    echo json_encode([
        "success" => false,
        "patients" => [],
        "count" => 0,
        "message" => "No appointments found for doctor ID: " . $doctor_id,
        "debug" => $debug_info
    ]);
} else {
    echo json_encode([
        "success" => true,
        "patients" => $patients,
        "count" => count($patients),
        "debug" => $debug_info
    ]);
}

$stmt->close();
$conn->close();
?>
