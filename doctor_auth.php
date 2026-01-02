<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set Timezone to PH
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once 'db.php'; // Check kung db.php o db_connection.php

// Import PHPMailer
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? $input['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid request'];

try {

    // === REGISTER ===
    if ($action === 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id FROM doctor_profile WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = 'Username or Email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO doctor_profile (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please login.';
            } else {
                $response['message'] = 'Database error: ' . $stmt->error;
            }
        }
        $stmt->close();

    // === LOGIN ===
    } elseif ($action === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password FROM doctor_profile WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['doctor_id'] = $row['id'];
                $_SESSION['doctor_username'] = $row['username'];
                $_SESSION['doctor_logged_in'] = true;
                $response['success'] = true;
                $response['message'] = 'Login successful!';
            } else {
                $response['message'] = 'Invalid password.';
            }
        } else {
            $response['message'] = 'Username not found.';
        }
        $stmt->close();

    // === FORGOT PASSWORD ===
    } elseif ($action === 'forgot_password') {
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("SELECT id, username FROM doctor_profile WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(50));
            // Gawing 24 hours (1 day) ang expiry para hindi mag-error agad
            $expires = date("Y-m-d H:i:s", strtotime("+1 day")); 

            $update = $conn->prepare("UPDATE doctor_profile SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update->bind_param("sss", $token, $expires, $email);
            
            if ($update->execute()) {
                $mail = new PHPMailer(true);
                try {
                    // SMTP Config
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    
                    // =================================================
                    // ILAGAY MO ULIT ANG APP PASSWORD MO DITO
                    // =================================================
                    $mail->Username   = 'harbienaga@gmail.com'; 
                    $mail->Password   = 'wacx eqwu yopp rxoa'; 
                    // =================================================

                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Disable SSL Check for Localhost
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    $mail->setFrom($mail->Username, 'GabayAI Support');
                    $mail->addAddress($email);

                    $resetLink = "http://localhost/gabayai/reset_password_doctor.php?token=" . $token;
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your Password - GabayAI';
                    $mail->Body    = "
                        <h2>Password Reset</h2>
                        <p>Hi " . htmlspecialchars($user['username']) . ",</p>
                        <p>Click here to reset your password:</p>
                        <p><a href='$resetLink'>$resetLink</a></p>
                        <p>Link expires in 24 hours.</p>
                    ";

                    $mail->send();
                    $response['success'] = true;
                    $response['message'] = 'Reset link sent! Please check your email.';
                } catch (Exception $e) {
                    $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
                }
            } else {
                $response['message'] = 'Database update failed.';
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'If account exists, email sent.';
        }
    }

} catch (Exception $e) {
    $response['message'] = 'Server Error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
