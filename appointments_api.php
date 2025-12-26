<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'gabayai';
$username = 'root';
$password = '';

// Connect to database
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => true]);
    exit();
}

// Get doctor_id from session
$doctor_id = $_SESSION['doctor_id'] ?? $_SESSION['user_id'] ?? null;

if (!$doctor_id) {
    echo json_encode(['success' => false, 'error' => 'Doctor ID not found', 'redirect' => true]);
    exit();
}

// Get action
$action = $_GET['action'] ?? '';

// FETCH APPOINTMENTS
if ($action === 'fetch') {
    $filter = $_GET['filter'] ?? 'all';
    
    // Base query
    $sql = "SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.symptoms,
        a.status,
        a.consultation_type,
        p.id as patient_id,
        p.full_name as patient_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ?";
    
    // Add filters
    if ($filter === 'today') {
        $sql .= " AND DATE(a.appointment_date) = CURDATE()";
    } elseif ($filter === 'pending') {
        $sql .= " AND a.status = 'pending'";
    } elseif ($filter === 'confirmed') {
        $sql .= " AND a.status = 'confirmed'";
    } elseif ($filter === 'completed') {
        $sql .= " AND a.status = 'completed'";
    }
    
    $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $date = new DateTime($row['appointment_date']);
            $time = new DateTime($row['appointment_time']);
            
            $appointments[] = [
                'id' => $row['appointment_id'],
                'patient_name' => $row['patient_name'] ?? 'Unknown Patient',
                'patient_id' => 'PAT' . str_pad($row['patient_id'], 6, '0', STR_PAD_LEFT),
                'date' => $date->format('M d, Y'),
                'time' => $time->format('g:i A'),
                'reason' => $row['symptoms'] ?? 'General consultation',
                'status' => $row['status']
            ];
        }
        
        // Get stats
        $stats_sql = "SELECT 
            COUNT(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 END) as today_count,
            COUNT(CASE WHEN DATE(appointment_date) > CURDATE() AND status NOT IN ('completed', 'cancelled') THEN 1 END) as upcoming_count,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
        FROM appointments 
        WHERE doctor_id = ?";
        
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bind_param("i", $doctor_id);
        $stats_stmt->execute();
        $stats = $stats_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'appointments' => $appointments,
            'stats' => $stats
        ]);
        
        $stmt->close();
        $stats_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// UPDATE APPOINTMENT STATUS
elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $data['appointment_id'] ?? null;
    $new_status = $data['status'] ?? null;
    
    if (!$appointment_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    try {
        $sql = "UPDATE appointments 
                SET status = ? 
                WHERE appointment_id = ? AND doctor_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Appointment ' . $new_status . ' successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Appointment not found or no changes made'
            ]);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>
