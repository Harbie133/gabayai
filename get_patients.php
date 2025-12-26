<?php
/**
 * Get Combined Patients List
 * Fetches patients from BOTH users table and patients table
 * Merges them into a unified list with blood group from medical_info
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
    $allPatients = [];
    
    // ========== FETCH FROM USERS TABLE WITH MEDICAL_INFO ==========
    $users_sql = "SELECT 
                    u.id,
                    u.username,
                    CONCAT(COALESCE(u.first_name, ''), ' ', 
                           COALESCE(u.middle_initial, ''), ' ', 
                           COALESCE(u.last_name, '')) AS name,
                    u.first_name,
                    u.last_name,
                    u.date_of_birth,
                    u.gender,
                    u.email,
                    u.phone AS contact_number,
                    CONCAT_WS(', ', 
                        u.address, 
                        u.barangay_name, 
                        u.city_name, 
                        u.region_name
                    ) AS full_address,
                    u.is_active,
                    u.created_at AS registration_date,
                    m.blood_group,
                    m.allergies,
                    m.chronic_conditions,
                    m.current_medications,
                    m.medical_history,
                    m.height,
                    m.weight,
                    ec.phone AS emergency_contact,
                    'users' AS source_table
                FROM users u
                LEFT JOIN medical_info m ON u.id = m.user_id
                LEFT JOIN emergency_contacts ec ON u.id = ec.user_id
                WHERE 1=1
                GROUP BY u.id";
    
    $users_result = $conn->query($users_sql);
    
    if ($users_result) {
        while ($row = $users_result->fetch_assoc()) {
            // Determine status
            $status = ($row['is_active'] == 1) ? 'Active' : 'Inactive';
            
            // Build full name
            $fullName = trim($row['name']);
            if (empty($fullName) || $fullName == '  ') {
                $fullName = $row['username'];
            }
            
            // Generate patient ID
            $patient_id = 'USR' . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
            
            $allPatients[] = [
                'id' => 'u_' . $row['id'], // Prefix with 'u_' to identify users table
                'internal_id' => $row['id'],
                'patient_id' => $patient_id,
                'name' => $fullName,
                'date_of_birth' => $row['date_of_birth'],
                'gender' => $row['gender'] ?? 'N/A',
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'address' => $row['full_address'],
                'blood_group' => $row['blood_group'] ?? 'N/A',
                'emergency_contact' => $row['emergency_contact'],
                'allergies' => $row['allergies'],
                'chronic_conditions' => $row['chronic_conditions'],
                'current_medications' => $row['current_medications'],
                'medical_history' => $row['medical_history'],
                'height' => $row['height'],
                'weight' => $row['weight'],
                'registration_date' => $row['registration_date'],
                'registration_source' => 'User Account',
                'status' => $status,
                'source_table' => 'users'
            ];
        }
    }
    
    // ========== FETCH FROM PATIENTS TABLE WITH MEDICAL_INFO ==========
    $patients_sql = "SELECT 
                        p.id,
                        p.patient_id,
                        p.name,
                        p.date_of_birth,
                        p.gender,
                        p.contact_number,
                        p.email,
                        p.address,
                        COALESCE(m.blood_group, p.blood_group) as blood_group,
                        p.emergency_contact,
                        p.registration_source,
                        p.registration_date,
                        p.status,
                        COALESCE(m.allergies, p.allergies) as allergies,
                        COALESCE(m.current_medications, p.current_medications) as current_medications,
                        COALESCE(m.medical_history, p.medical_history) as medical_history,
                        m.chronic_conditions,
                        m.height,
                        m.weight,
                        'patients' AS source_table
                    FROM patients p
                    LEFT JOIN medical_info m ON p.id = m.user_id
                    WHERE 1=1";
    
    $patients_result = $conn->query($patients_sql);
    
    if ($patients_result) {
        while ($row = $patients_result->fetch_assoc()) {
            $allPatients[] = [
                'id' => 'p_' . $row['id'], // Prefix with 'p_' to identify patients table
                'internal_id' => $row['id'],
                'patient_id' => $row['patient_id'],
                'name' => $row['name'],
                'date_of_birth' => $row['date_of_birth'],
                'gender' => $row['gender'],
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'address' => $row['address'],
                'blood_group' => $row['blood_group'] ?? 'N/A',
                'emergency_contact' => $row['emergency_contact'],
                'allergies' => $row['allergies'],
                'chronic_conditions' => $row['chronic_conditions'],
                'current_medications' => $row['current_medications'],
                'medical_history' => $row['medical_history'],
                'height' => $row['height'],
                'weight' => $row['weight'],
                'registration_date' => $row['registration_date'],
                'registration_source' => $row['registration_source'] ?? 'Doctor-Added',
                'status' => $row['status'] ?? 'Active',
                'source_table' => 'patients'
            ];
        }
    }
    
    // Sort all patients by registration date (newest first)
    usort($allPatients, function($a, $b) {
        return strtotime($b['registration_date']) - strtotime($a['registration_date']);
    });
    
    // ========== CALCULATE STATISTICS ==========
    $stats = [];
    
    // Total patients (both tables)
    $stats['total'] = count($allPatients);
    
    // Active patients
    $stats['active'] = 0;
    foreach ($allPatients as $patient) {
        if ($patient['status'] === 'Active') {
            $stats['active']++;
        }
    }
    
    // New patients this month
    $stats['new_this_month'] = 0;
    $currentMonth = date('Y-m');
    foreach ($allPatients as $patient) {
        if ($patient['registration_date'] && 
            date('Y-m', strtotime($patient['registration_date'])) === $currentMonth) {
            $stats['new_this_month']++;
        }
    }
    
    // Additional stats
    $stats['from_users'] = 0;
    $stats['from_patients'] = 0;
    $stats['with_blood_group'] = 0;
    
    foreach ($allPatients as $patient) {
        if ($patient['source_table'] === 'users') {
            $stats['from_users']++;
        } else {
            $stats['from_patients']++;
        }
        
        if (!empty($patient['blood_group']) && $patient['blood_group'] !== 'N/A') {
            $stats['with_blood_group']++;
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'patients' => $allPatients,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching patients: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch patients: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
