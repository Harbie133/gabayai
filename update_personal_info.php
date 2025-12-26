<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

$last_name = trim($_POST['last_name'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$middle_initial = trim($_POST['middle_initial'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');

$region_code = trim($_POST['region_code'] ?? '');
$region_name = trim($_POST['region_name'] ?? '');
$city_code = trim($_POST['city_code'] ?? '');
$city_name = trim($_POST['city_name'] ?? '');
$barangay_code = trim($_POST['barangay_code'] ?? '');
$barangay_name = trim($_POST['barangay_name'] ?? '');

if (empty($last_name) || empty($first_name) || empty($phone) || empty($date_of_birth) || empty($gender)) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (empty($region_code) || empty($city_code) || empty($barangay_code)) {
    echo json_encode(['success' => false, 'message' => 'Complete address required']);
    exit;
}

require_once 'db.php';

if (!empty($email)) {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use']);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();
}

$stmt = $conn->prepare("UPDATE users SET 
    last_name = ?, first_name = ?, middle_initial = ?, email = ?, phone = ?, 
    date_of_birth = ?, gender = ?, address = ?, postal_code = ?,
    region_code = ?, region_name = ?, city_code = ?, city_name = ?,
    barangay_code = ?, barangay_name = ?, updated_at = CURRENT_TIMESTAMP 
    WHERE id = ?");

$stmt->bind_param("sssssssssssssssi", 
    $last_name, $first_name, $middle_initial, $email, $phone, 
    $date_of_birth, $gender, $address, $postal_code,
    $region_code, $region_name, $city_code, $city_name,
    $barangay_code, $barangay_name, $user_id
);

if ($stmt->execute()) {
    if (!empty($email)) $_SESSION['email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>
