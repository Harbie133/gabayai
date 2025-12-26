<?php
/**
 * Get Prescriptions - Matches YOUR table structure
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
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");

try {
    $doctor_id = intval($_SESSION['doctor_id']);
    
    // Get prescriptions using YOUR table structure (user_id, not patient_identifier)
    $sql = "SELECT 
                p.prescription_id,
                p.appointment_id,
                p.user_id,
                p.diagnosis,
                p.additional_notes,
                p.prescription_date,
                p.status,
                u.username,
                CONCAT(COALESCE(u.first_name, ''), ' ', 
                       COALESCE(u.middle_initial, ''), ' ', 
                       COALESCE(u.last_name, '')) AS name,
                a.appointment_date,
                a.appointment_time,
                a.status AS appointment_status
            FROM prescriptions p
            INNER JOIN users u ON p.user_id = u.id
            LEFT JOIN appointments a ON p.appointment_id = a.appointment_id
            WHERE p.doctor_id = ?
            ORDER BY p.prescription_date DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prescriptions = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get medications
        $med_sql = "SELECT 
                        medication_name AS name,
                        dosage,
                        frequency,
                        duration,
                        instructions
                    FROM medications
                    WHERE prescription_id = ?";
        
        $med_stmt = $conn->prepare($med_sql);
        $prescription_id = intval($row['prescription_id']);
        $med_stmt->bind_param("i", $prescription_id);
        $med_stmt->execute();
        $med_result = $med_stmt->get_result();
        
        $medications = [];
        while ($med = $med_result->fetch_assoc()) {
            $medications[] = $med;
        }
        $med_stmt->close();
        
        // Build patient name
        $patient_name = trim($row['name']);
        if (empty($patient_name) || $patient_name === '  ') {
            $patient_name = $row['username'];
        }
        
        // Format dates
        $appointment_date = '';
        $appointment_time = '';
        if (!empty($row['appointment_date'])) {
            $appointment_date = date('M d, Y', strtotime($row['appointment_date']));
        }
        if (!empty($row['appointment_time'])) {
            $appointment_time = date('h:i A', strtotime($row['appointment_time']));
        }
        
        $prescriptions[] = [
            'prescription_id' => $row['prescription_id'],
            'appointment_id' => $row['appointment_id'],
            'user_id' => $row['user_id'],
            'patient_name' => $patient_name,
            'patient_username' => $row['username'],
            'diagnosis' => $row['diagnosis'],
            'additional_notes' => $row['additional_notes'],
            'prescription_date' => $row['prescription_date'],
            'status' => $row['status'],
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'appointment_status' => $row['appointment_status'],
            'medications' => $medications
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'prescriptions' => $prescriptions,
        'total' => count($prescriptions)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
