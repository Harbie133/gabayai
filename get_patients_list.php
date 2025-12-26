<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Adjust this query based on your patients table structure
$stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM patients ORDER BY first_name");
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

echo json_encode(['success' => true, 'patients' => $patients]);

$stmt->close();
$conn->close();
?>
