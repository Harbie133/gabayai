<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

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

$user_id = $_SESSION['user_id'];
$symptoms = json_encode($input['symptoms']);
$diagnosis = $input['diagnosis'];

// Update patient record to track symptom analyzer usage
$update_sql = "UPDATE patients 
               SET used_symptom_analyzer = TRUE,
                   symptom_analyzer_count = symptom_analyzer_count + 1,
                   last_symptom_check = CURDATE(),
                   registration_source = CASE 
                       WHEN registration_source IS NULL THEN 'Symptom Analyzer'
                       ELSE registration_source 
                   END
               WHERE account_id = ?";

$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Optional: Store symptom check history in separate table
$history_sql = "INSERT INTO symptom_history (patient_account_id, symptoms, diagnosis, check_date) 
                VALUES (?, ?, ?, NOW())";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("iss", $user_id, $symptoms, $diagnosis);
$history_stmt->execute();

$stmt->close();
$history_stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Symptom check tracked']);
?>
