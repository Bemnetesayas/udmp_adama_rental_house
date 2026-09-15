<?php
include('session_config.php');
session_start();
include('db.php');
include('mail_helper.php');

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
send_verification_email($email, $user['full_name'], $token);
header("Location: $back?resend=sent");
exit();