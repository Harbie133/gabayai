<?php
/**
 * Create Prescription - Matches YOUR table structure
 * Table columns: prescription_id, appointment_id, user_id, doctor_id, diagnosis, additional_notes, etc.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    // Validate
    if (empty($data['patient_id'])) {
        throw new Exception('Patient ID is required');
    }
    
    if (empty($data['diagnosis'])) {
        throw new Exception('Diagnosis is required');
    }
    
    if (empty($data['medications']) || !is_array($data['medications'])) {
        throw new Exception('At least one medication is required');
    }
    
    $doctor_id = intval($_SESSION['doctor_id']);
    $patient_id = $data['patient_id'];
    $appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : null;
    $diagnosis = trim($data['diagnosis']);
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    $medications = $data['medications'];
    
    // Parse patient_id to get user_id
    // Format: 'u_17' -> extract 17
    $user_id = 0;
    
    if (strpos($patient_id, 'u_') === 0) {
        $user_id = intval(substr($patient_id, 2));
    } elseif (strpos($patient_id, 'p_') === 0) {
        $user_id = intval(substr($patient_id, 2));
    } else {
        throw new Exception('Invalid patient ID format');
    }
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert prescription using YOUR table structure
    $insert_sql = "INSERT INTO prescriptions (
                        appointment_id,
                        user_id, 
                        doctor_id, 
                        diagnosis, 
                        additional_notes,
                        status
                    ) VALUES (?, ?, ?, ?, ?, 'Active')";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("iiiss", 
        $appointment_id,
        $user_id, 
        $doctor_id, 
        $diagnosis, 
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert prescription: ' . $stmt->error);
    }
    
    $prescription_id = $conn->insert_id;
    $stmt->close();
    
    // Insert medications
    $med_sql = "INSERT INTO medications (
                    prescription_id, 
                    medication_name, 
                    dosage, 
                    frequency, 
                    duration, 
                    instructions
                ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $med_stmt = $conn->prepare($med_sql);
    if (!$med_stmt) {
        throw new Exception('Prepare medications failed: ' . $conn->error);
    }
    
    foreach ($medications as $med) {
        $med_name = isset($med['name']) ? trim($med['name']) : '';
        $med_dosage = isset($med['dosage']) ? trim($med['dosage']) : 'Not specified';
        $med_frequency = isset($med['frequency']) ? trim($med['frequency']) : 'Not specified';
        $med_duration = isset($med['duration']) ? trim($med['duration']) : 'Not specified';
        $med_instructions = isset($med['instructions']) ? trim($med['instructions']) : 'None';
        
        if (empty($med_name)) {
            throw new Exception('Medication name cannot be empty');
        }
        
        $med_stmt->bind_param("isssss",
            $prescription_id,
            $med_name,
            $med_dosage,
            $med_frequency,
            $med_duration,
            $med_instructions
        );
        
        if (!$med_stmt->execute()) {
            throw new Exception('Failed to add medication: ' . $med_stmt->error);
        }
    }
    
    $med_stmt->close();
    
    // Update appointment status to completed
    if ($appointment_id) {
        $update_sql = "UPDATE appointments SET status = 'completed' WHERE appointment_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param("i", $appointment_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription created successfully!',
        'prescription_id' => $prescription_id,
        'appointment_id' => $appointment_id
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
