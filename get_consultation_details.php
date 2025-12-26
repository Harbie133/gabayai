<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_GET['consultationId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing consultationId']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$consultId = (int)$_GET['consultationId'];

$sql = "
    SELECT 
        c.id,
        c.patient_name,
        c.patient_age,
        c.patient_sex,
        c.duration,
        c.severity,
        c.complaint,
        dp.full_name     AS doctor_name,
        dp.specialization
    FROM consultations c
    JOIN doctor_profile dp ON dp.id = c.doctor_id
    WHERE c.id = ? AND c.patient_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $consultId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

// kung wala ka pang topics column, gawin muna empty list
$topics = [];

echo json_encode([
    'doctorName'           => $row['doctor_name'],
    'doctorSpecialization' => $row['specialization'],
    'patient' => [
        'name'      => $row['patient_name'],
        'age'       => $row['patient_age'],
        'sex'       => $row['patient_sex'],
        'duration'  => $row['duration'],
        'severity'  => $row['severity'],
        'complaint' => $row['complaint'],
        'topics'    => $topics
    ]
]);
