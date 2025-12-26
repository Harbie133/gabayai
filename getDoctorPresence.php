<?php
header('Content-Type: application/json');
require_once 'db.php'; // yung $conn mo (dbname=gabayai)

$seconds = 20;

// Kunin online doctors based sa doctor_presence.last_seen
$stmt = $conn->prepare("
  SELECT doctor_id
  FROM doctor_presence
  WHERE last_seen >= (NOW() - INTERVAL ? SECOND)
");
$stmt->bind_param("i", $seconds);
$stmt->execute();

$res = $stmt->get_result();
$ids = [];

while ($row = $res->fetch_assoc()) {
  $ids[] = (int)$row['doctor_id'];
}

echo json_encode(["onlineIds" => $ids]);
