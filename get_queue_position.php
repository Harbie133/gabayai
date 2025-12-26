<?php
header('Content-Type: application/json');
require 'config.php';

$id = $_GET['consult_id'] ?? null;
if (!$id) {
  echo json_encode(['error' => 'Missing consult_id']);
  exit;
}

$stmt = $pdo->prepare("SELECT doctor_id, created_at, status FROM consultations WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo json_encode(['error' => 'Not found']);
  exit;
}

$doctorId = $row['doctor_id'];
$created  = $row['created_at'];

$aheadStmt = $pdo->prepare("
  SELECT COUNT(*) AS ahead
  FROM consultations
  WHERE doctor_id = ?
    AND status IN ('pending','ongoing')
    AND created_at < ?
");
$aheadStmt->execute([$doctorId, $created]);
$ahead = (int)$aheadStmt->fetchColumn();

echo json_encode([
  'status' => $row['status'],
  'ahead'  => $ahead,
  'position' => $ahead + 1
]);
