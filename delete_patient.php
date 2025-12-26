<?php
/**
 * Delete Patient from Either Users or Patients Table
 * Determines which table to delete from based on ID prefix
 */

session_start();
header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['patient_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Patient ID is required'
    ]);
    exit();
}

$patient_id = $data['patient_id']; // Format: 'u_123' or 'p_456'

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

$conn->set_charset("utf8mb4");

try {
    // Determine which table based on ID prefix
    if (strpos($patient_id, 'u_') === 0) {
        // Delete from users table
        $id = intval(str_replace('u_', '', $patient_id));
        $table = 'users';
        
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
    } elseif (strpos($patient_id, 'p_') === 0) {
        // Delete from patients table
        $id = intval(str_replace('p_', '', $patient_id));
        $table = 'patients';
        
        // Also delete related records
        $conn->query("DELETE FROM medical_info WHERE patient_id = $id");
        $conn->query("DELETE FROM emergency_contacts WHERE patient_id = $id");
        $conn->query("DELETE FROM appointments WHERE patient_id = $id");
        
        $sql = "DELETE FROM patients WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
    } else {
        throw new Exception('Invalid patient ID format');
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete patient');
    }
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        
        // Log deletion
        error_log("Patient deleted from $table: ID = $id by Doctor ID = " . $_SESSION['doctor_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient deleted successfully',
            'deleted_from' => $table
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Patient not found or already deleted'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting patient: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete patient: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
