<?php
session_start();
require_once 'db.php'; // yung code na sinend mo (creates $conn)

// Debug quick (optional): uncomment to verify it runs
// error_log("doctor_logout hit. doctor_id=" . ($_SESSION['doctor_id'] ?? 'NULL'));

$doctorId = isset($_SESSION['doctor_id']) ? (int)$_SESSION['doctor_id'] : 0;

if ($doctorId > 0) {
    $stmt = $conn->prepare("DELETE FROM doctor_presence WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->close();
}

// Clear only doctor session variables (same as yours)
unset($_SESSION['doctor_logged_in']);
unset($_SESSION['doctor_id']);
unset($_SESSION['doctor_login_id']);
unset($_SESSION['doctor_username']);
unset($_SESSION['doctor_name']);
unset($_SESSION['doctor_email']);
unset($_SESSION['doctor_created_at']);
unset($_SESSION['doctor_login_time']);
unset($_SESSION['logged_in']);

// (Optional) close DB
$conn->close();

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect
header("Location: doctor_login.html");
exit();
?>
