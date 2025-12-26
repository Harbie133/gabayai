<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

require_once 'db.php';

$stmt = $conn->prepare("SELECT last_name, first_name, middle_initial, email, phone, date_of_birth, gender, address, postal_code, 
                        region_code, region_name, city_code, city_name, barangay_code, barangay_name 
                        FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
} else {
    echo json_encode(['success' => true, 'data' => null]);
}

$stmt->close();
$conn->close();
?>
