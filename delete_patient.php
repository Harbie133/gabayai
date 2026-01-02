<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'gabayai';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}
$id = (int)$input['id'];

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
