<?php
// update_patient.php
session_start();
header('Content-Type: application/json');

// --- 1. CONFIGURATION ---
$host = 'localhost';
$db   = 'gabayai';
$user = 'root';
$pass = ''; // Leave blank kung XAMPP default

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// --- 2. GET INPUT DATA ---
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

$id = $input['id'];
$fname = $input['first_name'] ?? '';
$lname = $input['last_name'] ?? '';
$phone = $input['phone'] ?? '';
// Kung walang laman, gawing NULL para hindi "0000-00-00"
$dob = !empty($input['date_of_birth']) ? $input['date_of_birth'] : NULL;
$gender = !empty($input['gender']) ? $input['gender'] : NULL;

// --- 3. UPDATE QUERY ---
// Gamit tayo ng Prepared Statement para secure
$stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, date_of_birth=?, gender=? WHERE id=?");

// "sssssi" -> string, string, string, string, string, integer
$stmt->bind_param("sssssi", $fname, $lname, $phone, $dob, $gender, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Patient updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
