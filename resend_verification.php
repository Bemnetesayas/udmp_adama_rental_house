<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/mail_helper.php');
include('includes/security.php');

if (!empty($_POST)) csrf_validate();

$email = trim($_POST['email'] ?? '');
if ($email === '' && isset($_SESSION['verify_pending_email'])) {
    $email = $_SESSION['verify_pending_email'];
}
$back = 'login.php';

if ($email === '') {
    header("Location: $back?resend=failed");
    exit();
}

$esc = mysqli_real_escape_string($conn, $email);
$res = mysqli_query($conn, "SELECT id, full_name, email_verified FROM users WHERE email='$esc' LIMIT 1");
if (!$res || !($user = mysqli_fetch_assoc($res))) {
    header("Location: $back?resend=noaccount");
    exit();
}
if ((int)$user['email_verified'] === 1) {
    header("Location: $back?resend=already");
    exit();
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 86400);
mysqli_query($conn, "UPDATE users SET verify_token='$token', verify_expires='$expires' WHERE id=" . (int)$user['id']);

$_SESSION['verify_pending_email'] = $email;
$mailResult = send_verification_email($email, $user['full_name'], $token);
$ep = 'email=' . urlencode($email);
if ($mailResult['ok'] === true) {
    header("Location: verify_pending.php?$ep&resend=sent");
} else {
    $why = ($mailResult['info'] === 'brevo ip not authorized') ? '&why=ip_auth' : '';
    header("Location: verify_pending.php?$ep&resend=failed$why");
}
exit();