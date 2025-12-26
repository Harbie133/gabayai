<?php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

// 1) Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 2) Read JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// 3) Basic validation
if (
    empty($data['doctorId']) ||
    empty($data['patient']['name']) ||
    empty($data['patient']['complaint'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// 4) IDs
$doctorId = (int)$data['doctorId'];          // from frontend (doctors.html)
$patientId = $_SESSION['user_id'] ?? null;   // users.id for logged-in patient
$patientIdStr = $patientId ? (string)$patientId : null;

// 5) Reuse existing consultation for same doctor+patient
if ($patientIdStr) {
    $check = $conn->prepare(
        "SELECT id
         FROM consultations
         WHERE doctor_id = ? AND patient_id = ?
         ORDER BY started_at DESC
         LIMIT 1"
    );
    if ($check) {
        $check->bind_param("is", $doctorId, $patientIdStr);
        $check->execute();
        $res = $check->get_result();
        $row = $res->fetch_assoc();
        $check->close();

        if ($row) {
            echo json_encode([
                'success'         => true,
                'consultation_id' => $row['id'],
                'message'         => 'Existing consultation reused'
            ]);
            exit;
        }
    }
}

// 6) Pull fields from payload
$p = $data['patient'];

$patientName   = $p['name'];
$patientPhone  = $p['phone']       ?? null;
$patientAge    = $p['age'] !== '' ? (int)$p['age'] : null;
$patientSex    = $p['sex']         ?? null;
$duration      = $p['duration']    ?? null;
$severity      = $p['severity']    ?? null;
$complaint     = $p['complaint'];
$feverNow      = $p['feverNow']    ?? null;
$temperature   = $p['temperature'] ?? null;

// store arrays as JSON text (for JSON columns)
$medicalHistory = json_encode($p['history']   ?? []);
$allergies      = json_encode($p['allergies'] ?? []);
$topics         = json_encode($p['topics']    ?? []);

$allergyOther = $p['allergyOther'] ?? null;
$consent      = !empty($p['consent']) ? 1 : 0;
$status       = 'ongoing'; // or 'pending'

// 7) INSERT – EXACT 18 columns (2–19) with NOW() for started_at
$sql = "INSERT INTO consultations (
            doctor_id,
            patient_id,
            patient_name,
            patient_phone,
            patient_age,
            patient_sex,
            duration,
            severity,
            complaint,
            fever_now,
            temperature,
            medical_history,
            allergies,
            topics,
            allergy_other,
            consent,
            status,
            started_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB error (prepare)',
        'error'   => $conn->error
    ]);
    exit;
}

// 8) Bind params – 17 values
$stmt->bind_param(
    "isssissssssssssis",
    $doctorId,       // 1  i
    $patientIdStr,   // 2  s (NULL kung walang user_id)
    $patientName,    // 3  s
    $patientPhone,   // 4  s
    $patientAge,     // 5  i
    $patientSex,     // 6  s
    $duration,       // 7  s
    $severity,       // 8  s
    $complaint,      // 9  s
    $feverNow,       // 10 s
    $temperature,    // 11 s
    $medicalHistory, // 12 s
    $allergies,      // 13 s
    $topics,         // 14 s
    $allergyOther,   // 15 s
    $consent,        // 16 i
    $status          // 17 s
);

// 9) Execute
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB error (execute)',
        'error'   => $stmt->error
    ]);
    $stmt->close();
    exit;
}

$consultationId = $stmt->insert_id;
$stmt->close();

// 10) Response
echo json_encode([
    'success'         => true,
    'consultation_id' => $consultationId,
    'message'         => 'New consultation created'
]);
