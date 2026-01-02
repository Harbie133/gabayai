<?php
date_default_timezone_set('Asia/Manila'); 
require_once 'db.php'; 

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newToken = $_POST['token'];
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id, reset_expires FROM doctor_profile WHERE reset_token = ?");
        $stmt->bind_param("s", $newToken);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (time() < strtotime($row['reset_expires'])) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE doctor_profile SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $update->bind_param("si", $hashed, $row['id']);
                
                if ($update->execute()) {
                    $success = "Password updated successfully! <br><br> <a href='doctor_login.html' class='btn-link'>Go to Login</a>";
                } else {
                    $error = "Database error.";
                }
            } else {
                $error = "Link expired. Request a new one.";
            }
        } else {
            $error = "Invalid token.";
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
    <!-- Add FontAwesome for Eye Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(61, 90, 128, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        h2 { color: #3d5a80; margin-bottom: 20px; margin-top: 0; }
        
        /* Password Wrapper for Icon Positioning */
        .password-wrapper {
            position: relative;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .password-wrapper label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #4a5568;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 40px 12px 15px; /* Right padding for icon */
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus { border-color: #3d5a80; }

        /* Eye Icon Style */
        .toggle-password {
            position: absolute;
            right: 15px;
            bottom: 12px; /* Center vertically relative to input height */
            cursor: pointer;
            color: #718096;
            font-size: 1.1rem;
            background: none;
            border: none;
            padding: 0;
            z-index: 10;
        }
        .toggle-password:hover { color: #3d5a80; }

        button.submit-btn {
            width: 100%;
            padding: 14px;
            background: #3d5a80;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        button.submit-btn:hover { background: #2c425e; }

        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .success { background: #d1fae5; color: #047857; border: 1px solid #6ee7b7; }
        
        .btn-link { 
            color: #3d5a80; font-weight: bold; text-decoration: none; 
            border: 1px solid #3d5a80; padding: 8px 16px; border-radius: 5px;
            display: inline-block;
        }
        .btn-link:hover { background: #3d5a80; color: white; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Reset Password</h2>

        <?php if($error): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="msg success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="password-wrapper">
                    <label>New Password</label>
                    <input type="password" name="password" id="pass1" placeholder="Enter new password" required>
                    <button type="button" class="toggle-password" onclick="togglePass('pass1', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="password-wrapper">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="pass2" placeholder="Confirm password" required>
                    <button type="button" class="toggle-password" onclick="togglePass('pass2', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="submit-btn">Change Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function togglePass(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
