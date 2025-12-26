<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

// Build query with filters
$query = "SELECT a.*, p.name as patient_name, p.contact_number, p.email 
          FROM appointments a 
          LEFT JOIN patients p ON a.patient_id = p.id 
          WHERE a.doctor_id = ?";

$params = [$doctor_id];
$types = "i";

// Add date filter
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $_GET['date'];
    $types .= "s";
}

// Add status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $query .= " AND a.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Add search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $query .= " AND (p.name LIKE ? OR p.contact_number LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode([
    'success' => true,
    'appointments' => $appointments
]);

$stmt->close();
$conn->close();
?>
