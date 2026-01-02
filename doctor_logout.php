<?php
session_start();
require_once 'db.php'; // Palitan ng 'db.php' kung yun ang gamit mo

$doctorId = isset($_SESSION['doctor_id']) ? (int)$_SESSION['doctor_id'] : 0;

if ($doctorId > 0) {
    // ✅ SET OFFLINE status bago umalis
    $stmt = $conn->prepare("UPDATE doctor_profile SET online_status = 'Not Available' WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Clear specific session variables first (good practice)
unset($_SESSION['doctor_logged_in']);
unset($_SESSION['doctor_id']);
unset($_SESSION['doctor_username']);

// Completely destroy the session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect
header("Location: doctor_login.html");
exit();
?>
