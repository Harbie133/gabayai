<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $room_id = isset($data['room_id']) ? $data['room_id'] : '';
    
    if (empty($room_id)) {
        echo json_encode(['success' => false, 'message' => 'Room ID required']);
        exit();
    }
    
    // Update room status to completed
    $stmt = $conn->prepare("UPDATE teleconsultation_rooms SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE room_id = ?");
    $stmt->bind_param("s", $room_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete signals for this room (cleanup)
    $deleteStmt = $conn->prepare("DELETE FROM teleconsultation_signals WHERE room_id = ?");
    $deleteStmt->bind_param("s", $room_id);
    $deleteStmt->execute();
    $affected = $deleteStmt->affected_rows;
    $deleteStmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Room ended and cleaned up',
        'signals_deleted' => $affected
    ]);
}

$conn->close();
?>
