<?php
// get_user_latest_analysis.php
session_start();
header('Content-Type: application/json');

// DB Connection
$host = 'localhost';
$db   = 'gabayai';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB Connection Failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Kukunin ang pinaka-latest na analysis
$stmt = $pdo->prepare("SELECT results_json FROM symptom_analysis_history WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $results = json_decode($row['results_json'], true);
    // Extract condition names only
    $topics = [];
    if (is_array($results)) {
        foreach ($results as $r) {
            $topics[] = $r['name'];
        }
    }
    echo json_encode(['success' => true, 'topics' => $topics]);
} else {
    echo json_encode(['success' => false, 'topics' => []]);
}
?>
