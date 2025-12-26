<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || !isset($_SESSION['doctor_username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patient_id = isset($data['patient_id']) ? $data['patient_id'] : null;
    $room_name = isset($data['room_name']) ? $data['room_name'] : 'Consultation Room';
    
    // Generate unique room ID
    $room_id = bin2hex(random_bytes(16));
    $doctor_id = $_SESSION['doctor_id'];
    
    $stmt = $conn->prepare("INSERT INTO teleconsultation_rooms 
        (room_id, doctor_id, patient_id, room_name, status) 
        VALUES (?, ?, ?, ?, 'active')");
    
    $stmt->bind_param("siis", $room_id, $doctor_id, $patient_id, $room_name);
    
    if ($stmt->execute()) {
        // Build the full URL for patient to join
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $room_url = $protocol . "://" . $host . $path . "/teleconsult_join.html?room=" . $room_id;
        
        echo json_encode([
            'success' => true,
            'room_id' => $room_id,
            'room_url' => $room_url,
            'message' => 'Room created successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create room: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
