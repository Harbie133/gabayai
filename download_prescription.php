<?php
session_start();

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Not authenticated');
}

$prescription_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "gabayai");

// Get prescription details
$sql = "SELECT p.*, d.full_name as doctor_name, d.specialty, 
               u.username, u.first_name, u.last_name
        FROM prescriptions p
        LEFT JOIN doctor_profile d ON p.doctor_id = d.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.prescription_id = ? AND p.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $prescription_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Prescription not found');
}

$prescription = $result->fetch_assoc();

// Generate simple HTML for printing/PDF
?>
<!DOCTYPE html>
<html>
<head>
    <title>Prescription #<?php echo $prescription['prescription_id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background: #667eea;
            color: white;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
        }
        @media print {
            body {
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 GabayAI Healthcare System</h1>
        <h2>Medical Prescription</h2>
        <p>Prescription #<?php echo $prescription['prescription_id']; ?></p>
    </div>

    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="content">
            <div class="info-grid">
                <div class="label">Name:</div>
                <div><?php echo $prescription['first_name'] . ' ' . $prescription['last_name']; ?></div>
                
                <div class="label">Username:</div>
                <div><?php echo $prescription['username']; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Doctor Information</div>
        <div class="content">
            <div class="info-grid">
                <div class="label">Doctor:</div>
                <div><?php echo $prescription['doctor_name'] ?? 'Not specified'; ?></div>
                
                <div class="label">Specialty:</div>
                <div><?php echo $prescription['specialty'] ?? 'Not specified'; ?></div>
                
                <div class="label">Date:</div>
                <div><?php echo date('F d, Y h:i A', strtotime($prescription['prescription_date'])); ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Diagnosis</div>
        <div class="content">
            <?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?>
        </div>
    </div>

    <?php if ($prescription['additional_notes']): ?>
    <div class="section">
        <div class="section-title">Additional Notes</div>
        <div class="content">
            <?php echo nl2br(htmlspecialchars($prescription['additional_notes'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Status</div>
        <div class="content">
            <strong><?php echo $prescription['status']; ?></strong>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 40px;">
        <button onclick="window.print()" style="background: #667eea; color: white; border: none; padding: 15px 30px; border-radius: 5px; cursor: pointer; font-size: 16px;">
            Print / Save as PDF
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 15px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
<?php
$conn->close();
?>
