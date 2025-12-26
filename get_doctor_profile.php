<?php
session_start();
header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'logged_in' => false, 'message' => 'Not logged in']);
    exit();
}

// Database connection
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

// Fetch doctor profile with availability fields
$sql = "SELECT 
    id,
    username,
    title,
    full_name,
    email,
    phone,
    specialization,
    experience,
    bio,
    hospital,
    languages,
    degree,
    university,
    graduation_year,
    expertise,
    profile_photo,
    availability,
    available_days,
    available_hours
FROM doctor_profile 
WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $doctor = $result->fetch_assoc();
    
    // Handle profile photo path
    if (!empty($doctor['profile_photo'])) {
        $photo = basename($doctor['profile_photo']);
        $doctor['profile_photo'] = 'images/doctors/' . $photo;
    } else {
        $doctor['profile_photo'] = 'https://via.placeholder.com/150';
    }
    
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'id' => $doctor['id'],
        'username' => $doctor['username'],
        'title' => $doctor['title'],
        'full_name' => $doctor['full_name'],
        'email' => $doctor['email'],
        'phone' => $doctor['phone'],
        'specialization' => $doctor['specialization'],
        'experience' => $doctor['experience'],
        'bio' => $doctor['bio'],
        'hospital' => $doctor['hospital'],
        'languages' => $doctor['languages'],
        'degree' => $doctor['degree'],
        'university' => $doctor['university'],
        'graduation_year' => $doctor['graduation_year'],
        'expertise' => $doctor['expertise'],
        'profile_photo' => $doctor['profile_photo'],
        'availability' => $doctor['availability'],
        'available_days' => $doctor['available_days'],
        'available_hours' => $doctor['available_hours']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Doctor not found']);
}

$stmt->close();
$conn->close();
?>
