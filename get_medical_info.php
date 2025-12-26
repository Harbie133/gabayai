<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
session_start();
require_once 'db.php';
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT blood_group, height, weight, allergies, chronic_conditions,
                   current_medications, medical_history, family_medical_history
            FROM medical_info 
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medical_info = $result->fetch_assoc();
    $stmt->close();
    
    if ($medical_info) {
        echo json_encode(['success' => true, 'data' => $medical_info]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No medical information found']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
