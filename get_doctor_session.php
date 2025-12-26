<?php
/**
 * Doctor Session Validation
 * Checks if doctor is logged in and fetches profile information
 */

session_start();
header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (isset($_SESSION['doctor_id']) && isset($_SESSION['doctor_username'])) {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gabayai";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        // If DB fails, still return session data
        echo json_encode([
            'logged_in' => true,
            'doctor_id' => $_SESSION['doctor_id'],
            'username' => $_SESSION['doctor_username'],
            'full_name' => 'Dr. ' . $_SESSION['doctor_username']
        ]);
        exit();
    }
    
    $conn->set_charset("utf8mb4");
    $doctor_id = $_SESSION['doctor_id'];
    
    // Fetch doctor's full name and title from database
    $sql = "SELECT title, full_name, specialization FROM doctor_profile WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
        
        // Build display name
        if (!empty($doctor['full_name'])) {
            $title = !empty($doctor['title']) ? $doctor['title'] : 'Dr.';
            $display_name = $title . ' ' . $doctor['full_name'];
        } else {
            $display_name = 'Dr. ' . $_SESSION['doctor_username'];
        }
        
        echo json_encode([
            'logged_in' => true,
            'doctor_id' => $_SESSION['doctor_id'],
            'username' => $_SESSION['doctor_username'],
            'full_name' => $display_name,
            'title' => $doctor['title'] ?? 'Dr.',
            'specialization' => $doctor['specialization'] ?? '',
            'name_only' => $doctor['full_name'] ?? $_SESSION['doctor_username']
        ]);
    } else {
        echo json_encode([
            'logged_in' => true,
            'doctor_id' => $_SESSION['doctor_id'],
            'username' => $_SESSION['doctor_username'],
            'full_name' => 'Dr. ' . $_SESSION['doctor_username'],
            'name_only' => $_SESSION['doctor_username']
        ]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>
