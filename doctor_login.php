<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = trim($_POST['username']);
    $input_password = $_POST['password'];
    
    // ✅ YOUR DB: password column
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM doctor_profile WHERE username = ?");
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $db_password, $full_name, $email);
        $stmt->fetch();
        
        // ✅ FIXED: Use $db_password consistently
        if (password_verify($input_password, $db_password) || $input_password === $db_password) {
            $_SESSION['doctor_logged_in'] = true;
            $_SESSION['doctor_id'] = $id;
            $_SESSION['doctor_login_id'] = $id;
            $_SESSION['doctor_username'] = $username;
            $_SESSION['doctor_name'] = $full_name ?: $username;
            $_SESSION['doctor_email'] = $email ?: $username . '@doctor.com';
            $_SESSION['doctor_login_time'] = time();
            $_SESSION['logged_in'] = true;
            
            // ✅ AUTO ONLINE STATUS
            $update_stmt = $conn->prepare("UPDATE doctor_profile SET online_status = 'Available' WHERE id = ?");
            $update_stmt->bind_param("i", $id);
            $update_stmt->execute();
            $update_stmt->close();
            
            header("Location: doctor_dashboard.html");
            exit();
        } else {
            echo "<script>alert('Invalid username or password!'); window.location.href='doctor_login.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid username or password!'); window.location.href='doctor_login.html';</script>";
    }
    
    $stmt->close();
}

$conn->close();
?>
