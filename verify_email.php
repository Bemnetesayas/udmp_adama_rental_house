<?php
include('db.php');

$status = 'invalid';
if (isset($_GET['token']) && $_GET['token'] !== '') {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $res = mysqli_query($conn, "SELECT id, full_name, email, email_verified, verify_expires FROM users WHERE verify_token='$token' LIMIT 1");
    if ($res && ($user = mysqli_fetch_assoc($res))) {
        if ((int)$user['email_verified'] === 1) {
            $status = 'already';
        } elseif ($user['verify_expires'] !== null && strtotime($user['verify_expires']) < time()) {
            $status = 'expired';
        } else {
            mysqli_query($conn, "UPDATE users SET email_verified=1, verify_token=NULL, verify_expires=NULL WHERE id=" . (int)$user['id']);
            $status = 'ok';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:24px}
        .card{background:#fff;border-radius:16px;padding:44px;max-width:440px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.06);text-align:center}
        .icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 20px}
        .icon.ok{background:#f0fdf4;color:#16a34a;border:2px solid #bbf7d0}
        .icon.bad{background:#fef2f2;color:#dc2626;border:2px solid #fecaca}
        .icon.warn{background:#fffbeb;color:#d97706;border:2px solid #fde68a}
        h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px}
        p{color:#64748b;font-size:14px;line-height:1.7;margin-bottom:8px}
        .btn{display:inline-block;margin-top:18px;padding:12px 30px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;transition:all .2s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .btn-line{display:inline-block;margin-top:14px;padding:12px 30px;background:#f1f5f9;color:#334155;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;margin-left:6px}
    </style>
</head>
<body>
    <?php if ($status === 'ok'): ?>
        <div class="card">
            <div class="icon ok"><i class="fas fa-check"></i></div>
            <h1>Email verified!</h1>
            <p>Your email has been confirmed. You can now sign in to your AdamaRent account.</p>
            <a href="login.php" class="btn">Go to Sign In</a>
        </div>
    <?php elseif ($status === 'already'): ?>
        <div class="card">
            <div class="icon ok"><i class="fas fa-check"></i></div>
            <h1>Already verified</h1>
            <p>This email was already confirmed. You can sign in now.</p>
            <a href="login.php" class="btn">Go to Sign In</a>
        </div>
    <?php elseif ($status === 'expired'): ?>
        <div class="card">
            <div class="icon warn"><i class="fas fa-clock"></i></div>
            <h1>Link expired</h1>
            <p>This verification link is no longer valid. Log in and click "Resend verification" to get a fresh link.</p>
            <a href="login.php" class="btn-line">Back to Login</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="icon bad"><i class="fas fa-circle-exclamation"></i></div>
            <h1>Invalid link</h1>
            <p>We couldn't find a pending verification for this link. Check the link or request a new one from the login page.</p>
            <a href="login.php" class="btn">Back to Login</a>
        </div>
    <?php endif; ?>
</body>
</html>