<?php
// verify_login.php
session_start();
require __DIR__ . '/db.php';

function back($code) {
  header('Location: login_doctor.php?error=' . urlencode($code));
  exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  back('missing');
}

$stmt = $mysqli->prepare('SELECT login_id, doctor_id, username, `password` FROM doctor_login WHERE username = ? LIMIT 1');
if (!$stmt) {
  back('server');
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
  $stmt->bind_result($login_id, $doctor_id, $db_username, $db_hash);
  $stmt->fetch();

  if (password_verify($password, $db_hash)) {
    // Prevent session fixation on successful login
    session_regenerate_id(true);

    $_SESSION['doctor_logged_in'] = true;
    $_SESSION['doctor_id'] = (int)$doctor_id;
    $_SESSION['doctor_username'] = $db_username;

    header('Location: DoctorDashboard.php');
    exit;
  }
}

back('invalid');
