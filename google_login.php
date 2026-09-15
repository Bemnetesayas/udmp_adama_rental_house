<?php
include('session_config.php');
session_start();
include('mail_helper.php');

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Google Sign-In - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;padding:24px}
        .card{background:#fff;border-radius:16px;padding:44px;max-width:440px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.06);text-align:center}
        .icon{width:64px;height:64px;border-radius:50%;background:#fffbeb;color:#d97706;border:2px solid #fde68a;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 20px}
        h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px}
        p{color:#64748b;font-size:14px;line-height:1.7}
        .btn{display:inline-block;margin-top:18px;padding:12px 30px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px}
    </style>
    </head>
    <body>
        <div class="card">
            <div class="icon"><i class="fas fa-wrench"></i></div>
            <h1>Google Sign-In not configured</h1>
            <p>Add your Google OAuth Client ID and Client Secret to <code>config_secrets.php</code>, then register
               <code>google_callback.php</code> as an authorized redirect URI in Google Cloud Console.</p>
            <a href="login.php" class="btn">Back to Sign In</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$redirect_uri = app_base_url() . '/google_callback.php';
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => $redirect_uri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);
header("Location: https://accounts.google.com/o/oauth2/v2/auth?$params");
exit();