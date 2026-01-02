<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}
$conn->set_charset("utf8mb4");

// ✅ ONLY DOCTORS WITH online_status = 'Available' (TELEMEDICINE READY)
$sql = "SELECT id FROM doctor_profile 
        WHERE online_status = 'Available'
        AND full_name IS NOT NULL 
        AND full_name != '' 
        AND specialization IS NOT NULL 
        AND specialization != ''";

$result = $conn->query($sql);

$onlineIds = [];
while($row = $result->fetch_assoc()) {
    $onlineIds[] = $row['id'];
}

echo json_encode($onlineIds);
$conn->close();
?>
