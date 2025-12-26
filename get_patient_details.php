<?php
/**
 * Get Patient Details from Either Users or Patients Table
 * Handles both 'u_' (users) and 'p_' (patients) ID prefixes
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit();
}

$patient_id = $_GET['id']; // Format: 'u_123' or 'p_456'

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
    $patient = null;
    
    // Determine which table based on ID prefix
    if (strpos($patient_id, 'u_') === 0) {
        // ========== FETCH FROM USERS TABLE ==========
        $id = intval(str_replace('u_', '', $patient_id));
        
        $sql = "SELECT 
            u.*,
            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as name,
            m.blood_group,
            m.height,
            m.weight,
            m.allergies,
            m.chronic_conditions,
            m.current_medications,
            m.medical_history,
            m.family_medical_history,
            CASE 
                WHEN u.is_active = 1 THEN 'Active'
                ELSE 'Inactive'
            END as status,
            CONCAT_WS(', ', u.address, u.barangay_name, u.city_name, u.region_name) as full_address
        FROM users u
        LEFT JOIN medical_info m ON u.id = m.user_id
        WHERE u.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $patient = $result->fetch_assoc();
            
            // Fix name if empty
            if (empty(trim($patient['name'])) || trim($patient['name']) == '') {
                $patient['name'] = $patient['username'];
            }
            
            // Get emergency contacts
            $emergency_sql = "SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY is_primary DESC LIMIT 1";
            $emergency_stmt = $conn->prepare($emergency_sql);
            $emergency_stmt->bind_param("i", $id);
            $emergency_stmt->execute();
            $emergency_result = $emergency_stmt->get_result();
            
            if ($emergency_result->num_rows > 0) {
                $emergency = $emergency_result->fetch_assoc();
                $patient['emergency_contact'] = $emergency['phone'];
                $patient['emergency_contact_name'] = $emergency['contact_name'];
            } else {
                $patient['emergency_contact'] = '';
                $patient['emergency_contact_name'] = '';
            }
            
            // Use full address if main address is empty
            if (empty($patient['address'])) {
                $patient['address'] = $patient['full_address'];
            }
            
            $patient['additional_notes'] = ''; // Can be extended later
            $patient['contact_number'] = $patient['phone'];
            
            $emergency_stmt->close();
        }
        
        $stmt->close();
        
    } elseif (strpos($patient_id, 'p_') === 0) {
        // ========== FETCH FROM PATIENTS TABLE ==========
        $id = intval(str_replace('p_', '', $patient_id));
        
        $sql = "SELECT 
            *,
            CASE 
                WHEN status IS NULL THEN 'Active'
                ELSE status
            END as status
        FROM patients 
        WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $patient = $result->fetch_assoc();
            
            // Set defaults for fields that might not exist
            $patient['additional_notes'] = $patient['additional_notes'] ?? '';
            $patient['email'] = $patient['email'] ?? '';
            $patient['allergies'] = $patient['allergies'] ?? '';
            $patient['medical_history'] = $patient['medical_history'] ?? '';
            $patient['current_medications'] = $patient['current_medications'] ?? '';
        }
        
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid patient ID format']);
        $conn->close();
        exit();
    }
    
    if ($patient) {
        echo json_encode([
            'success' => true,
            'patient' => $patient
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
    }
    
} catch (Exception $e) {
    error_log("Error fetching patient details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch patient: ' . $e->getMessage()]);
}

$conn->close();
?>
