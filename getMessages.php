<?php
// getMessages.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$consultationId = isset($_GET['consultationId']) ? (int)$_GET['consultationId'] : 0;
$sinceId        = isset($_GET['since']) ? (int)$_GET['since'] : 0;

if ($consultationId <= 0) {
    echo json_encode([]);
    exit;
}

// kuha messages + optional isang attachment row (kung meron)
$sql = "
    SELECT 
        m.id,
        m.sender,
        m.text,
        DATE_FORMAT(m.created_at, '%h:%i %p') AS time,
        a.file_path,
        a.file_name,
        a.file_type
    FROM messages m
    LEFT JOIN message_attachments a
        ON a.message_id = m.id
    WHERE m.consultation_id = ?
      AND m.id > ?
    ORDER BY m.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $consultationId, $sinceId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $msg = [
        'id'     => (int)$row['id'],
        'sender' => $row['sender'],   // 'patient' or 'doctor'
        'text'   => $row['text'],
        'time'   => $row['time'],
    ];

    if (!empty($row['file_path'])) {
        $msg['attachment'] = [
            'file_path' => $row['file_path'],   // e.g. uploads/chat/16/att_xxx.jpg
            'file_name' => $row['file_name'],
            'file_type' => $row['file_type'],
        ];
    }

    $messages[] = $msg;
}

echo json_encode($messages);
