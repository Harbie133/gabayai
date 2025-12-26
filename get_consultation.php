<?php
// get_consultation.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT 
            id,
            patient_name,
            patient_age,
            patient_sex,
            duration,
            severity,
            complaint,
            topics
        FROM consultations
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode([]);
    exit;
}

echo json_encode([
    'id'           => (int)$row['id'],
    'patient_name' => $row['patient_name'],
    'patient_age'  => $row['patient_age'],
    'patient_sex'  => $row['patient_sex'],
    'duration'     => $row['duration'],
    'severity'     => $row['severity'],
    'complaint'    => $row['complaint'],
    'topics_json'  => $row['topics'], // JSON string
]);
