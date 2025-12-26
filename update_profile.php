<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

switch ($type) {
    case 'personal':
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $dob = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, date_of_birth = ?, gender = ?, address = ?, city = ?, state = ?, postal_code = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $full_name, $phone, $dob, $gender, $address, $city, $state, $postal_code, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Personal information updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        $stmt->close();
        break;
        
    case 'medical':
        $blood_group = $_POST['blood_group'] ?? '';
        $height = $_POST['height'] ?? null;
        $weight = $_POST['weight'] ?? null;
        $allergies = $_POST['allergies'] ?? '';
        $chronic_conditions = $_POST['chronic_conditions'] ?? '';
        $current_medications = $_POST['current_medications'] ?? '';
        $medical_history = $_POST['medical_history'] ?? '';
        $insurance_provider = $_POST['insurance_provider'] ?? '';
        $insurance_number = $_POST['insurance_number'] ?? '';
        $family_medical_history = $_POST['family_medical_history'] ?? '';
        
        // Check if record exists
        $check = $conn->prepare("SELECT id FROM medical_info WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE medical_info SET blood_group = ?, height = ?, weight = ?, allergies = ?, chronic_conditions = ?, current_medications = ?, medical_history = ?, insurance_provider = ?, insurance_number = ?, family_medical_history = ? WHERE user_id = ?");
            $stmt->bind_param("sddsssssssi", $blood_group, $height, $weight, $allergies, $chronic_conditions, $current_medications, $medical_history, $insurance_provider, $insurance_number, $family_medical_history, $user_id);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO medical_info (user_id, blood_group, height, weight, allergies, chronic_conditions, current_medications, medical_history, insurance_provider, insurance_number, family_medical_history) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddssssss", $user_id, $blood_group, $height, $weight, $allergies, $chronic_conditions, $current_medications, $medical_history, $insurance_provider, $insurance_number, $family_medical_history);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medical information updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        $stmt->close();
        $check->close();
        break;
        
    case 'emergency':
        // Check if it's a delete request
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['delete']) && $input['delete'] === true) {
            $contact_id = $input['contact_id'];
            $stmt = $conn->prepare("DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $contact_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Contact deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
            $stmt->close();
            break;
        }
        
        // Add or update contact
        $contact_id = $_POST['contact_id'] ?? '';
        $contact_name = $_POST['contact_name'] ?? '';
        $relationship = $_POST['relationship'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $alternate_phone = $_POST['alternate_phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $is_primary = isset($_POST['is_primary']) ? 1 : 0;
        
        // If setting as primary, unset other primary contacts
        if ($is_primary) {
            $unset_primary = $conn->prepare("UPDATE emergency_contacts SET is_primary = 0 WHERE user_id = ?");
            $unset_primary->bind_param("i", $user_id);
            $unset_primary->execute();
            $unset_primary->close();
        }
        
        if (!empty($contact_id)) {
            // Update existing contact
            $stmt = $conn->prepare("UPDATE emergency_contacts SET contact_name = ?, relationship = ?, phone = ?, alternate_phone = ?, email = ?, address = ?, is_primary = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssssssiiii", $contact_name, $relationship, $phone, $alternate_phone, $email, $address, $is_primary, $contact_id, $user_id);
        } else {
            // Insert new contact
            $stmt = $conn->prepare("INSERT INTO emergency_contacts (user_id, contact_name, relationship, phone, alternate_phone, email, address, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $user_id, $contact_name, $relationship, $phone, $alternate_phone, $email, $address, $is_primary);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Emergency contact saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save contact']);
        }
        $stmt->close();
        break;
        
    case 'account':
        $email = $_POST['email'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        // Check if email already exists for another user
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            $check->close();
            break;
        }
        $check->close();
        
        // If changing password, verify current password
        if (!empty($new_password)) {
            if (empty($current_password)) {
                echo json_encode(['success' => false, 'message' => 'Current password required']);
                break;
            }
            
            $verify = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $verify->bind_param("i", $user_id);
            $verify->execute();
            $verify_result = $verify->get_result();
            $user_data = $verify_result->fetch_assoc();
            
            if (!password_verify($current_password, $user_data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                $verify->close();
                break;
            }
            $verify->close();
            
            // Update email and password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $email, $hashed_password, $user_id);
        } else {
            // Update only email
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['email'] = $email;
            echo json_encode(['success' => true, 'message' => 'Account settings updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
}

$conn->close();
?>
