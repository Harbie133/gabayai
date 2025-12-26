<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];

// Get total patients
$total_patients_query = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?";
$stmt = $conn->prepare($total_patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$total_patients = $result->fetch_assoc()['count'];

// Get new patients this month
$new_patients_query = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments 
                       WHERE doctor_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                       AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($new_patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$new_patients = $result->fetch_assoc()['count'];

// Get active cases (Pending or Confirmed appointments)
$active_cases_query = "SELECT COUNT(*) as count FROM appointments 
                       WHERE doctor_id = ? AND status IN ('Pending', 'Confirmed')";
$stmt = $conn->prepare($active_cases_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$active_cases = $result->fetch_assoc()['count'];

// Get pending reports (appointments that need follow-up)
$pending_reports_query = "SELECT COUNT(*) as count FROM appointments 
                          WHERE doctor_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($pending_reports_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_reports = $result->fetch_assoc()['count'];

// Get total consultations
$total_consultations_query = "SELECT COUNT(*) as count FROM appointments 
                              WHERE doctor_id = ? AND status = 'Completed'";
$stmt = $conn->prepare($total_consultations_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$total_consultations = $result->fetch_assoc()['count'];

// Get upcoming appointments
$upcoming_appointments_query = "SELECT COUNT(*) as count FROM appointments 
                                WHERE doctor_id = ? AND appointment_date >= CURRENT_DATE() 
                                AND status IN ('Pending', 'Confirmed')";
$stmt = $conn->prepare($upcoming_appointments_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_appointments = $result->fetch_assoc()['count'];

// Get unread messages (placeholder - you'll need to create messages table)
$unread_messages = 0;

echo json_encode([
    'success' => true,
    'total_patients' => $total_patients,
    'new_patients' => $new_patients,
    'active_cases' => $active_cases,
    'pending_reports' => $pending_reports,
    'total_consultations' => $total_consultations,
    'upcoming_appointments' => $upcoming_appointments,
    'unread_messages' => $unread_messages
]);

$stmt->close();
$conn->close();
?>
