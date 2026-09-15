<?php
include('session_config.php');
session_start();
include('db.php');
include('mail_helper.php');

function google_redirect($target = 'login.php?google=error') {
    header("Location: $target");
    exit();
}

// CSRF state check
$err_state = $_SESSION['google_oauth_state'] ?? '';
unset($_SESSION['google_oauth_state']);
$state = $_GET['state'] ?? '';
if ($state === '' || $err_state === '' || !hash_equals($err_state, $state)) {
    google_redirect('login.php?google=state');
}
if (isset($_GET['error'])) {
    google_redirect('login.php?google=denied');
}
if (!isset($_GET['code'])) {
    google_redirect('login.php?google=error');
}
if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    google_redirect('login.php?google=unconfigured');
}

// Exchange the authorization code for tokens
$redirect_uri = app_base_url() . '/google_callback.php';
$post = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code',
]);
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT => 20,
]);
$tok_body = curl_exec($ch);
$tok_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tok = json_decode((string)$tok_body, true);
if ($tok_code < 200 || $tok_code >= 300 || empty($tok['id_token'])) {
    google_redirect('login.php?google=token');
}

// Validate the ID token with Google (issuer, audience, expiry, email)
$info_body = file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($tok['id_token']));
$info = json_decode((string)$info_body, true);
if (empty($info['email']) || ($info['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    google_redirect('login.php?google=invalid');
}
if (isset($info['exp']) && (int)$info['exp'] < time()) {
    google_redirect('login.php?google=expired');
}

$g_email = $info['email'];
$g_name = $info['name'] ?? (strstr($g_email, '@', true) ?: $g_email);
$esc_email = mysqli_real_escape_string($conn, $g_email);
$esc_name = mysqli_real_escape_string($conn, $g_name);

// Link by email: create account if missing, or attach verification if present
$res = mysqli_query($conn, "SELECT * FROM users WHERE email='$esc_email' LIMIT 1");
if ($res && ($user = mysqli_fetch_assoc($res))) {
    // Google proved this email — mark verified if it wasn't
    if ((int)$user['email_verified'] === 0) {
        mysqli_query($conn, "UPDATE users SET email_verified=1, verify_token=NULL, verify_expires=NULL WHERE id=" . (int)$user['id']);
        $user['email_verified'] = 1;
    }
    if ((int)$user['is_admin'] === 0 && $user['full_name'] === '') {
        mysqli_query($conn, "UPDATE users SET full_name='$esc_name' WHERE id=" . (int)$user['id']);
        $user['full_name'] = $g_name;
    }
} else {
    $pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (full_name, email, password, email_verified) VALUES ('$esc_name', '$esc_email', '$pass', 1)";
    if (!mysqli_query($conn, $sql)) {
        google_redirect('login.php?google=error');
    }
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$esc_email' LIMIT 1"));
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
if ((int)$user['is_admin'] >= 1) {
    $_SESSION['is_admin'] = (int)$user['is_admin'];
    header("Location: admin_panel.php");
} else {
    header("Location: index.php");
}
exit();