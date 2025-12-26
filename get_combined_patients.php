<?php
/**
 * Get Patients with Appointments - FIXED VERSION
 */

session_start();
header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in as a doctor'
    ]);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

$conn->set_charset("utf8mb4");

try {
    $doctor_id = intval($_SESSION['doctor_id']);
    
    // Get patients who have appointments with THIS doctor
    $sql = "SELECT 
                a.appointment_id,
                a.patient_id,
                a.appointment_date,
                a.appointment_time,
                a.status AS appointment_status,
                a.consultation_type,
                a.symptoms,
                u.id,
                u.username,
                u.first_name,
                u.last_name,
                u.middle_initial,
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,
                u.address,
                u.city_name,
                u.region_name,
                u.barangay_name
            FROM appointments a
            INNER JOIN users u ON a.patient_id = u.id
            WHERE a.doctor_id = ?
            AND a.status IN ('pending', 'confirmed', 'in_progress')
            ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = [];
    $seenPatients = []; // Prevent duplicates
    
    while ($row = $result->fetch_assoc()) {
        $patient_id = $row['patient_id'];
        
        // Skip if we already added this patient
        if (isset($seenPatients[$patient_id])) {
            continue;
        }
        $seenPatients[$patient_id] = true;
        
        // Build full name
        $fullName = trim(
            ($row['first_name'] ?? '') . ' ' . 
            ($row['middle_initial'] ?? '') . ' ' . 
            ($row['last_name'] ?? '')
        );
        
        if (empty($fullName) || $fullName === '  ') {
            $fullName = $row['username'];
        }
        
        // Build address
        $address = implode(', ', array_filter([
            $row['address'],
            $row['barangay_name'],
            $row['city_name'],
            $row['region_name']
        ]));
        
        // Format dates
        $appointmentDate = date('M d, Y', strtotime($row['appointment_date']));
        $appointmentTime = date('h:i A', strtotime($row['appointment_time']));
        
        $patients[] = [
            'id' => 'u_' . $patient_id,
            'internal_id' => $patient_id,
            'patient_id' => 'USR' . str_pad($patient_id, 6, '0', STR_PAD_LEFT),
            'name' => $fullName,
            'username' => $row['username'],
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'contact_number' => $row['phone'] ?? '',
            'date_of_birth' => $row['date_of_birth'] ?? '',
            'gender' => $row['gender'] ?? 'N/A',
            'address' => $address,
            'appointment_id' => $row['appointment_id'],
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'appointment_status' => $row['appointment_status'],
            'consultation_type' => $row['consultation_type'] ?? 'virtual',
            'symptoms' => $row['symptoms'] ?? '',
            'source_table' => 'users'
        ];
    }
    
    $stmt->close();
    
    // Calculate stats
    $stats = [
        'total' => count($patients),
        'pending' => 0,
        'confirmed' => 0,
        'in_progress' => 0
    ];
    
    foreach ($patients as $patient) {
        $status = $patient['appointment_status'];
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'patients' => $patients,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
