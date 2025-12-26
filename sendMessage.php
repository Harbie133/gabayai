<?php
// sendMessage.php  (used by both patient and doctor)
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

$consultationId = isset($input['consultationId']) ? (int)$input['consultationId'] : 0;
$messageText    = isset($input['message']) ? trim($input['message']) : '';
$sender         = isset($input['sender']) ? trim($input['sender']) : 'patient'; // default patient

// enforce allowed sender values
if ($sender !== 'patient' && $sender !== 'doctor') {
    $sender = 'patient';
}

if ($consultationId <= 0 || $messageText === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$sql  = "INSERT INTO messages (consultation_id, sender, text, created_at)
         VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB prepare error']);
    exit;
}

$stmt->bind_param('iss', $consultationId, $sender, $messageText);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB execute error']);
}

$stmt->close();
