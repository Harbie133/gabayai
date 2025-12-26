<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode([]);
    exit;
}
$doctorId = (int)$_SESSION['doctor_id'];

/*
  NOTE:
  - Wala kang c.created_at column sa consultations, kaya hindi na ito ginagamit.
  - Gagamitin natin ang latest message created_at; kung wala pang message,
    gagamitin na lang natin ang consultation id bilang sort fallback.
*/

$sql = "
SELECT 
    c.id AS consultation_id,
    c.patient_name,
    c.status,
    COALESCE(m.text, '') AS last_message,
    -- oras ng huling message kung meron, else blank
    CASE 
        WHEN m.created_at IS NOT NULL 
            THEN DATE_FORMAT(m.created_at, '%h:%i %p')
        ELSE ''
    END AS last_time,
    -- display lang: gamitin natin ang id bilang 'created_time' fallback
    c.id AS created_time_raw
FROM consultations c
LEFT JOIN messages m ON m.id = (
    SELECT id FROM messages
    WHERE consultation_id = c.id
    ORDER BY created_at DESC
    LIMIT 1
)
WHERE c.doctor_id = ?
ORDER BY COALESCE(m.created_at, NOW()) DESC, c.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctorId);
$stmt->execute();
$result = $stmt->get_result();

$convs = [];
while ($row = $result->fetch_assoc()) {
    $convs[] = [
        'consultation_id' => (int)$row['consultation_id'],
        'patient_name'    => $row['patient_name'],
        'status'          => $row['status'],
        'last_message'    => $row['last_message'],
        'last_time'       => $row['last_time'],
        // simple string lang, kung gusto mong ipakita sa UI later
        'created_time'    => (string)$row['created_time_raw'],
    ];
}

echo json_encode($convs);
