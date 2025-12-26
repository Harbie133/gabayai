<?php
session_start();

// Clear only user session variables (keep doctor sessions intact)
unset($_SESSION['logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']);
unset($_SESSION['login_time']);

header("Location: userlogin.html"); // Or whatever your user login page is called
exit();
?>
