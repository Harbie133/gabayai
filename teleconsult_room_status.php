<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed",
        "error" => $conn->connect_error
    ]);
    exit();
}

$room_id = isset($_GET['room_id']) ? trim($_GET['room_id']) : '';

if (empty($room_id)) {
    echo json_encode([
        "success" => false, 
        "message" => "No room ID provided"
    ]);
    $conn->close();
    exit();
}

// Check if room exists
$stmt = $conn->prepare("SELECT room_id, status, doctor_id, patient_id, room_name, created_at 
                        FROM teleconsultation_rooms 
                        WHERE room_id = ?");
$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid meeting code. Room not found.",
        "room" => null
    ]);
} else {
    $room = $result->fetch_assoc();
    
    echo json_encode([
        "success" => true,
        "message" => "Room found",
        "room" => $room
    ]);
}

$stmt->close();
$conn->close();
?>
