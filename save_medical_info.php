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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $blood_group = isset($_POST['bloodType']) ? $conn->real_escape_string(trim($_POST['bloodType'])) : '';
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $allergies = !empty($_POST['allergies']) ? $conn->real_escape_string(trim($_POST['allergies'])) : null;
        $chronic_conditions = !empty($_POST['chronicConditions']) ? $conn->real_escape_string(trim($_POST['chronicConditions'])) : null;
        $current_medications = !empty($_POST['currentMedications']) ? $conn->real_escape_string(trim($_POST['currentMedications'])) : null;
        $medical_history = !empty(trim($_POST['medicalHistory'])) ? $conn->real_escape_string(trim($_POST['medicalHistory'])) : 'N/A';
        $family_medical_history = !empty(trim($_POST['familyMedicalHistory'])) ? $conn->real_escape_string(trim($_POST['familyMedicalHistory'])) : 'N/A';
        
        if (empty($blood_group)) {
            throw new Exception('Blood type is required');
        }
        
        $check_sql = "SELECT id FROM medical_info WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $existing = $result->fetch_assoc();
        $check_stmt->close();
        
        if ($existing) {
            $sql = "UPDATE medical_info 
                    SET blood_group = ?,
                        height = ?,
                        weight = ?,
                        allergies = ?,
                        chronic_conditions = ?,
                        current_medications = ?,
                        medical_history = ?,
                        family_medical_history = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $stmt->bind_param(
                "sddsssssi",
                $blood_group,
                $height,
                $weight,
                $allergies,
                $chronic_conditions,
                $current_medications,
                $medical_history,
                $family_medical_history,
                $user_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Execute error: ' . $stmt->error);
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Medical information updated successfully!']);
            
        } else {
            $sql = "INSERT INTO medical_info 
                    (user_id, blood_group, height, weight, allergies, chronic_conditions,
                     current_medications, medical_history, family_medical_history) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            
            $stmt->bind_param(
                "isddsssss",
                $user_id,
                $blood_group,
                $height,
                $weight,
                $allergies,
                $chronic_conditions,
                $current_medications,
                $medical_history,
                $family_medical_history
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Execute error: ' . $stmt->error);
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Medical information saved successfully!']);
        }
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
