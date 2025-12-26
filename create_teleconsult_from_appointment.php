<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;
    
    if ($appointment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
        exit();
    }
    
    $doctor_id = $_SESSION['doctor_id'];
    
    // Verify appointment belongs to this doctor and check if code already exists
    $stmt = $conn->prepare("SELECT appointment_id, patient_id, appointment_date, meeting_link 
                            FROM appointments 
                            WHERE appointment_id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit();
    }
    
    $appointment = $result->fetch_assoc();
    $patient_id = $appointment['patient_id'];
    $existing_link = $appointment['meeting_link'];
    $stmt->close();
    
    // CHECK IF CODE ALREADY EXISTS
    if (!empty($existing_link)) {
        // Extract room_id from existing link
        if (preg_match('/room=([a-f0-9]+)/i', $existing_link, $matches)) {
            $room_id = $matches[1];
            
            // Check if room is still active
            $checkStmt = $conn->prepare("SELECT room_id, status FROM teleconsultation_rooms WHERE room_id = ?");
            $checkStmt->bind_param("s", $room_id);
            $checkStmt->execute();
            $roomResult = $checkStmt->get_result();
            
            if ($roomResult->num_rows > 0) {
                $room = $roomResult->fetch_assoc();
                
                // If room exists and is active or completed, return existing code
                if ($room['status'] === 'active' || $room['status'] === 'completed') {
                    $checkStmt->close();
                    
                    echo json_encode([
                        'success' => true,
                        'room_id' => $room_id,
                        'room_url' => $existing_link,
                        'appointment_id' => $appointment_id,
                        'message' => 'Existing room code retrieved',
                        'existing' => true
                    ]);
                    $conn->close();
                    exit();
                }
            }
            $checkStmt->close();
        }
    }
    
    // Generate NEW room ID if no existing active room
    $room_id = bin2hex(random_bytes(16));
    $room_name = "Appointment Consultation - " . $appointment['appointment_date'];
    
    // Insert into teleconsultation_rooms
    $stmt = $conn->prepare("INSERT INTO teleconsultation_rooms 
        (room_id, doctor_id, patient_id, appointment_id, room_name, status) 
        VALUES (?, ?, ?, ?, ?, 'active')");
    
    $stmt->bind_param("siiis", $room_id, $doctor_id, $patient_id, $appointment_id, $room_name);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to create room: ' . $stmt->error]);
        exit();
    }
    $stmt->close();
    
    // Build meeting URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $room_url = $protocol . "://" . $host . $path . "/teleconsult_join.html?room=" . $room_id;
    
    // Update appointment with meeting link and status
    $updateStmt = $conn->prepare("UPDATE appointments 
                                  SET meeting_link = ?, 
                                      status = 'confirmed',
                                      updated_at = CURRENT_TIMESTAMP 
                                  WHERE appointment_id = ?");
    $updateStmt->bind_param("si", $room_url, $appointment_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    echo json_encode([
        'success' => true,
        'room_id' => $room_id,
        'room_url' => $room_url,
        'appointment_id' => $appointment_id,
        'message' => 'New room created successfully',
        'existing' => false
    ]);
}

$conn->close();
?>
