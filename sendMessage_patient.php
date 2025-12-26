<?php
// sendMessage_patient.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';          // <-- ito na gamit mo sa ibang PHP

$input = json_decode(file_get_contents('php://input'), true);

$consultationId = isset($input['consultationId']) ? (int)$input['consultationId'] : 0;
$messageText    = isset($input['message']) ? trim($input['message']) : '';

// patient side: fixed sender
$sender = 'patient';

if ($consultationId <= 0 || $messageText === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$sql  = "INSERT INTO messages (consultation_id, sender, text) VALUES (?, ?, ?)";
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
