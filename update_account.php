<?php
session_start();

header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_username = $conn->real_escape_string(trim($_POST['username']));
$current_password = $_POST['current_password'];
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

// Validate username
if (strlen($new_username) < 3 || strlen($new_username) > 20) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3-20 characters']);
    exit();
}

// Verify current password
$sql = "SELECT password FROM users WHERE id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$row = $result->fetch_assoc();
if (!password_verify($current_password, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit();
}

// Check if username is taken by another user
$sql = "SELECT id FROM users WHERE username = '$new_username' AND id != $user_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit();
}

// Update username and optionally password
if (!empty($new_password)) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET username = '$new_username', password = '$hashed_password' WHERE id = $user_id";
    $success_message = 'Username and password updated successfully!';
} else {
    $sql = "UPDATE users SET username = '$new_username' WHERE id = $user_id";
    $success_message = 'Username updated successfully!';
}

if ($conn->query($sql) === TRUE) {
    $_SESSION['username'] = $new_username;
    echo json_encode(['success' => true, 'message' => $success_message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating account']);
}

$conn->close();
?>
