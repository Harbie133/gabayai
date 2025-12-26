<?php
$servername = "localhost";
$username = "root";  // your db username
$password = "";      // your db password
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
