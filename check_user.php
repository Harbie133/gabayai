<?php
session_start();
require_once 'db.php';

echo "<h2>Session & Database Check</h2>";
echo "<pre>";

echo "=== SESSION INFO ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in with ID: " . $_SESSION['user_id'] . "\n\n";
    
    echo "=== CHECKING USER IN DATABASE ===\n";
    try {
        $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ User found in database:\n";
            echo "ID: " . $user['id'] . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Name: " . ($user['full_name'] ?? 'N/A') . "\n\n";
        } else {
            echo "❌ User ID " . $_SESSION['user_id'] . " not found in users table!\n\n";
        }
        
        echo "=== CHECKING MEDICAL INFO TABLE ===\n";
        $stmt = $pdo->query("DESCRIBE medical_info");
        echo "✅ medical_info table structure:\n";
        while ($row = $stmt->fetch()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
    } catch(PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ User is NOT logged in!\n";
    echo "You need to log in first.\n";
}

echo "</pre>";
?>
