<?php
// db.php - SECURE VERSION for GitHub/Vercel (TiDB Cloud)
// Uses ENV vars ONLY - no hardcoded secrets!

$servername = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$username = getenv('DB_USER') ?: 'eDqJP1S1PhDdGLs.root';
$password = getenv('DB_PASSWORD') ?: '';  // EMPTY fallback - MUST use Vercel env var!
$dbname = getenv('DB_NAME') ?: 'gabayai';
$port = intval(getenv('DB_PORT') ?: 4000);

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Error handling for Vercel logs
if ($conn->connect_error) {
    error_log("DB Connection Failed: " . $conn->connect_error);
    http_response_code(500);
    die(json_encode(['error' => 'Database unavailable']));
}

// Set charset
$conn->set_charset("utf8mb4");
?>
