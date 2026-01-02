<?php
// get_latest_analysis.php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'gabayai';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the MOST RECENT analysis
$stmt = $pdo->prepare("SELECT * FROM symptom_analysis_history WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Decode JSON string back to array for JS
    $row['results'] = json_decode($row['results_json'], true);
    unset($row['results_json']); // Remove raw string to save bandwidth
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'No history found']);
}
?>
