<?php 
include('session_config.php');
session_start();
include('db.php');

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = $_POST['password'];

    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if($res && ($user = mysqli_fetch_assoc($res))){
        if(password_verify($pass, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Check for a pending admin invite for this user
            $uid = (int)$user['id'];
            $check_invite = mysqli_query($conn, "SELECT * FROM admin_invites WHERE user_id=$uid AND status='pending' LIMIT 1");
            if(mysqli_num_rows($check_invite) > 0){
                $_SESSION['pending_admin_key'] = 1;
                header("Location: admin_key.php");
                exit();
            }

            if($user['is_admin'] >= 1){
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                header("Location: admin_panel.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "No account found with this email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AdamaRent</title>
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
            <h2>Welcome to AdamaRent</h2>
            <p>Sign in to manage your properties, track listings, and access your dashboard.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Manage house listings</li>
                <li><i class="fas fa-check-circle"></i> Track views and inquiries</li>
                <li><i class="fas fa-check-circle"></i> Admin & landlord access</li>
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
            <h1>Sign In</h1>
            <p class="subtitle">Enter your credentials to access your account</p>

            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
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
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>
</body>
</html>
