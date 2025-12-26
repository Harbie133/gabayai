<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// 1) Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$result = [];

// 2) Main query
$sql = "
    SELECT 
        c.id AS consultation_id,
        dp.full_name AS doctor_name,
        dp.specialization,
        c.patient_age,
        c.patient_sex,
        c.duration,
        COALESCE(m.text, '') AS last_message_preview,
        DATE_FORMAT(m.created_at, '%h:%i %p') AS last_time,
        c.status
    FROM consultations c
    JOIN doctor_profile dp
        ON dp.id = c.doctor_id
    LEFT JOIN (
        SELECT 
            consultation_id,
            text,
            created_at
        FROM messages
        WHERE id IN (
            SELECT MAX(id)
            FROM messages
            GROUP BY consultation_id
        )
    ) m
        ON m.consultation_id = c.id
    WHERE c.patient_id = ?
    ORDER BY c.id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

// 3) Build JSON array
while ($row = $res->fetch_assoc()) {
    $doctorName = $row['doctor_name'] ?: 'Doctor';

    $parts = [];
    if (!empty($row['patient_age'])) {
        $parts[] = $row['patient_age'] . ' yrs';
    }
    if (!empty($row['patient_sex'])) {
        $parts[] = $row['patient_sex'];
    }
    if (!empty($row['duration'])) {
        $parts[] = $row['duration'];
    }
    $infoPreview = count($parts) ? implode(' · ', $parts) : '';

    $result[] = [
        'consultation_id' => (int)$row['consultation_id'],
        'doctor_name'     => $doctorName,
        'specialization'  => $row['specialization'] ?? '—',
        'info_preview'    => $infoPreview,
        'last_message'    => $row['last_message_preview'] ?: '(no messages yet)',
        'last_time'       => $row['last_time'] ?: '',
        'status'          => $row['status'] ?? ''
    ];
}

$stmt->close();

echo json_encode($result);
