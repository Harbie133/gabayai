<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Fetch symptoms
$sql = "SELECT id, symptom_name AS name FROM symptoms";
$result = $conn->query($sql);

$symptoms = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $symptoms[] = $row;
    }
}

echo json_encode($symptoms);
$conn->close();
?>
