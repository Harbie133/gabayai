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

// Get filters
$date_filter = isset($_GET['date']) && $_GET['date'] != '' ? $_GET['date'] : null;
$status_filter = isset($_GET['status']) && $_GET['status'] != '' ? $_GET['status'] : null;
$type_filter = isset($_GET['type']) && $_GET['type'] != '' ? $_GET['type'] : null;

// Query WITHOUT patient join - we'll get patient name from notes
$sql = "SELECT * FROM appointments WHERE doctor_id = ?";

$params = [$doctor_id];
$types = "i";

if ($date_filter) {
    $sql .= " AND appointment_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter) {
    $sql .= " AND consultation_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

$sql .= " ORDER BY appointment_date DESC, appointment_time DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Query error: " . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    // Extract patient name from notes field
    $patient_name = 'Guest Patient';
    if (!empty($row['notes'])) {
        if (preg_match('/Patient:\s*([^|]+)/', $row['notes'], $matches)) {
            $patient_name = trim($matches[1]);
        }
    }
    
    $row['patient_name'] = $patient_name;
    $appointments[] = $row;
}

echo json_encode([
    "success" => true, 
    "appointments" => $appointments,
    "count" => count($appointments)
]);

$stmt->close();
$conn->close();
?>
