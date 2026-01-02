<?php
// upload_attachment_doctor.php

// 1. Error Handling (Log to file, don't break JSON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

function sendJson($success, $msg, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'error' => $success ? '' : $msg], $data));
    exit;
}

require 'db.php'; // Siguraduhin tama filename (db.php or db_connection.php)

// 2. Auth Check
if (!isset($_SESSION['doctor_id'])) {
    sendJson(false, 'Unauthorized: Please log in.');
}

// 3. Input Check
$consultation_id = isset($_POST['consultationId']) ? intval($_POST['consultationId']) : 0;
$text_content    = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($consultation_id <= 0 || !isset($_FILES['file'])) {
    sendJson(false, 'Missing required data (ID or File).');
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJson(false, 'File upload error code: ' . $file['error']);
}

// 4. Folder Setup: uploads/chat/{consultation_id}/
// Para tugma sa Patient Side logic
$baseDir = 'uploads/chat/';
$consultDir = $baseDir . $consultation_id . '/';

if (!is_dir($baseDir)) { mkdir($baseDir, 0755, true); }
if (!is_dir($consultDir)) { mkdir($consultDir, 0755, true); }

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
// Unique name para iwas overwrite
$unique_name = 'doc_' . uniqid() . '.' . $ext;
$targetPath = $consultDir . $unique_name;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    sendJson(false, 'Failed to move uploaded file. Check folder permissions.');
}

// 5. Database Insert
// Table: messages
$sender = 'doctor';
$sql1 = "INSERT INTO messages (consultation_id, sender, text, created_at) VALUES (?, ?, ?, NOW())";
$stmt1 = $conn->prepare($sql1);
if (!$stmt1) { sendJson(false, 'DB Error (Message): ' . $conn->error); }

$stmt1->bind_param("iss", $consultation_id, $sender, $text_content);
if (!$stmt1->execute()) { sendJson(false, 'DB Insert Error (Message): ' . $stmt1->error); }

$message_id = $stmt1->insert_id;
$stmt1->close();

// Table: message_attachments
$original_name = $file['name'];
$file_type = $file['type'];

$sql2 = "INSERT INTO message_attachments (message_id, file_path, file_name, file_type, created_at) VALUES (?, ?, ?, ?, NOW())";
$stmt2 = $conn->prepare($sql2);
if (!$stmt2) { sendJson(false, 'DB Error (Attachment): ' . $conn->error); }

$stmt2->bind_param("isss", $message_id, $targetPath, $original_name, $file_type);

if (!$stmt2->execute()) { sendJson(false, 'DB Insert Error (Attachment): ' . $stmt2->error); }

$stmt2->close();
$conn->close();

sendJson(true, 'Success', [
    'message_id' => $message_id,
    'file_path' => $targetPath,
    'file_name' => $original_name
]);
?>
