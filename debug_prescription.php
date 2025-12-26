<?php
session_start();
header('Content-Type: application/json');

$response = [
    'session_check' => isset($_SESSION['doctor_id']),
    'doctor_id' => $_SESSION['doctor_id'] ?? 'NOT SET',
    'session_data' => $_SESSION
];

// Database connection test
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $response['db_connection'] = 'FAILED: ' . $conn->connect_error;
} else {
    $response['db_connection'] = 'SUCCESS';
    
    // Test if doctor exists
    if (isset($_SESSION['doctor_id'])) {
        $doctor_id = $_SESSION['doctor_id'];
        $sql = "SELECT id, full_name FROM doctor_profile WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response['doctor_found'] = 'YES';
            $response['doctor_info'] = $result->fetch_assoc();
        } else {
            $response['doctor_found'] = 'NO';
        }
        $stmt->close();
        
        // Check appointments
        $appt_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ?";
        $stmt = $conn->prepare($appt_sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['appointments_count'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Check prescriptions
        $pres_sql = "SELECT COUNT(*) as count FROM prescriptions WHERE doctor_id = ?";
        $stmt = $conn->prepare($pres_sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['prescriptions_count'] = $result->fetch_assoc()['count'];
        $stmt->close();
    }
    
    $conn->close();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
