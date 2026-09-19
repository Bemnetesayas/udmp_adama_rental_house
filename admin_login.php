<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(isset($_POST['login'])){
    csrf_validate();
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email' AND is_admin >= 1 LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if($res && mysqli_num_rows($res) == 1){
        $user = mysqli_fetch_assoc($res);
        if(password_verify($password, $user['password'])){
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['is_admin'] = (int)$user['is_admin']; 
            header("Location: admin_panel.php");
            exit();
        } else { $err = "Invalid password. Please try again."; }
    } else { $err = "Access denied. No admin account found with this email."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;background:#f8fafc}
        .auth-left{flex:1;background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px;color:#fff;position:relative;overflow:hidden}
        .auth-left::before{content:'';position:absolute;width:400px;height:400px;border-radius:50%;background:rgba(99,102,241,.15);top:-80px;right:-80px}
        .auth-left::after{content:'';position:absolute;width:250px;height:250px;border-radius:50%;background:rgba(139,92,246,.1);bottom:-40px;left:-40px}
        .auth-left-content{position:relative;z-index:2;text-align:center;max-width:400px}
        .auth-left-content .logo{width:60px;height:60px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:24px;margin:0 auto 24px}
        .auth-left-content h2{font-size:28px;font-weight:800;margin-bottom:12px}
        .auth-left-content p{color:rgba(255,255,255,.7);font-size:15px;line-height:1.7}
        .auth-left .features{margin-top:40px;text-align:left}
        .auth-left .features li{list-style:none;padding:10px 0;color:rgba(255,255,255,.8);font-size:14px;display:flex;align-items:center;gap:12px}
        .auth-left .features li i{color:#a78bfa;font-size:16px}
        .auth-right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px}
        .auth-card{width:100%;max-width:400px}
        .auth-card .back-link{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13px;font-weight:500;margin-bottom:24px;transition:color .2s}
        .auth-card .back-link:hover{color:#6366f1}
        .auth-card h1{font-size:28px;font-weight:800;color:#0f172a;margin-bottom:6px;letter-spacing:-.5px}
        .auth-card .subtitle{color:#64748b;font-size:15px;margin-bottom:32px}
        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .error-msg i{font-size:16px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .input-wrapper{position:relative}
        .input-wrapper i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:16px}
        .form-group input{width:100%;padding:14px 14px 14px 44px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#4338ca,#6366f1);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:8px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(99,102,241,.4)}
        .mobile-brand{display:none;text-align:center;margin-bottom:32px}
        .mobile-brand .logo{width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:20px;color:#fff;margin:0 auto 12px}
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
            <div class="logo"><i class="fas fa-shield-halved"></i></div>
            <h2>Admin Portal</h2>
            <p>Secure access to the AdamaRent administration console. Manage users, listings, and approvals.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Manage users and permissions</li>
                <li><i class="fas fa-check-circle"></i> Approve property listings</li>
                <li><i class="fas fa-check-circle"></i> View analytics and reports</li>
            </ul>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-card">
            <div class="mobile-brand">
                <div class="logo"><i class="fas fa-shield-halved"></i></div>
                <h3>Admin Portal</h3>
            </div>
            <a href="Home.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
            <h1>Admin Login</h1>
            <p class="subtitle">Sign in with your admin credentials</p>

            <?php if(isset($err)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo $err; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Admin Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="admin@adamaRent.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-submit"><i class="fas fa-right-to-bracket"></i> Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
