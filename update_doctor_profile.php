<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");
$doctor_id = $_SESSION['doctor_id'];

// Get form data
$title = $_POST['title'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$specialization = $_POST['specialization'] ?? '';
$experience = $_POST['experience'] ?? '';
$bio = $_POST['bio'] ?? '';
$hospital = $_POST['hospital'] ?? '';
$degree = $_POST['degree'] ?? '';
$university = $_POST['university'] ?? '';
$graduation_year = $_POST['graduation_year'] ?? null;
$expertise = $_POST['expertise'] ?? '';

// Handle availability fields
$availability = $_POST['availability'] ?? 'Available';

// Handle available days (checkboxes to comma-separated string)
$available_days = '';
if (isset($_POST['available_days']) && is_array($_POST['available_days'])) {
    $available_days = implode(', ', $_POST['available_days']);
} else {
    $available_days = 'Not specified';
}

// Handle available hours (1-hour slots from 7 AM to 4 PM)
$available_hours = $_POST['available_hours'] ?? '9:00 AM - 10:00 AM';

// Handle languages (multiple select)
$languages = '';
if (isset($_POST['languages']) && is_array($_POST['languages'])) {
    $languages = implode(', ', $_POST['languages']);
}

// Handle profile photo upload
$profile_photo = null;
$photo_updated = false;

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_photo'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (in_array($file_type, $allowed_types)) {
        // Validate file size (max 5MB)
        if ($file['size'] <= 5 * 1024 * 1024) {
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'doctor_' . $doctor_id . '_' . time() . '.' . $file_extension;
            
            // Set upload directory
            $upload_dir = __DIR__ . '/images/doctors/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $target_file = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $profile_photo = $new_filename;
                $photo_updated = true;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File size too large (max 5MB)']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed']);
        exit();
    }
}

// Handle password change
$password_updated = false;
if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $sql = "SELECT password FROM doctor_profile WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (password_verify($current_password, $row['password'])) {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE doctor_profile SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $doctor_id);
        $stmt->execute();
        $password_updated = true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit();
    }
}

// Build UPDATE query
if ($photo_updated) {
    $sql = "UPDATE doctor_profile SET 
        title = ?, full_name = ?, email = ?, phone = ?, specialization = ?, experience = ?, bio = ?,
        hospital = ?, languages = ?, degree = ?, university = ?, graduation_year = ?, expertise = ?,
        profile_photo = ?, availability = ?, available_days = ?, available_hours = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssssssi",
        $title, $full_name, $email, $phone, $specialization, $experience, $bio,
        $hospital, $languages, $degree, $university, $graduation_year, $expertise,
        $profile_photo, $availability, $available_days, $available_hours, $doctor_id
    );
} else {
    $sql = "UPDATE doctor_profile SET 
        title = ?, full_name = ?, email = ?, phone = ?, specialization = ?, experience = ?, bio = ?,
        hospital = ?, languages = ?, degree = ?, university = ?, graduation_year = ?, expertise = ?,
        availability = ?, available_days = ?, available_hours = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssi",
        $title, $full_name, $email, $phone, $specialization, $experience, $bio,
        $hospital, $languages, $degree, $university, $graduation_year, $expertise,
        $availability, $available_days, $available_hours, $doctor_id
    );
}

if ($stmt->execute()) {
    $response = [
        'success' => true,
        'message' => 'Profile updated successfully'
    ];
    
    if ($photo_updated) {
        $response['profile_photo'] = 'images/doctors/' . $profile_photo;
    }
    
    if ($password_updated) {
        $response['message'] .= ' and password changed';
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
