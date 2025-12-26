<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Session Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .success { background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h2>Doctor Appointments - Working Version</h2>
    
    <?php
    $doctor_id = $_SESSION['doctor_id'] ?? 1;
    echo "<div class='success'>Doctor ID: $doctor_id</div>";
    
    $conn = new mysqli("localhost", "root", "", "gabayai");
    
    if ($conn->connect_error) {
        die("<div class='error'>Connection failed</div>");
    }
    
    echo "<div class='success'>✓ Connected to database</div>";
    
    // Get appointments - SIMPLE query
    $sql = "SELECT * FROM appointments WHERE doctor_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div class='success'><strong>Found " . $result->num_rows . " appointment(s)</strong></div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Patient Name</th><th>Date</th><th>Time</th><th>Status</th><th>Type</th><th>Symptoms</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            // Extract patient name from notes
            $patient_name = 'Guest Patient';
            if (!empty($row['notes'])) {
                if (preg_match('/Patient:\s*([^|]+)/', $row['notes'], $matches)) {
                    $patient_name = trim($matches[1]);
                }
            }
            
            echo "<tr>";
            echo "<td>" . $row['appointment_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($patient_name) . "</strong></td>";
            echo "<td>" . $row['appointment_date'] . "</td>";
            echo "<td>" . $row['appointment_time'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['consultation_type'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['symptoms'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='info'><strong>Notes field format:</strong><br>";
        echo "<code>" . htmlspecialchars($row['notes'] ?? 'No notes') . "</code></div>";
    } else {
        echo "<div class='error'>No appointments found for doctor_id = $doctor_id</div>";
    }
    
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
