<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Build display name
    $displayName = $_SESSION['username'] ?? 'User';
    
    if (!empty($_SESSION['first_name'])) {
        $displayName = $_SESSION['first_name'];
        if (!empty($_SESSION['last_name'])) {
            $displayName .= ' ' . $_SESSION['last_name'];
        }
    }
    
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? 'User',
            'email' => $_SESSION['email'] ?? '',
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'display_name' => $displayName
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>
