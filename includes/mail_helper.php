<?php
// Load optional secrets (gitignored). Falls back to empty constants.
if (file_exists(__DIR__ . '/../config/config_secrets.php')) {
    require_once __DIR__ . '/../config/config_secrets.php';
}
if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', '');
    define('BREVO_FROM_EMAIL', '');
    define('BREVO_FROM_NAME', 'AdamaRent');
    define('GOOGLE_CLIENT_ID', '');
    define('GOOGLE_CLIENT_SECRET', '');
}

if (!function_exists('app_base_url')) {
    function app_base_url() {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $base = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $base .= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return rtrim($base, '/');
    }
}

// Returns true when a real (non-dev) mail setup is configured.
function mail_env_is_configured() {
    return defined('BREVO_API_KEY') && defined('BREVO_FROM_EMAIL')
        && BREVO_API_KEY !== '' && BREVO_FROM_EMAIL !== '';
}

// Append to a local mail log so the verification flow is testable in dev.
function log_mail_dev($subject, $to, $link = '', $note = '') {
    $entry = '[' . date('Y-m-d H:i:s') . "] To: $to | $subject";
    if ($link !== '') {
        $entry .= " | Link: $link";
    }
    if ($note !== '') {
        $entry .= " | $note";
    }
    $entry .= "\n";
    @file_put_contents(dirname(__DIR__) . '/logs/mail_log.txt', $entry, FILE_APPEND);
}

// Send a transactional email through Brevo API v3. Returns array [ok, info].
function send_mail_brevo($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
    if (!mail_env_is_configured()) {
        return ['ok' => false, 'info' => 'dev'];
    }

    $payload = [
        'sender' => ['email' => BREVO_FROM_EMAIL, 'name' => BREVO_FROM_NAME],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => ($textBody !== '' ? $textBody : strip_tags($htmlBody)),
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Api-Key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 20,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($code >= 200 && $code < 300) {
        return ['ok' => true, 'info' => "brevo http $code"];
    }
    $detail = $cerr !== '' ? $cerr : ($body !== false ? substr($body, 0, 500) : 'no response');
    log_mail_dev($subject, $toEmail, '', "BREVO ERROR ($code): $detail");
    return ['ok' => false, 'info' => "brevo error $code"];
}

// Build + send a "verify your email" mail.
// The verification link is logged locally ONLY when mail is not configured
// (dev mode) — never when a real email is sent, so tokens can't leak from logs.
function send_verification_email($email, $name, $token) {
    $link = app_base_url() . '/verify_email.php?token=' . urlencode($token);
    $subject = 'Verify your AdamaRent email address';
    $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:auto">'
        . '<h2 style="color:#0f172a">Welcome to AdamaRent, ' . htmlspecialchars($name) . '!</h2>'
        . '<p style="font-size:15px;color:#475569">Please confirm this email address to activate your account and start using AdamaRent.</p>'
        . '<p style="text-align:center"><a href="' . $link . '" style="display:inline-block;background:#0d9488;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:700">Verify my email</a></p>'
        . '<p style="font-size:13px;color:#94a3b8">Or copy this link into your browser: <br>' . $link . '</p>'
        . '<p style="font-size:12px;color:#cbd5e1">This link expires in 24 hours. If you didn\'t sign up on AdamaRent, you can ignore this email.</p>'
        . '</div>';
    $res = send_mail_brevo($email, $name, $subject, $html, "Verify your AdamaRent email: $link");
    if ($res['ok']) {
        log_mail_dev($subject, $email, 'kept private in inbox', 'sent via Brevo');
    } else {
        log_mail_dev($subject, $email, $res['info'] === 'dev' ? $link : 'n/a', $res['info'] === 'dev' ? 'mail not configured (dev link below)' : $res['info']);
    }
    return $res;
}