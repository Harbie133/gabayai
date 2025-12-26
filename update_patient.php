<?php
/**
 * Update Patient in Either Users or Patients Table
 * Determines which table to update based on ID prefix
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit();
}

$patient_id = $input['patient_id']; // Format: 'u_123' or 'p_456'

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");

try {
    // Determine which table based on ID prefix
    if (strpos($patient_id, 'u_') === 0) {
        // ========== UPDATE USERS TABLE ==========
        $id = intval(str_replace('u_', '', $patient_id));
        
        // Parse full name
        $name_parts = explode(' ', trim($input['name']), 2);
        $first_name = $conn->real_escape_string($name_parts[0]);
        $last_name = isset($name_parts[1]) ? $conn->real_escape_string($name_parts[1]) : '';
        
        // Prepare user data
        $date_of_birth = $conn->real_escape_string($input['date_of_birth']);
        $gender = $conn->real_escape_string($input['gender']);
        $contact_number = $conn->real_escape_string($input['contact_number']);
        $email = $conn->real_escape_string($input['email']);
        $address = $conn->real_escape_string($input['address']);
        $is_active = ($input['status'] === 'Active') ? 1 : 0;
        
        // Update users table
        $user_sql = "UPDATE users SET 
            first_name = '$first_name',
            last_name = '$last_name',
            date_of_birth = '$date_of_birth',
            gender = '$gender',
            phone = '$contact_number',
            email = '$email',
            address = '$address',
            is_active = $is_active,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = $id";
        
        if (!$conn->query($user_sql)) {
            throw new Exception('Failed to update user: ' . $conn->error);
        }
        
        // Update or insert medical_info
        $blood_group = $conn->real_escape_string($input['blood_group']);
        $allergies = $conn->real_escape_string($input['allergies']);
        $medical_history = $conn->real_escape_string($input['medical_history']);
        $current_medications = $conn->real_escape_string($input['current_medications']);
        
        // Check if medical record exists
        $check_sql = "SELECT id FROM medical_info WHERE user_id = $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing
            $medical_sql = "UPDATE medical_info SET 
                blood_group = '$blood_group',
                allergies = '$allergies',
                medical_history = '$medical_history',
                current_medications = '$current_medications',
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = $id";
        } else {
            // Insert new
            $medical_sql = "INSERT INTO medical_info 
                (user_id, blood_group, allergies, medical_history, current_medications) 
                VALUES ($id, '$blood_group', '$allergies', '$medical_history', '$current_medications')";
        }
        
        $conn->query($medical_sql);
        
        // Update emergency contact if provided
        if (!empty($input['emergency_contact'])) {
            $emergency_phone = $conn->real_escape_string($input['emergency_contact']);
            
            // Check if emergency contact exists
            $check_emergency = "SELECT id FROM emergency_contacts WHERE user_id = $id LIMIT 1";
            $emergency_result = $conn->query($check_emergency);
            
            if ($emergency_result && $emergency_result->num_rows > 0) {
                $conn->query("UPDATE emergency_contacts SET phone = '$emergency_phone', updated_at = CURRENT_TIMESTAMP WHERE user_id = $id LIMIT 1");
            } else {
                $conn->query("INSERT INTO emergency_contacts (user_id, contact_name, relationship, phone) 
                             VALUES ($id, 'Emergency Contact', 'Family', '$emergency_phone')");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Patient updated successfully']);
        
    } elseif (strpos($patient_id, 'p_') === 0) {
        // ========== UPDATE PATIENTS TABLE ==========
        $id = intval(str_replace('p_', '', $patient_id));
        
        // Prepare patient data
        $name = $conn->real_escape_string($input['name']);
        $date_of_birth = $conn->real_escape_string($input['date_of_birth']);
        $gender = $conn->real_escape_string($input['gender']);
        $contact_number = $conn->real_escape_string($input['contact_number']);
        $email = $conn->real_escape_string($input['email']);
        $address = $conn->real_escape_string($input['address']);
        $blood_group = $conn->real_escape_string($input['blood_group']);
        $emergency_contact = $conn->real_escape_string($input['emergency_contact']);
        $status = $conn->real_escape_string($input['status']);
        $allergies = $conn->real_escape_string($input['allergies'] ?? '');
        $medical_history = $conn->real_escape_string($input['medical_history'] ?? '');
        $current_medications = $conn->real_escape_string($input['current_medications'] ?? '');
        $notes = $conn->real_escape_string($input['notes'] ?? '');
        
        // Update patients table
        $update_sql = "UPDATE patients SET 
            name = '$name',
            date_of_birth = '$date_of_birth',
            gender = '$gender',
            contact_number = '$contact_number',
            email = '$email',
            address = '$address',
            blood_group = '$blood_group',
            emergency_contact = '$emergency_contact',
            status = '$status',
            allergies = '$allergies',
            medical_history = '$medical_history',
            current_medications = '$current_medications',
            additional_notes = '$notes',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = $id";
        
        if (!$conn->query($update_sql)) {
            throw new Exception('Failed to update patient: ' . $conn->error);
        }
        
        echo json_encode(['success' => true, 'message' => 'Patient updated successfully']);
        
    } else {
        throw new Exception('Invalid patient ID format');
    }
    
} catch (Exception $e) {
    error_log("Error updating patient: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update patient: ' . $e->getMessage()]);
}

$conn->close();
?>
