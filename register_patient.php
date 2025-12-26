<?php
/**
 * Patient Registration Handler for Users Table
 * Processes new patient registration and stores in users table
 */

session_start();
header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login first.'
    ]);
    exit();
}

// Get JSON input from the form
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data format.'
    ]);
    exit();
}

// Validate required fields
$requiredFields = ['fullName', 'dateOfBirth', 'gender', 'contactNumber', 'address'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields: ' . implode(', ', $missingFields)
    ]);
    exit();
}

// Parse full name into first, middle, last
$nameParts = explode(' ', trim($data['fullName']));
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[count($nameParts) - 1] ?? '';
$middleInitial = '';

if (count($nameParts) > 2) {
    $middleInitial = substr($nameParts[1], 0, 1);
}

// Generate username from name
$username = strtolower(str_replace(' ', '', $data['fullName'])) . rand(100, 999);

// Sanitize and prepare data
$dateOfBirth = $data['dateOfBirth'];
$gender = $data['gender'];
$contactNumber = trim($data['contactNumber']);
$email = !empty($data['email']) ? trim($data['email']) : null;
$address = trim($data['address']);

// Medical information (store in notes or separate handling)
$allergies = !empty($data['allergies']) ? trim($data['allergies']) : null;
$medicalHistory = !empty($data['medicalHistory']) ? trim($data['medicalHistory']) : null;
$currentMedications = !empty($data['currentMedications']) ? trim($data['currentMedications']) : null;
$notes = !empty($data['notes']) ? trim($data['notes']) : null;

// Database connection
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

$conn->set_charset("utf8mb4");

try {
    // Check if username already exists
    $check_sql = "SELECT id FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Regenerate username if exists
    while ($check_result->num_rows > 0) {
        $username = strtolower(str_replace(' ', '', $data['fullName'])) . rand(1000, 9999);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    }
    $check_stmt->close();
    
    // Generate default password
    $defaultPassword = 'patient' . rand(1000, 9999);
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    // Insert into users table
    $sql = "INSERT INTO users (
        username,
        first_name,
        last_name,
        middle_initial,
        email,
        phone,
        date_of_birth,
        gender,
        address,
        password,
        is_active,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "ssssssssss",
        $username,
        $firstName,
        $lastName,
        $middleInitial,
        $email,
        $contactNumber,
        $dateOfBirth,
        $gender,
        $address,
        $hashedPassword
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to register patient: ' . $stmt->error);
    }
    
    $inserted_patient_id = $conn->insert_id;
    $patient_id = 'USR' . str_pad($inserted_patient_id, 6, '0', STR_PAD_LEFT);
    
    $stmt->close();
    
    // Store medical information in medical_info table if it exists
    if ((!empty($allergies) || !empty($medicalHistory) || !empty($currentMedications)) && 
        $conn->query("SHOW TABLES LIKE 'medical_info'")->num_rows > 0) {
        
        $medical_sql = "INSERT INTO medical_info (
            patient_id,
            allergies,
            medical_history,
            current_medications,
            additional_notes
        ) VALUES (?, ?, ?, ?, ?)";
        
        $medical_stmt = $conn->prepare($medical_sql);
        
        if ($medical_stmt) {
            $medical_stmt->bind_param(
                "issss",
                $inserted_patient_id,
                $allergies,
                $medicalHistory,
                $currentMedications,
                $notes
            );
            $medical_stmt->execute();
            $medical_stmt->close();
        }
    }
    
    // Log successful registration
    error_log("Patient registered in users table: ID = $patient_id, Username = $username by Doctor ID = " . $_SESSION['doctor_id']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Patient registered successfully',
        'patient_id' => $patient_id,
        'username' => $username,
        'default_password' => $defaultPassword,
        'internal_id' => $inserted_patient_id
    ]);
    
} catch (Exception $e) {
    error_log("Patient registration error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
