<?php
session_start();
header('Content-Type: application/json');

// Force set session for testing
$_SESSION['doctor_id'] = 1; // Change to your actual doctor ID

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

// Test data
$testData = [
    'patient_id' => 'u_17', // Change this to a real patient ID from your users table
    'appointment_id' => 1,   // Change this to a real appointment ID
    'diagnosis' => 'Test Diagnosis',
    'notes' => 'Test notes',
    'medications' => [
        [
            'name' => 'Test Medication',
            'dosage' => '1 tablet',
            'frequency' => 'Once daily',
            'duration' => '7 days',
            'instructions' => 'Take with food'
        ]
    ]
];

try {
    $doctor_id = intval($_SESSION['doctor_id']);
    $patient_id = $testData['patient_id'];
    $appointment_id = intval($testData['appointment_id']);
    
    // Parse patient_id
    $source_table = 'users';
    $internal_id = 0;
    
    if (strpos($patient_id, 'u_') === 0) {
        $source_table = 'users';
        $internal_id = intval(substr($patient_id, 2));
    } elseif (strpos($patient_id, 'p_') === 0) {
        $source_table = 'patients';
        $internal_id = intval(substr($patient_id, 2));
    }
    
    echo json_encode([
        'debug' => [
            'doctor_id' => $doctor_id,
            'patient_id' => $patient_id,
            'internal_id' => $internal_id,
            'source_table' => $source_table,
            'appointment_id' => $appointment_id
        ]
    ]);
    
    // Check if prescriptions table has appointment_id column
    $result = $conn->query("DESCRIBE prescriptions");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug info',
        'columns_in_prescriptions_table' => $columns,
        'test_data' => $testData
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

$conn->close();
?>
