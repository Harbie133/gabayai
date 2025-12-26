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

// 4) Identify patient from session / payload
$patientId = $_SESSION['patient_id'] ?? ($_SESSION['user_id'] ?? null);
if (!$patientId && !empty($data['patient_id'])) {
    $patientId = $data['patient_id'];
}

// 5) If patient has pending/ongoing, reuse that consultation
if ($patientId) {
    $sql = "SELECT id
            FROM consultations
            WHERE patient_id = ?
              AND status IN ('pending','ongoing')
            ORDER BY started_at DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'DB error (prepare check)',
            'error'   => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("s", $patientId);
    $stmt->execute();
    $res      = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
        echo json_encode([
            'success'         => true,
            'consultation_id' => $existing['id'],
            'message'         => 'Existing active consultation'
        ]);
        exit;
    }
}

// 6) Build JSON fields
$historyJson   = json_encode($data['patient']['history']   ?? []);
$allergiesJson = json_encode($data['patient']['allergies'] ?? []);
$topicsJson    = json_encode($data['patient']['topics']    ?? []);

// 7) INSERT – match EXACT table (id auto + 18 cols)
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
        'message' => 'DB error (prepare insert)',
        'error'   => $conn->error
    ]);
    exit;
}

// 8) Values from payload
$doctorId      = (int)$data['doctorId'];
$patientIdStr  = $patientId !== null ? (string)$patientId : null;

$p             = $data['patient'];
$patientName   = $p['name'];
$patientPhone  = $p['phone']       ?? null;
$patientAge    = $p['age'] !== '' ? (int)$p['age'] : null;
$patientSex    = $p['sex']         ?? null;
$duration      = $p['duration']    ?? null;
$severity      = $p['severity']    ?? null;
$complaint     = $p['complaint'];
$feverNow      = $p['feverNow']    ?? null;
$temperature   = $p['temperature'] ?? null;
$allergyOther  = $p['allergyOther'] ?? null;
$consent       = !empty($p['consent']) ? 1 : 0;
$status        = 'ongoing';

// 9) Bind params – 17 vars, 17 types
$stmt->bind_param(
    "isssisssssssssssis",
    $doctorId,      // i
    $patientIdStr,  // s
    $patientName,   // s
    $patientPhone,  // s
    $patientAge,    // i
    $patientSex,    // s
    $duration,      // s
    $severity,      // s
    $complaint,     // s
    $feverNow,      // s
    $temperature,   // s
    $historyJson,   // s
    $allergiesJson, // s
    $topicsJson,    // s
    $allergyOther,  // s
    $consent,       // i
    $status         // s
);

// 10) Execute
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB error (execute insert)',
        'error'   => $stmt->error
    ]);
    $stmt->close();
    exit;
}

$consultationId = $stmt->insert_id;
$stmt->close();

// 11) Done
echo json_encode([
    'success'         => true,
    'consultation_id' => $consultationId,
    'message'         => 'New consultation created'
]);
