<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'db.php';

// Query to fetch all patients
$sql = "SELECT 
    id,
    username,
    last_name,
    first_name,
    middle_initial,
    email,
    phone,
    date_of_birth,
    gender,
    TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age,
    address,
    city_name,
    region_name,
    barangay_name,
    profile_picture,
    is_active,
    created_at,
    updated_at
FROM users
ORDER BY created_at DESC";

$result = $conn->query($sql);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $conn->error
    ]);
    exit;
}

$patients = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format patient ID as P000001
        $patientId = 'P' . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
        
        $patients[] = [
            'id' => $row['id'],
            'patient_id' => $patientId,
            'username' => $row['username'],
            'last_name' => $row['last_name'],
            'first_name' => $row['first_name'],
            'middle_initial' => $row['middle_initial'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'date_of_birth' => $row['date_of_birth'],
            'gender' => $row['gender'],
            'age' => $row['age'],
            'address' => $row['address'],
            'city_name' => $row['city_name'],
            'region_name' => $row['region_name'],
            'barangay_name' => $row['barangay_name'],
            'profile_picture' => $row['profile_picture'],
            'is_active' => $row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'patients' => $patients,
    'total' => count($patients)
]);

$conn->close();
?>
