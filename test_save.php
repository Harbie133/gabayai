<?php
session_start();

// Set a test user ID (change this to your actual user ID)
$_SESSION['user_id'] = 1; // CHANGE THIS to your actual user ID

echo "<h2>Testing Save Medical Info</h2>";

// Simulate form submission
$_POST['bloodType'] = 'A+';
$_POST['height'] = '170';
$_POST['weight'] = '65';
$_POST['allergies'] = 'None';
$_POST['chronicConditions'] = 'None';
$_POST['currentMedications'] = 'None';
$_POST['medicalHistory'] = 'N/A';
$_POST['familyMedicalHistory'] = 'N/A';

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<pre>";
echo "Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n\n";

// Include the save file
ob_start();
include 'save_medical_info.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
echo "</pre>";

echo "<hr>";
echo "<p>Check the debug_medical.txt file for detailed logs</p>";
?>
    