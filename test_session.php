<?php
session_start();
echo "<h2>Session Debug</h2>";
echo "Doctor ID: " . ($_SESSION['doctor_id'] ?? '<span style=color:red>NOT SET</span>') . "<br>";
echo "All Session: <pre>" . print_r($_SESSION, true) . "</pre>";
?>
