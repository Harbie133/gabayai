<?php
// upload_attachment_patient.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$consultationId = isset($_POST['consultationId']) ? (int)$_POST['consultationId'] : 0;
$text           = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($consultationId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid consultation']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// simple whitelist (images + pdf)
$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
];

if (!in_array($file['type'], $allowedTypes, true)) {
    echo json_encode(['success' => false, 'error' => 'Unsupported file type']);
    exit;
}

// prepare upload folder
$baseDir   = __DIR__ . '/uploads/chat';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$consultDir = $baseDir . '/' . $consultationId;
if (!is_dir($consultDir)) {
    mkdir($consultDir, 0777, true);
}

// unique filename
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$uniqName = uniqid('att_', true) . ($ext ? '.' . $ext : '');
$target   = $consultDir . '/' . $uniqName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// relative path to store in DB (for <img src> / <a href>)
$relativePath = 'uploads/chat/' . $consultationId . '/' . $uniqName;

// 1) insert message (sender = patient)
$sender = 'patient';

$sqlMsg = "INSERT INTO messages (consultation_id, sender, text)
           VALUES (?, ?, ?)";
$stmtMsg = $conn->prepare($sqlMsg);
if (!$stmtMsg) {
    echo json_encode(['success' => false, 'error' => 'DB prepare msg failed']);
    exit;
}
$stmtMsg->bind_param('iss', $consultationId, $sender, $text);
if (!$stmtMsg->execute()) {
    echo json_encode(['success' => false, 'error' => 'DB exec msg failed']);
    exit;
}
$messageId = $stmtMsg->insert_id;
$stmtMsg->close();

// 2) insert attachment row
$sqlAtt = "INSERT INTO message_attachments (message_id, file_path, file_name, file_type)
           VALUES (?, ?, ?, ?)";
$stmtAtt = $conn->prepare($sqlAtt);
if (!$stmtAtt) {
    echo json_encode(['success' => false, 'error' => 'DB prepare att failed']);
    exit;
}
$originalName = $file['name'];
$fileType     = $file['type'];

$stmtAtt->bind_param('isss', $messageId, $relativePath, $originalName, $fileType);

if (!$stmtAtt->execute()) {
    echo json_encode(['success' => false, 'error' => 'DB exec att failed']);
    exit;
}
$stmtAtt->close();

echo json_encode([
    'success'     => true,
    'message_id'  => $messageId,
    'file_path'   => $relativePath,
    'file_name'   => $originalName,
    'file_type'   => $fileType
]);
