<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['doctor_id'])) {
  http_response_code(401);
  echo json_encode(["error" => "Unauthorized"]);
  exit;
}

$doctorId = (int)$_SESSION['doctor_id'];

$stmt = $conn->prepare("
  INSERT INTO doctor_presence (doctor_id, last_seen)
  VALUES (?, NOW())
  ON DUPLICATE KEY UPDATE last_seen = NOW()
");
$stmt->bind_param("i", $doctorId);
$stmt->execute();

echo json_encode(["ok" => true]);
