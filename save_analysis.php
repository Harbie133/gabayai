<?php
// save_analysis.php
session_start();
header('Content-Type: application/json');

// --- DEBUG LOGGING FUNCTION ---
function logDebug($msg) {
    file_put_contents('debug_log.txt', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

// 1. Database Connection
$host = 'localhost';
$db   = 'gabayai';
$user = 'root';
$pass = ''; // Default XAMPP/Laragon password is usually empty
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    logDebug("DB Connection Failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// 2. Auth Check & Fallback
$user_id = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    logDebug("Session User ID found: " . $user_id);
} else {
    // --- FALLBACK FOR TESTING ---
    // Kung walang session, subukan natin gamitin ang ID #1 (siguraduhin mong merong user na may ID=1 sa users table)
    // Kapag production na, tanggalin mo ito.
    $user_id = 1; 
    logDebug("No Session found. Using Fallback User ID: 1");
}

// Check if User ID really exists in DB to prevent Foreign Key Error
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtCheck->execute([$user_id]);
if ($stmtCheck->rowCount() == 0) {
    logDebug("User ID $user_id does NOT exist in users table. Cannot save.");
    echo json_encode(['success' => false, 'error' => 'Invalid User ID']);
    exit;
}

// 3. Get JSON Data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    logDebug("No JSON data received.");
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// 4. Prepare Variables
$input_symptoms = $data['input_symptoms'] ?? '';
$age = $data['age'] ?? null;
$gender = $data['gender'] ?? null;
$height = $data['height_cm'] ?? null;
$weight = $data['weight_kg'] ?? null;
$bp = $data['blood_pressure'] ?? null;
$urgency = $data['urgency_level'] ?? 'Normal';
$results_json = json_encode($data['results'] ?? []);

logDebug("Attempting save for User $user_id. Symptoms: " . substr($input_symptoms, 0, 20) . "...");

// 5. Insert Query
try {
    $sql = "INSERT INTO symptom_analysis_history 
            (user_id, input_symptoms, age, gender, height_cm, weight_kg, blood_pressure, urgency_level, results_json) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $input_symptoms, $age, $gender, $height, $weight, $bp, $urgency, $results_json]);

    logDebug("Save Success!");
    echo json_encode(['success' => true, 'message' => 'Saved successfully']);
} catch (Exception $e) {
    logDebug("Insert Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
