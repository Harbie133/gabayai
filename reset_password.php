<?php
// === CONFIGURATION ===
require_once 'db.php'; // Siguraduhin na tama ang path ng db.php mo

$message = '';
$messageType = '';

// Kuhanin ang token mula sa URL
$token = $_GET['token'] ?? '';

// Kung nag-submit ng bagong password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($pass !== $confirm) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } elseif (strlen($pass) < 6) {
        $message = "Password must be at least 6 characters.";
        $messageType = "error";
    } else {
        // Check kung valid pa ang token
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $userId = $row['id'];
            $newHash = password_hash($pass, PASSWORD_DEFAULT);

            // Update password at tanggalin na ang token
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->bind_param("si", $newHash, $userId);

            if ($update->execute()) {
                $message = "Password updated successfully! Redirecting to login...";
                $messageType = "success";
                echo "<script>setTimeout(() => { window.location.href = 'userlogin.html'; }, 3000);</script>";
            } else {
                $message = "Database error. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid or expired token.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GabayAI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* === PURPLE THEME DESIGN === */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Purple Gradient */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%; max-width: 400px;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px; color: white; text-align: center;
        }

        .auth-header h2 { font-size: 1.5rem; }

        .auth-body { padding: 30px; }

        .form-group { margin-bottom: 20px; position: relative; }
        
        .form-group label { display: block; margin-bottom: 8px; color: #4a5568; font-weight: 600; }
        
        .form-group input {
            width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 1rem; outline: none; transition: 0.3s;
        }

        .form-group input:focus { border-color: #764ba2; box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1); }

        .toggle-password {
            position: absolute; right: 15px; top: 42px; color: #718096; cursor: pointer;
        }

        .btn-submit {
            width: 100%; padding: 12px; background: #764ba2; color: white; border: none;
            border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s;
        }

        .btn-submit:hover { background: #553c9a; transform: translateY(-2px); }

        .message {
            padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.9rem;
        }
        .message.error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        .message.success { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }

    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <h2>Reset Password</h2>
        </div>
        <div class="auth-body">
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" id="pass" required placeholder="Enter new password">
                    <i class="fas fa-eye toggle-password" onclick="toggle('pass')"></i>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm" required placeholder="Repeat password">
                    <i class="fas fa-eye toggle-password" onclick="toggle('confirm')"></i>
                </div>

                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        function toggle(id) {
            let x = document.getElementById(id);
            if (x.type === "password") x.type = "text";
            else x.type = "password";
        }
    </script>
</body>
</html>
