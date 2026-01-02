<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Get POST data
$title = $_POST['title'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? ''; // ✅ OPTIONAL
$specialization = $_POST['specialization'] ?? '';
$availability = $_POST['availability'] ?? 'Available';
$available_days = isset($_POST['available_days']) && is_array($_POST['available_days']) 
    ? implode(', ', $_POST['available_days']) 
    : '';
$available_hours = $_POST['available_hours'] ?? '';

// ✅ CRITICAL: Base SQL with online_status for patient side sync
$sql = "UPDATE doctor_profile SET 
        title = ?, full_name = ?, email = ?, specialization = ?, 
        availability = ?, online_status = ?, available_days = ?, available_hours = ?";
$params = [$title, $full_name, $email, $specialization, $availability, $availability, $available_days, $available_hours];
$types = "ssssssss"; // 8 strings

// ✅ Phone OPTIONAL - add only if provided
if (!empty($phone)) {
    $sql = "UPDATE doctor_profile SET title = ?, full_name = ?, email = ?, phone = ?, specialization = ?, availability = ?, online_status = ?, available_days = ?, available_hours = ?";
    $params = [$title, $full_name, $email, $phone, $specialization, $availability, $availability, $available_days, $available_hours];
    $types = "sssssssss"; // 9 strings
}

// ✅ Profile photo OPTIONAL
$photo_updated = false;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_exts) && $_FILES['profile_photo']['size'] <= 5*1024*1024) {
        $new_name = 'doctor_' . $doctor_id . '_' . time() . '.' . $file_ext;
        $target_dir = 'images/doctors/';
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_dir . $new_name)) {
            $sql .= ", profile_photo = ?";
            $params[] = $target_dir . $new_name;
            $types .= "s";
            $photo_updated = true;
        }
    }
}

// ✅ PASSWORD COMPLETELY OPTIONAL
if (!empty($_POST['new_password'])) {
    if (empty($_POST['current_password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password required to change password']);
        exit();
    }
    
    $check_sql = "SELECT user_password FROM doctor_profile WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($_POST['current_password'], $row['user_password'])) {
            $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql .= ", user_password = ?";
            $params[] = $new_hash;
            $types .= "s";
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }
    }
    $stmt->close();
}

// ✅ Always add timestamp and WHERE clause
$sql .= ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$params[] = $doctor_id;
$types .= "i";

// Prepare, bind, execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $response = ['success' => true, 'message' => 'Profile updated successfully!'];
    
    // Return new photo URL if uploaded
    if ($photo_updated) {
        $response['profile_photo'] = 'http://localhost/gabayai/' . $params[sizeof($params)-3];
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
