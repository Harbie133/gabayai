<?php
session_start();
date_default_timezone_set('Asia/Manila');

// I-off ang error display sa browser para malinis ang JSON response
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Check connection file
require_once 'db.php'; 

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => 'An error occurred'];

try {
    if ($conn->connect_error) {
        throw new Exception("Connection Failed: " . $conn->connect_error);
    }

    $action = $_POST['action'] ?? '';

    // === LOGIN ===
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['logged_in'] = true; 
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email']; 

                $response['success'] = true;
                $response['message'] = 'Login successful';
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            $response['message'] = 'User not found';
        }
    } 

    // === REGISTER ===
    elseif ($action === 'register') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        
        if($check->get_result()->num_rows > 0) {
            $response['message'] = 'Username or Email already taken';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password);
            if($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Registration successful';
            } else {
                $response['message'] = 'Database Error';
            }
        }
    }

    // === FORGOT PASSWORD ===
    elseif ($action === 'forgot_password') {
        $email = $_POST['email'];
        
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $token = bin2hex(random_bytes(50));
            $expires = date("Y-m-d H:i:s", strtotime("+1 day"));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update->bind_param("sss", $token, $expires, $email);
            
            if ($update->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'harbienaga@gmail.com'; 
                    $mail->Password = 'wacx eqwu yopp rxoa'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // *** ITO ANG FIX PARA SA SSL ERROR ***
                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    $mail->setFrom($mail->Username, 'GabayAI Support');
                    $mail->addAddress($email);

                    // Siguraduhin na tama ang path ng reset_password.php mo
                    $link = "http://localhost/gabayai/reset_password.php?token=" . $token;
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Password Request';
                    $mail->Body = "
                        <h3>Password Reset Request</h3>
                        <p>Hi " . htmlspecialchars($user['username']) . ",</p>
                        <p>You requested a password reset. Click the link below to proceed:</p>
                        <p><a href='$link' style='background:#764ba2; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
                        <p><small>If you did not request this, please ignore this email.</small></p>
                    ";

                    $mail->send();
                    $response['success'] = true;
                    $response['message'] = 'Reset link sent! Check your email inbox/spam.';
                } catch (Exception $e) {
                    $response['message'] = 'Mailer Error: ' . $mail->ErrorInfo;
                }
            }
        } else {
            // Fake success para sa security (iwas user fishing)
            $response['success'] = true; 
            $response['message'] = 'If email exists, link was sent.';
        }
    }

} catch (Exception $e) {
    $response['message'] = 'Server Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
?>
