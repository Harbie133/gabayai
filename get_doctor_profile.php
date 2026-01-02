<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

$sql = "SELECT title, full_name, email, phone, specialization, availability, available_days, available_hours, profile_photo FROM doctor_profile WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'title' => $row['title'] ?? '',
        'full_name' => $row['full_name'] ?? '',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '', // ✅ OPTIONAL - empty OK
        'specialization' => $row['specialization'] ?? '',
        'availability' => $row['availability'] ?? 'Available',
        'available_days' => $row['available_days'] ?? '',
        'available_hours' => $row['available_hours'] ?? '',
        'profile_photo' => $row['profile_photo'] ? 'http://localhost/gabayai/' . $row['profile_photo'] : ''
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Profile not found', 'logged_in' => true]);
}

$stmt->close();
$conn->close();
?>
