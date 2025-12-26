<?php
session_start();
require_once 'db.php'; // Your database connection file

// Get search parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterGender = isset($_GET['gender']) ? $_GET['gender'] : '';
$filterRegion = isset($_GET['region']) ? $_GET['region'] : '';
$filterCity = isset($_GET['city']) ? $_GET['city'] : '';

// Build the SQL query with dynamic WHERE clauses
$sql = "SELECT id, username, CONCAT(first_name, ' ', IFNULL(middle_initial, ''), ' ', last_name) as full_name, 
        email, phone, date_of_birth, gender, city, region_name, barangay_name, is_active 
        FROM users WHERE 1=1";

$params = [];
$types = '';

// Add search condition (searches across multiple fields)
if (!empty($searchTerm)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%{$searchTerm}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= 'sssss';
}

// Add gender filter
if (!empty($filterGender)) {
    $sql .= " AND gender = ?";
    $params[] = $filterGender;
    $types .= 's';
}

// Add region filter
if (!empty($filterRegion)) {
    $sql .= " AND region_name LIKE ?";
    $params[] = "%{$filterRegion}%";
    $types .= 's';
}

// Add city filter
if (!empty($filterCity)) {
    $sql .= " AND city_name LIKE ?";
    $params[] = "%{$filterCity}%";
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

echo json_encode(['success' => true, 'data' => $patients]);

$stmt->close();
$conn->close();
?>
