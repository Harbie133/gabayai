<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Prescription Debug</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Prescription Debugging Tool</h1>

    <div class="box">
        <h2>📋 Current Session Info</h2>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <p class="success">✅ User is logged in</p>
            <ul>
                <li><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></li>
                <li><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'NOT SET'; ?></li>
                <li><strong>Email:</strong> <?php echo $_SESSION['email'] ?? 'NOT SET'; ?></li>
            </ul>
        <?php else: ?>
            <p class="error">❌ User is NOT logged in</p>
        <?php endif; ?>
    </div>

    <?php
    $conn = new mysqli("localhost", "root", "", "gabayai");
    if ($conn->connect_error) {
        die("<div class='box'><p class='error'>Database connection failed</p></div>");
    }
    ?>

    <div class="box">
        <h2>👥 All Users in Database</h2>
        <?php
        $result = $conn->query("SELECT id, username, email FROM users");
        echo "<table><tr><th>ID</th><th>Username</th><th>Email</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $highlight = (isset($_SESSION['user_id']) && $row['id'] == $_SESSION['user_id']) ? 'style="background: #d4edda;"' : '';
            echo "<tr $highlight>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        ?>
    </div>

    <div class="box">
        <h2>💊 All Prescriptions in Database</h2>
        <?php
        $result = $conn->query("SELECT p.*, u.username, d.full_name as doctor_name 
                                FROM prescriptions p
                                LEFT JOIN users u ON p.user_id = u.id
                                LEFT JOIN doctor_profile d ON p.doctor_id = d.id
                                ORDER BY p.prescription_id DESC");
        
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Patient (User ID)</th><th>Doctor</th><th>Diagnosis</th><th>Date</th><th>Status</th></tr>";
            while ($row = $result->fetch_assoc()) {
                $highlight = (isset($_SESSION['user_id']) && $row['user_id'] == $_SESSION['user_id']) ? 'style="background: #d4edda;"' : '';
                echo "<tr $highlight>";
                echo "<td>{$row['prescription_id']}</td>";
                echo "<td>{$row['username']} (ID: {$row['user_id']})</td>";
                echo "<td>{$row['doctor_name']}</td>";
                echo "<td>" . substr($row['diagnosis'], 0, 50) . "</td>";
                echo "<td>{$row['prescription_date']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p class='success'>✅ Total prescriptions: {$result->num_rows}</p>";
        } else {
            echo "<p class='warning'>⚠️ No prescriptions found in database</p>";
        }
        ?>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="box">
        <h2>🎯 Prescriptions for Current User (ID: <?php echo $_SESSION['user_id']; ?>)</h2>
        <?php
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT p.*, d.full_name as doctor_name 
                                FROM prescriptions p
                                LEFT JOIN doctor_profile d ON p.doctor_id = d.id
                                WHERE p.user_id = ?
                                ORDER BY p.prescription_date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p class='success'>✅ Found {$result->num_rows} prescription(s) for this user</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Doctor</th><th>Diagnosis</th><th>Date</th><th>Status</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['prescription_id']}</td>";
                echo "<td>{$row['doctor_name']}</td>";
                echo "<td>{$row['diagnosis']}</td>";
                echo "<td>{$row['prescription_date']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No prescriptions found for user ID {$user_id}</p>";
            echo "<p class='warning'>⚠️ This means either:</p>";
            echo "<ul>";
            echo "<li>The doctor hasn't created prescriptions for this user yet</li>";
            echo "<li>The user_id in prescriptions doesn't match your session user_id</li>";
            echo "</ul>";
        }
        ?>
    </div>
    <?php endif; ?>

    <div class="box">
        <h2>🔧 Quick Actions</h2>
        <p><a href="patient_prescriptions.html" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Go to Patient Prescriptions Page</a></p>
        <p><a href="userlogin.html" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Login Page</a></p>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
