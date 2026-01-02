<?php
$servername = "sql310.infinityfree.com";
$username = "if0_40257822";  // your db username
$password = "UcdQSOY1E2HprQQ";      // your db password
$dbname = "if0_40257822_gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
