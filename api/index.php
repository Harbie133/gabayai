<?php
// api/index.php - FINAL GabayAI Auth API for Vercel + TiDB Cloud
// Compatible with your db.php in ROOT + PHPMailer in ROOT

ini_set('session.save_path', '/tmp');
session_start();
date_default_timezone_set('Asia/Manila');

// Clean JSON + CORS headers
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// LOAD YOUR DB.PHP FROM ROOT (works from api/ folder)
require_once '../db.php';  // ../ = go up to root/db.php

// PHPMailer from root
require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'message' => 'Server error'];

try {
    // Verify DB connection
    if ($conn->connect_error) {
        throw new Exception("Database unavailable: " . $conn->connect_error);
    }

    $action = $_POST['action'] ?? '';

    // === LOGIN ===
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response['message'] = 'Username and password required';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];

                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['user'] = $row;
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            $response['message'] = 'User not found';
        }
        $stmt->close();
    }

    // === REGISTER ===
    elseif ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $raw_pass = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($raw_pass)) {
            $response['message'] = 'All fields are required';
            echo json_encode($response);
            exit;
        }

        $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);

        // Check existing
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $response['message'] = 'Username or email already exists';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_pass);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please login.';
            } else {
                $response['message'] = 'Registration failed: ' . $stmt->error;
            }
        }
        $check->close();
    }

    // === FORGOT PASSWORD ===
    elseif ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $response['message'] = 'Email required';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(50));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update->bind_param("sss", $token, $expires, $email);

            if ($update->execute()) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'harbienaga@gmail.com';
                    $mail->Password = getenv('SMTP_PASS') ?: 'wacx eqwu yopp rxoa';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    $mail->setFrom('harbienaga@gmail.com', 'GabayAI');
                    $mail->addAddress($email);

                    $reset_link = "https://gabayai.vercel.app/reset_password.php?token=" . $token;
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your GabayAI Password';
                    $mail->Body = "
                        <h2>Password Reset</h2>
                        <p>Hi {$user['username']},</p>
                        <p>Reset your password: <a href='$reset_link' style='background:#764ba2;color:white;padding:12px 24px;text-decoration:none;border-radius:5px;'>Reset Now</a></p>
                        <p>Expires in 24 hours | <small>If not requested, ignore.</small></p>
                    ";

                    $mail->send();
                    $response['success'] = true;
                    $response['message'] = 'Check your email for reset link!';
                } catch (Exception $e) {
                    $response['message'] = 'Email failed: ' . $mail->ErrorInfo;
                }
            }
            $update->close();
        } else {
            $response['success'] = true;
            $response['message'] = 'If email exists, check your inbox.';
        }
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    $conn->close();
}

echo json_encode($response);
?>
