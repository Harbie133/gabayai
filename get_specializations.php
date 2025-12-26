<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, specialization_name FROM specialization_list";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['specialization_name']) . "</option>";
  }
}

$conn->close();
?>
