<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');

// Database connection
$conn = new mysqli("localhost", "root", "", "gabayai");

if ($conn->connect_error) {
    echo "Error: Database connection failed";
    exit();
}

$conn->set_charset("utf8mb4");

// Check if user/patient is logged in
$patient_id = null;
if (isset($_SESSION['user_id'])) {
    $patient_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
} elseif (isset($_SESSION['id'])) {
    $patient_id = $_SESSION['id'];
}

// Get form data
$doctor_id = isset($_POST['doctor']) ? intval($_POST['doctor']) : 0;
$full_name = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
$contact_no = isset($_POST['contactNo']) ? trim($_POST['contactNo']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Validation
if ($doctor_id == 0 || empty($full_name) || empty($contact_no) || empty($date) || empty($time) || empty($reason)) {
    echo "Error: All required fields must be filled";
    exit();
}

// Validate doctor exists - checking against your exact table
$check_doctor = "SELECT id, full_name FROM doctor_profile WHERE id = ? AND availability = 'Available'";
$check_stmt = $conn->prepare($check_doctor);

if (!$check_stmt) {
    echo "Error: " . $conn->error;
    exit();
}

$check_stmt->bind_param("i", $doctor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo "Error: Doctor not found or not available (ID: " . $doctor_id . ")";
    exit();
}

$doctor_data = $check_result->fetch_assoc();
$check_stmt->close();

// Validate date (weekday only)
$date_obj = new DateTime($date);
$day_of_week = $date_obj->format('N');
if ($day_of_week >= 6) {
    echo "Error: Appointments only on weekdays";
    exit();
}

// Validate time
$time_hour = intval(substr($time, 0, 2));
if ($time_hour < 7 || $time_hour > 17) {
    echo "Error: Time must be between 07:00 and 17:00";
    exit();
}

// Check conflicts
$conflict_check = "SELECT appointment_id FROM appointments 
                   WHERE doctor_id = ? 
                   AND appointment_date = ? 
                   AND appointment_time = ? 
                   AND status != 'cancelled'";
$conflict_stmt = $conn->prepare($conflict_check);
$conflict_stmt->bind_param("iss", $doctor_id, $date, $time);
$conflict_stmt->execute();
$conflict_result = $conflict_stmt->get_result();

if ($conflict_result->num_rows > 0) {
    echo "Error: Time slot already booked";
    $conflict_stmt->close();
    exit();
}
$conflict_stmt->close();

// Prepare notes
$notes = "Patient: " . $full_name . " | Contact: " . $contact_no;
if (!empty($email)) {
    $notes .= " | Email: " . $email;
}

// Insert appointment - matching your exact database structure
$sql = "INSERT INTO appointments 
        (patient_id, doctor_id, appointment_date, appointment_time, consultation_type, symptoms, status, notes) 
        VALUES (?, ?, ?, ?, 'in_person', ?, 'pending', ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Error: " . $conn->error;
    exit();
}

$stmt->bind_param("iissss", $patient_id, $doctor_id, $date, $time, $reason, $notes);

if ($stmt->execute()) {
    $formatted_date = $date_obj->format('F j, Y');
    $formatted_time = date('g:i A', strtotime($time));
    echo "Success! Appointment with Dr. " . $doctor_data['full_name'] . " scheduled for " . $formatted_date . " at " . $formatted_time . ".";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
