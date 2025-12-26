<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicare_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get user data from session
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Check if patient record exists
$check_sql = "SELECT id FROM patients WHERE account_id = ? OR email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $user_email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing patient record
    $update_sql = "UPDATE patients 
                   SET has_account = TRUE, 
                       last_login = NOW(),
                       status = 'Active'
                   WHERE account_id = ? OR email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("is", $user_id, $user_email);
    $update_stmt->execute();
    $update_stmt->close();
} else {
    // Fetch user data from users table
    $user_sql = "SELECT name, email, phone, date_of_birth, gender, address FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    if ($user_data) {
        // Generate patient ID
        $patient_id = 'PAT' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
        
        // Create new patient record
        $insert_sql = "INSERT INTO patients 
                       (patient_id, name, email, contact_number, date_of_birth, gender, address, 
                        has_account, account_id, registration_source, status, last_login) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, ?, 'Self-Registration', 'Active', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssissi", 
            $patient_id, 
            $user_data['name'], 
            $user_data['email'], 
            $user_data['phone'],
            $user_data['date_of_birth'],
            $user_data['gender'],
            $user_data['address'],
            $user_id
        );
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $user_stmt->close();
}

$check_stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Patient data synced']);
?>
