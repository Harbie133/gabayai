<?php
session_start();
date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

// SUPABASE - DELETE db.php
$SUPABASE_URL = 'https://supabase.com/dashboard/project/uborgrghdgvaumcqzxhr/settings/api-keys';
$SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVib3JncmdoZGd2YXVtY3F6eGhyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjczNTI5NDYsImV4cCI6MjA4MjkyODk0Nn0.ntDzyoE3WFp-LaihnJNeBcsf-cJ-v1luEjW0kcm57yY'; // from eye icon

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => 'An error occurred'];

function supabaseCall($endpoint, $data) {
    global $SUPABASE_URL, $SUPABASE_ANON_KEY;
    $ch = curl_init($SUPABASE_URL . $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($result, true);
    }
    return ['error' => $result];
}

try {
    $action = $_POST['action'] ?? '';

    // === LOGIN ===
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $email = strtolower($username) . '@gabayai.ph'; // username → email
        $password = $_POST['password'] ?? '';

        $data = json_encode(['email' => $email, 'password' => $password]);
        $result = supabaseCall('/auth/v1/token', $data);  // Fixed endpoint
        
        if (isset($result['access_token'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['token'] = $result['access_token'];
            $response['success'] = true;
            $response['message'] = 'Login successful';
        } else {
            $response['message'] = $result['error_description'] ?? 'Invalid credentials';
        }
    }

    // === REGISTER ===
    elseif ($action === 'register') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $data = json_encode(['email' => $email, 'password' => $password]);
        $result = supabaseCall('/auth/v1/signup', $data);
        
        if (isset($result['id'])) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $response['success'] = true;
            $response['message'] = 'Registration successful';
        } else {
            $response['message'] = $result['msg'] ?? 'Registration failed';
        }
    }

    // === FORGOT PASSWORD (Keep PHPMailer + Supabase check)
    elseif ($action === 'forgot_password') {
        $email = $_POST['email'];
        
        // Check if exists sa Supabase
        $data = json_encode(['email' => $email]);
        $check = supabaseCall('/auth/v1/signup', $data); // misuses signup for check
        
        $mail = new PHPMailer(true);
        try {
            // Your exact PHPMailer config (unchanged)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'harbienaga@gmail.com'; 
            $mail->Password = 'wacx eqwu yopp rxoa'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom($mail->Username, 'GabayAI Support');
            $mail->addAddress($email);
            $link = "https://your-vercel-app.vercel.app/reset_password.php?token=" . bin2hex(random_bytes(50)); // Update URL
            
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Request';
            $mail->Body = "
                <h3>Password Reset Request</h3>
                <p>Click <a href='$link' style='background:#764ba2; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
            ";
            $mail->send();
            $response['success'] = true;
            $response['message'] = 'Reset link sent!';
        } catch (Exception $e) {
            $response['message'] = 'Mailer Error: ' . $mail->ErrorInfo;
        }
    }

} catch (Exception $e) {
    $response['message'] = 'Server Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
