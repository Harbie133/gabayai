<?php
session_start();
include('db.php');

header('Content-Type: application/json');

// Check if doctor is logged in using your session structure
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized', 'redirect' => true]);
    exit();
}

// Check for session timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    // Session expired
    session_unset();
    session_destroy();
    echo json_encode(['error' => 'Session expired', 'redirect' => true]);
    exit();
}

// Use doctor_id if available, otherwise use user_id
$doctor_id = $_SESSION['doctor_id'] ?? $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Base query - updated to match your database structure
$sql = "SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.symptoms,
    a.status,
    a.consultation_type,
    a.notes,
    p.id as patient_id,
    p.full_name as patient_name
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.id
WHERE a.doctor_id = ?";

// Add filter conditions
if ($filter === 'today') {
    $sql .= " AND DATE(a.appointment_date) = CURDATE()";
} elseif ($filter !== 'all') {
    // Map frontend statuses to your database statuses
    if ($filter === 'completed') {
        $sql .= " AND a.status = 'completed'";
    } elseif ($filter === 'pending') {
        $sql .= " AND a.status = 'pending'";
    } elseif ($filter === 'confirmed') {
        $sql .= " AND a.status = 'confirmed'";
    }
}

$sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

// Prepare and execute
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    // Format date and time
    $date = new DateTime($row['appointment_date']);
    $time = new DateTime($row['appointment_time']);
    
    $appointments[] = [
        'id' => $row['appointment_id'],
        'patient_name' => $row['patient_name'],
        'patient_id' => 'PAT' . str_pad($row['patient_id'], 6, '0', STR_PAD_LEFT),
        'date' => $date->format('M d, Y'),
        'time' => $time->format('g:i A'),
        'reason' => $row['symptoms'] ?? 'General consultation',
        'status' => $row['status'],
        'consultation_type' => $row['consultation_type']
    ];
}

// Calculate stats - updated for your database
$stats_sql = "SELECT 
    COUNT(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 END) as today_count,
    COUNT(CASE WHEN DATE(appointment_date) > CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 END) as upcoming_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
FROM appointments 
WHERE doctor_id = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $doctor_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'appointments' => $appointments,
    'stats' => $stats
]);

$stmt->close();
$stats_stmt->close();
$conn->close();
?>
