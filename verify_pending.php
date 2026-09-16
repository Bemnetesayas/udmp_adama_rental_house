<?php
include('session_config.php');
session_start();
include('db.php');
include('mail_helper.php');

$email = trim($_GET['email'] ?? '');
if ($email === '' && isset($_SESSION['verify_pending_email'])) {
    $email = $_SESSION['verify_pending_email'];
}
$email = htmlspecialchars($email);

$resend_msg = '';
if (isset($_GET['resend'])) {
    if ($_GET['resend'] === 'sent') {
        $resend_msg = ['ok', 'A fresh verification link has been sent. Check your inbox.'];
    } elseif ($_GET['resend'] === 'already') {
        $resend_msg = ['ok', 'This email is already verified — you can sign in now.'];
    } elseif ($_GET['resend'] === 'noaccount') {
        $resend_msg = ['err', 'No account found for that email address.'];
    } else {
        $resend_msg = ['err', 'Could not resend the link. Please try again.'];
    }
}

$dev_verify_link = '';
if (!mail_env_is_configured() && $email !== '') {
    $esc = mysqli_real_escape_string($conn, rawurldecode($email));
    $er = mysqli_query($conn, "SELECT verify_token FROM users WHERE email='$esc' AND email_verified=0 AND verify_token IS NOT NULL AND verify_expires > NOW() LIMIT 1");
    if ($er && ($rdev = mysqli_fetch_assoc($er))) {
        $dev_verify_link = 'verify_email.php?token=' . urlencode($rdev['verify_token']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Inbox - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f8fafc 0%,#f0fdfa 100%);padding:24px}
        .card{background:#fff;border-radius:20px;padding:48px 40px;max-width:460px;width:100%;box-shadow:0 12px 48px rgba(15,23,42,.10);text-align:center;position:relative;overflow:hidden}
        .card::before{content:'';position:absolute;inset:0 0 auto 0;height:6px;background:linear-gradient(90deg,#0d9488,#14b8a6,#2dd4bf)}
        .icon-wrap{width:88px;height:88px;border-radius:28px;background:linear-gradient(135deg,#0d9488,#14b8a6);display:flex;align-items:center;justify-content:center;margin:8px auto 24px;box-shadow:0 10px 30px rgba(13,148,136,.35);animation:pop .5s cubic-bezier(.34,1.56,.64,1)}
        .icon-wrap i{color:#fff;font-size:38px;animation:float 2.4s ease-in-out infinite}
        @keyframes pop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
        h1{font-size:24px;font-weight:800;color:#0f172a;letter-spacing:-.5px;margin-bottom:8px}
        .sub{color:#64748b;font-size:15px;line-height:1.7;margin-bottom:6px}
        .email-pill{display:inline-block;margin-top:12px;padding:10px 22px;background:#f0fdfa;border:1.5px solid #99f6e4;color:#0f766e;border-radius:99px;font-size:15px;font-weight:700;word-break:break-all}
        .hint{margin-top:20px;padding:14px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;text-align:left;font-size:13px;color:#64748b;line-height:1.7}
        .hint strong{color:#334155;font-size:13px}
        .hint li{margin-left:18px;margin-top:4px}
        .actions{margin-top:26px}
        .btn{display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;font-family:inherit;cursor:pointer;border:none;transition:all .2s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(13,148,136,.4)}
        .btn-line{display:inline-block;padding:12px 28px;background:#f1f5f9;color:#334155;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;margin-left:8px;transition:background .2s}
        .btn-line:hover{background:#e2e8f0}
        .dev-note{margin-top:16px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:12px;padding:12px 16px;font-size:13px;line-height:1.6}
        .dev-note a{color:#d97706;font-weight:700}
        .flash{margin-top:18px;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;text-align:left}
        .flash.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
        .flash.err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
        .back{margin-top:22px;font-size:13px;color:#94a3b8}
        .back a{color:#0d9488;font-weight:600;text-decoration:none}
        .back a:hover{text-decoration:underline}
        @media(max-width:480px){
            .card{padding:36px 24px}
            .btn-line{margin-left:0;margin-top:10px}
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class="fas fa-paper-plane"></i></div>
        <h1>Check your inbox</h1>
        <p class="sub">We sent a verification link to<br></p>
        <div class="email-pill"><?php echo $email; ?></div>
        <p class="sub" style="margin-top:16px">Click the link inside the email to activate your account and finish signing in.</p>

        <?php if ($resend_msg): ?>
            <div class="flash <?php echo $resend_msg[0]; ?>"><i class="fas <?php echo $resend_msg[0] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i> <?php echo $resend_msg[1]; ?></div>
        <?php endif; ?>

        <?php if ($dev_verify_link !== ''): ?>
            <div class="dev-note"><i class="fas fa-flask"></i> <strong>DEV MODE</strong> — email server not configured. Your verification link:<br>
                <a href="<?php echo $dev_verify_link; ?>"><?php echo $dev_verify_link; ?></a></div>
        <?php endif; ?>

        <div class="hint">
            <strong>Didn't receive the email?</strong>
            <ul>
                <li>Check your <strong>Spam</strong> or <strong>Promotions</strong> folder.</li>
                <li>Wait a minute — delivery can take a few moments.</li>
                <li>Make sure you entered the correct address above.</li>
            </ul>
        </div>

        <div class="actions">
            <form method="POST" action="resend_verification.php" style="display:inline-block">
                <input type="hidden" name="email" value="<?php echo $email; ?>">
                <button type="submit" name="resend" class="btn">Resend verification link</button>
            </form>
            <a href="login.php" class="btn-line">Back to Sign In</a>
        </div>

        <div class="back">Link expired or wrong email? <a href="login.php">Sign in and resend</a></div>
    </div>
</body>
</html>