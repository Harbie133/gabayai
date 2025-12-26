<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'Not logged in', 'redirect' => true]);
    exit();
}

echo json_encode([
    'success' => true,
    'name' => $_SESSION['username'] ?? 'Doctor',
    'doctor_id' => $_SESSION['doctor_id'] ?? $_SESSION['user_id'] ?? null
]);
?>
