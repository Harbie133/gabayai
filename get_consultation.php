<?php
// get_consultation.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php'; // Siguraduhin tama filename (db.php or db_connection.php)

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

// Select ALL columns (*) or specify explicitly if you prefer
$sql = "SELECT * FROM consultations WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'Consultation not found']);
    exit;
}

// Clean up / Decode JSON fields para ready-to-use na sa JavaScript
$topics = [];
if (!empty($row['topics'])) {
    $decoded = json_decode($row['topics'], true);
    $topics = is_array($decoded) ? $decoded : explode(',', $row['topics']); // Fallback for comma-separated
}

// Decode other JSON fields if needed
$allergies = !empty($row['allergies']) ? json_decode($row['allergies'], true) : [];
$med_history = !empty($row['medical_history']) ? json_decode($row['medical_history'], true) : [];

echo json_encode([
    'id'            => (int)$row['id'],
    'patient_name'  => $row['patient_name'],
    'patient_phone' => $row['patient_phone'], // Ito ang request mo
    'patient_age'   => $row['patient_age'],
    'patient_sex'   => $row['patient_sex'],
    
    // Vitals / Stats
    'temperature'   => $row['temperature'],
    'fever_now'     => $row['fever_now'],
    
    // Main Complaint
    'duration'      => $row['duration'],
    'severity'      => $row['severity'],
    'complaint'     => $row['complaint'],
    
    // Arrays / Lists
    'topics_json'   => json_encode($topics), // Send as JSON string for existing JS compatibility
    'topics_array'  => $topics,              // Send as Array (optional, easier for future use)
    'allergies'     => $allergies,
    'medical_history' => $med_history,
    'allergy_other' => $row['allergy_other'],
    
    // Meta
    'status'        => $row['status'],
    'started_at'    => $row['started_at']
]);
?>
