<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');

function has_admin(): bool {
    global $conn;
    $r = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_admin >= 1");
    return $r && (int)(mysqli_fetch_row($r)[0] ?? 0) > 0;
}

function setup_key_valid(string $submitted): bool {
    global $conn;
    $r = mysqli_query($conn, "SELECT config_value FROM app_config WHERE config_key='admin_setup_key'");
    if (!$r) return false;
    $row = mysqli_fetch_assoc($r);
    $key = $row['config_value'] ?? '';
    return $key !== '' && hash_equals($key, $submitted);
}

$google_enabled = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';

if(isset($_POST['register'])){
    csrf_validate();
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $setup_key = trim($_POST['setup_key'] ?? '');

    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if($check_email && mysqli_num_rows($check_email) > 0){
        $error = "An account with this email already exists.";
    } elseif ($setup_key !== '' && !has_admin() && setup_key_valid($setup_key)) {
        $sql = "INSERT INTO users (full_name, email, password, is_admin, status, email_verified) VALUES ('$name', '$email', '$pass', 2, 1, 1)";
        if(mysqli_query($conn, $sql)){
            mysqli_query($conn, "DELETE FROM app_config WHERE config_key='admin_setup_key'");
            header("Location: login.php?setup=admin");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24h
        $sql = "INSERT INTO users (full_name, email, password, email_verified, verify_token, verify_expires) VALUES ('$name', '$email', '$pass', 0, '$token', '$expires')";
        if(mysqli_query($conn, $sql)){
            $_SESSION['verify_pending_email'] = $email;
            $mailResult = send_verification_email($email, $name, $token);
            $loc = 'verify_pending.php?email=' . urlencode($email);
            if ($mailResult['ok'] === false && $mailResult['info'] !== 'dev') {
                $loc .= '&resend=failed';
                if ($mailResult['info'] === 'brevo ip not authorized') {
                    $loc .= '&why=ip_auth';
                }
            }
            header("Location: $loc");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;background:#f8fafc}
        .auth-left{flex:1;background:linear-gradient(135deg,#0f172a 0%,#134e4a 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px;color:#fff;position:relative;overflow:hidden}
        .auth-left::before{content:'';position:absolute;width:500px;height:500px;border-radius:50%;background:rgba(13,148,136,.15);top:-100px;right:-100px}
        .auth-left::after{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(45,212,191,.1);bottom:-50px;left:-50px}
        .auth-left-content{position:relative;z-index:2;text-align:center;max-width:400px}
        .auth-left-content .logo{width:60px;height:60px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:24px;margin:0 auto 24px}
        .auth-left-content h2{font-size:28px;font-weight:800;margin-bottom:12px}
        .auth-left-content p{color:rgba(255,255,255,.7);font-size:15px;line-height:1.7}
        .auth-left .features{margin-top:40px;text-align:left}
        .auth-left .features li{list-style:none;padding:10px 0;color:rgba(255,255,255,.8);font-size:14px;display:flex;align-items:center;gap:12px}
        .auth-left .features li i{color:#2dd4bf;font-size:16px}
        .auth-right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px}
        .auth-card{width:100%;max-width:400px}
        .auth-card .back-link{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13px;font-weight:500;margin-bottom:24px;transition:color .2s}
        .auth-card .back-link:hover{color:#0d9488}
        .auth-card h1{font-size:28px;font-weight:800;color:#0f172a;margin-bottom:6px;letter-spacing:-.5px}
        .auth-card .subtitle{color:#64748b;font-size:15px;margin-bottom:32px}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .error-msg i{font-size:16px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .input-wrapper{position:relative}
        .input-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:16px}
        .form-group input{width:100%;padding:14px 14px 14px 44px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:8px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .divider{display:flex;align-items:center;gap:12px;margin:24px 0 6px;color:#94a3b8;font-size:12px;font-weight:500}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
        .btn-google{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;padding:12px;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;color:#0f172a;cursor:pointer;text-decoration:none;transition:all .2s;margin-top:10px}
        .btn-google:hover{background:#f8fafc;border-color:#cbd5e1;transform:translateY(-1px)}
        .auth-footer{text-align:center;margin-top:28px;font-size:14px;color:#64748b}
        .auth-footer a{color:#0d9488;text-decoration:none;font-weight:600}
        .auth-footer a:hover{text-decoration:underline}
        .mobile-brand{display:none;text-align:center;margin-bottom:32px}
        .mobile-brand .logo{width:48px;height:48px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:20px;color:#fff;margin:0 auto 12px}
        .mobile-brand h3{font-size:18px;font-weight:800;color:#0f172a}
        @media(max-width:768px){
            body{flex-direction:column}
            .auth-left{display:none}
            .mobile-brand{display:block}
            .auth-right{padding:24px}
        }
    </style>
</head>
<body>
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo">AR</div>
            <h2>Join AdamaRent</h2>
            <p>Create your landlord account and start listing your properties to thousands of potential tenants.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> List unlimited properties</li>
                <li><i class="fas fa-check-circle"></i> Reach hundreds of tenants</li>
                <li><i class="fas fa-check-circle"></i> Free to get started</li>
            </ul>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-card">
            <div class="mobile-brand">
                <div class="logo">AR</div>
                <h3>AdamaRent</h3>
            </div>
            <a href="Home.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
            <h1>Create Account</h1>
            <p class="subtitle">Fill in your details to get started as a landlord</p>

            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="Your full name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Create a strong password" required minlength="6">
                    </div>
                </div>
                <div class="form-group" id="setupKeyGroup" style="display:none">
                    <label>Admin Setup Key <span class="opt">(optional)</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="text" name="setup_key" placeholder="Paste admin setup key here">
                    </div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:6px">Only needed if you want this account to become the first administrator.</div>
                </div>
                <div style="text-align:center;margin-bottom:8px">
                    <label style="font-size:13px;color:#64748b;cursor:pointer">
                        <input type="checkbox" id="setupKeyToggle" style="margin-right:6px"> I'm the site administrator
                    </label>
                </div>
                <button type="submit" name="register" class="btn-submit">Create Account</button>
            </form>

            <?php if($google_enabled): ?>
                <div class="divider">or</div>
                <a href="google_login.php" class="btn-google">
                    <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.2 6.2 29.5 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.5 0 10.5-2.1 14.2-5.6l-6.6-5.6C29.5 34.4 26.9 36 24 36c-5.2 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1c.3 1.2.4 2.5.4 3.9s-.2 2.7-.4 3.9c-1.3 5.6-5.4 10.5-11 12.7l6.6 5.6C43.2 42.2 48 36 48 24c0-1.3-.1-2.7-.4-3.9L43.6 20.1z"/></svg>
                    Continue with Google
                </a>
            <?php endif; ?>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('setupKeyToggle').addEventListener('change', function(){
        document.getElementById('setupKeyGroup').style.display = this.checked ? 'block' : 'none';
    });
    </script>
</body>
</html>
