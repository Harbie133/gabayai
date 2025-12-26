<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");

// Fetch ONLY doctors who have completed their profile (have full_name and specialization)
$sql = "SELECT 
    id,
    CONCAT(COALESCE(title, 'Dr.'), ' ', full_name) as name,
    full_name,
    title,
    specialization as specialty,
    email,
    phone,
    experience,
    bio,
    hospital,
    languages,
    degree,
    university,
    graduation_year,
    expertise,
    profile_photo as image,
    COALESCE(availability, 'Available') as availability,
    COALESCE(available_days, 'Not specified') as available_days,
    COALESCE(available_hours, 'Not specified') as available_hours
FROM doctor_profile 
WHERE full_name IS NOT NULL 
  AND full_name != '' 
  AND specialization IS NOT NULL 
  AND specialization != ''
ORDER BY full_name ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit();
}

$doctors = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Handle profile photo path
        if (!empty($row['image'])) {
            $image = basename($row['image']);
            if (strpos($row['image'], 'images/') !== 0 && 
                strpos($row['image'], 'http') !== 0) {
                $row['image'] = 'images/doctors/' . $image;
            }
        } else {
            $row['image'] = 'images/doctors/default.jpg';
        }
        
        $doctors[] = $row;
    }
}

echo json_encode($doctors);

$conn->close();
?>
