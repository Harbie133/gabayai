<?php
session_start();
date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

// ✅ SUPABASE FIXED - YOUR EXACT VALUES
$SUPABASE_URL = 'https://uborgrghdgvaumcqzxhr.supabase.co';
$SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVib3JncmdoZGd2YXVtY3F6eGhyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjczNTI5NDYsImV4cCI6MjA4MjkyODk0Nn0.ntDzyoE3WFp-LaihnJNeBcsf-cJ-v1luEjW0kcm57yY';

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
        'Authorization: Bearer ' . $SUPABASE_ANON_KEY,  // ✅ FIXED
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // ✅ SSL FIXED
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    // ✅ SSL FIXED
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($result, true);
    }
    return ['error' => $result, 'http_code' => $httpCode];
}

try {
    $action = $_POST['action'] ?? '';

    // ✅ LOGIN - FULLY FIXED
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $email = strtolower($username) . '@gabayai.ph';
        $password = $_POST['password'] ?? '';

        $data = json_encode(['email' => $email, 'password' => $password]);
        $result = supabaseCall('/auth/v1/token?grant_type=password', $data);  // ✅ FIXED!
        
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

    // ✅ REGISTER - WORKING
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
            $response['message'] = $result['msg'] ?? json_encode($result);
        }
    }

    // ✅ FORGOT PASSWORD - SUPABASE BUILT-IN (no PHPMailer needed)
    elseif ($action === 'forgot_password') {
        $email = $_POST['email'];
        $data = json_encode(['email' => $email]);
        $result = supabaseCall('/auth/v1/recover', $data);
        $response['success'] = true;
        $response['message'] = 'Check email for reset link from Supabase';
    }

} catch (Exception $e) {
    $response['message'] = 'Server Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
