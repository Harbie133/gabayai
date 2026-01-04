<?php
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'eDqJP1S1PhDdGLs.root';
$password = getenv('DB_PASSWORD') ?: 'IgdyZ8OseRPGUjqs';
$dbname = getenv('DB_NAME') ?: 'gabayai';
$port = intval(getenv('DB_PORT') ?: 4000);

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>