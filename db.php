<?php
// db.php - FINAL SECURE VERSION (TiDB Cloud + Vercel)

$servername = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$username = getenv('DB_USER') ?: 'eDqJP1S1PhDdGLs.root';
$password = getenv('DB_PASSWORD') ?: ''; // Empty default for safety
$dbname = getenv('DB_NAME') ?: 'gabayai';
$port = intval(getenv('DB_PORT') ?: 4000);

// Initialize MySQLi
$conn = mysqli_init();

// Set SSL options (Auto-negotiate TLS for TiDB Cloud)
// NULL means use default system CA, or just enable SSL mode without strict cert check if needed
$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false); // Optional: Disable strict cert check for easier connection

// Connect with SSL flag
if (!$conn->real_connect($servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
    // Log error for Vercel (don't show user)
    error_log("Connect Error: " . mysqli_connect_error());
    // Show generic message
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$conn->set_charset("utf8mb4");
?>
