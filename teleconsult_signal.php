<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit();
}

// POST: Save signal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $room_id = isset($data['room_id']) ? $data['room_id'] : '';
    $sender_id = isset($data['sender_id']) ? $data['sender_id'] : '';
    $receiver_id = isset($data['receiver_id']) ? $data['receiver_id'] : '';
    $signal_type = isset($data['signal_type']) ? $data['signal_type'] : '';
    $signal_data = isset($data['signal_data']) ? json_encode($data['signal_data']) : '';
    
    if (empty($room_id) || empty($sender_id) || empty($signal_type)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO teleconsultation_signals 
        (room_id, sender_id, receiver_id, signal_type, signal_data) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $room_id, $sender_id, $receiver_id, $signal_type, $signal_data);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Signal saved",
            "signal_id" => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to save signal"
        ]);
    }
    
    $stmt->close();
}

// GET: Retrieve signals
else {
    $room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';
    $receiver_id = isset($_GET['receiver_id']) ? $_GET['receiver_id'] : '';
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    if (empty($room_id)) {
        echo json_encode(["success" => false, "message" => "Room ID required"]);
        exit();
    }
    
    // Get signals where:
    // - Room matches
    // - ID is greater than last_id
    // - Receiver matches OR receiver is empty (broadcast)
    $stmt = $conn->prepare("SELECT id, sender_id, receiver_id, signal_type, signal_data, created_at 
        FROM teleconsultation_signals 
        WHERE room_id = ? 
        AND id > ?
        AND (receiver_id = ? OR receiver_id = '')
        ORDER BY id ASC 
        LIMIT 100");
    
    $stmt->bind_param("sis", $room_id, $last_id, $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signals = [];
    while ($row = $result->fetch_assoc()) {
        $signals[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'signal_type' => $row['signal_type'],
            'signal_data' => json_decode($row['signal_data'], true),
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        "success" => true,
        "signals" => $signals,
        "count" => count($signals)
    ]);
    
    $stmt->close();
}

$conn->close();
?>
