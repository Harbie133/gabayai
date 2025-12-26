<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gabayai";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle different actions
$action = $_POST['action'] ?? '';

switch($action) {
    case 'send_reset_code':
        sendResetCode($conn);
        break;
    case 'verify_code':
        verifyCode($conn);
        break;
    case 'reset_password':
        resetPassword($conn);
        break;
}

// Function to send reset code via email
function sendResetCode($conn) {
    $identifier = $_POST['identifier'] ?? '';
    
    // Check if user exists (by username or email)
    $stmt = $conn->prepare("SELECT id, email, username FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    $user = $result->fetch_assoc();
    $email = $user['email'];
    $userId = $user['id'];
    
    // Generate 6-digit verification code
    $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
    
    // Store code in database with expiration (15 minutes)
    $expiryTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete any existing reset codes for this user
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Insert new reset code
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, reset_code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $verificationCode, $expiryTime);
    $stmt->execute();
    
    // Send email with verification code using PHP mail()
    if (sendSimpleEmail($email, $verificationCode, $user['username'])) {
        $_SESSION['reset_user_id'] = $userId;
        echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    }
}

// Function to verify the code
function verifyCode($conn) {
    $code = $_POST['code'] ?? '';
    $userId = $_SESSION['reset_user_id'] ?? 0;
    
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE user_id = ? AND reset_code = ? AND expires_at > NOW()");
    $stmt->bind_param("is", $userId, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['code_verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Code verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired code']);
    }
}

// Function to reset password
function resetPassword($conn) {
    if (!isset($_SESSION['code_verified']) || !$_SESSION['code_verified']) {
        echo json_encode(['success' => false, 'message' => 'Code not verified']);
        return;
    }
    
    $newPassword = $_POST['password'] ?? '';
    $userId = $_SESSION['reset_user_id'] ?? 0;
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        // Delete the reset code
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Clear session
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['code_verified']);
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
    }
}

// Simple email function using PHP mail()
function sendSimpleEmail($email, $verificationCode, $username) {
    $subject = "Password Reset - GabayAI";
    
    // HTML email content
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .code-box { background: #f8f9ff; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 25px 0; border-radius: 8px; }
            .code { font-size: 32px; font-weight: bold; color: #333; font-family: monospace; letter-spacing: 4px; }
            .footer { border-top: 1px solid #ddd; margin-top: 30px; padding-top: 20px; text-align: center; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0; font-size: 28px;'>🏥 GabayAI</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px;'>Password Reset Request</p>
            </div>
            
            <div class='content'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello, {$username}!</h2>
                <p style='color: #666; line-height: 1.6; margin-bottom: 25px;'>
                    We received a request to reset your password. Use the verification code below to proceed:
                </p>
                
                <div class='code-box'>
                    <h3 style='color: #667eea; margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>Verification Code</h3>
                    <div class='code'>{$verificationCode}</div>
                </div>
                
                <p style='color: #666; line-height: 1.6; margin-bottom: 20px;'>
                    This code will expire in <strong>15 minutes</strong> for security reasons.
                </p>
                
                <p style='color: #999; font-size: 14px; line-height: 1.6;'>
                    If you didn't request this password reset, please ignore this email or contact our support team.
                </p>
                
                <div class='footer'>
                    © 2025 GabayAI - Pamantasan ng Lungsod ng Muntinlupa<br>
                    This is an automated message, please do not reply.
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: GabayAI Support <noreply@gabayai.com>',
        'Reply-To: noreply@gabayai.com',
        'X-Mailer: PHP/' . phpversion()
    );
    
    // For development/testing - log the code instead of sending email
    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        // Development mode - just log the code
        error_log("PASSWORD RESET CODE FOR $email: $verificationCode");
        file_put_contents('reset_codes.log', date('Y-m-d H:i:s') . " - Email: $email, Code: $verificationCode\n", FILE_APPEND);
        return true; // Simulate successful email sending
    }
    
    // Production mode - actually send email
    return mail($email, $subject, $message, implode("\r\n", $headers));
}
?>
