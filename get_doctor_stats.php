<?php
session_start();
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? 0;

if(!isset($_SESSION['doctor_id']) || $_SESSION['doctor_id'] != $doctor_id) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=gabayai", $user, $pass);
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT patient_id) as total_patients,
        COUNT(CASE WHEN status='pending' THEN 1 END) as pending_chats,
        COUNT(CASE WHEN risk_level='high' THEN 1 END) as high_risk,
        COUNT(CASE WHEN DATE(created_at)=CURDATE() THEN 1 END) as today_consults
    FROM consultations WHERE doctor_id = ?
");
$stmt->execute([$doctor_id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>
