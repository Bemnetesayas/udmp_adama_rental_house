<?php
// Load optional secrets (gitignored). Falls back to empty constants.
if (file_exists(__DIR__ . '/config_secrets.php')) {
    require_once __DIR__ . '/config_secrets.php';
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
        if (defined('APP_BASE_URL') && APP_BASE_URL !== '') {
            return rtrim(APP_BASE_URL, '/');
        }
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
    @file_put_contents(__DIR__ . '/uploads/mail_log.txt', $entry, FILE_APPEND);
}

// POST to a URL with cURL. If the first attempt fails with an SSL error
// (common on XAMPP where no CA bundle is configured), retry once with SSL
// verification disabled so local development still works. Never returns the
// API key or the payload — only [body, httpCode, curlError, usedSslFallback].
// $options must contain CURLOPT_URL.
function curl_post_with_ssl_fallback($options, &$usedSslFallback) {
    $usedSslFallback = false;
    $opts = $options;
    $opts[CURLOPT_SSL_VERIFYPEER] = true;
    $opts[CURLOPT_SSL_VERIFYHOST] = 2;

    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false && stripos($err, 'SSL') !== false) {
        $usedSslFallback = true;
        $opts[CURLOPT_SSL_VERIFYPEER] = false;
        $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
    }
    return [$body, $code, $err];
}

// Send a transactional email through Brevo API v3. Returns array [ok, info].
function send_mail_brevo($toEmail, $toName, $subject, $htmlBody, $textBody = '') {
    if (!mail_env_is_configured()) {
        return ['ok' => false, 'info' => 'dev'];
    }

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        log_mail_dev($subject, $toEmail, '', 'INVALID RECIPIENT EMAIL — mail not sent');
        return ['ok' => false, 'info' => 'invalid recipient email'];
    }

    if (!extension_loaded('curl') || !function_exists('curl_init')) {
        log_mail_dev($subject, $toEmail, '', 'CURL NOT AVAILABLE — enable php_curl in php.ini (or uncomment extension=curl) and restart Apache');
        return ['ok' => false, 'info' => 'curl not available'];
    }

    $payload = [
        'sender' => ['email' => BREVO_FROM_EMAIL, 'name' => BREVO_FROM_NAME],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => ($textBody !== '' ? $textBody : strip_tags($htmlBody)),
    ];

    $options = [
        CURLOPT_URL => 'https://api.brevo.com/v3/smtp/email',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Api-Key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 20,
    ];

    $usedSslFallback = false;
    list($body, $code, $cerr) = curl_post_with_ssl_fallback($options, $usedSslFallback);

    if ($code >= 200 && $code < 300) {
        if ($usedSslFallback) {
            log_mail_dev($subject, $toEmail, '', 'sent via Brevo (SSL verification disabled for XAMPP fallback)');
        }
        return ['ok' => true, 'info' => "brevo http $code"];
    }

    if ($cerr !== '') {
        $detail = 'curl: ' . $cerr;
    } elseif ($body !== false && $body !== '') {
        $detail = 'response: ' . substr($body, 0, 500);
    } else {
        $detail = 'no response';
    }
    if ($usedSslFallback) {
        $detail .= ' | used SSL fallback';
    }

    // Brevo blocks sends from IPs that are not authorised on the account.
    if (is_string($body) && (stripos($body, 'unrecognised IP') !== false || stripos($body, 'authorised_ips') !== false)) {
        $detectedIp = '';
        if (preg_match('/\b(\d{1,3}(?:\.\d{1,3}){3})\b/', $body, $m)) {
            $detectedIp = $m[1];
        }
        $ipHint = $detectedIp !== '' ? " IP $detectedIp." : '.';
        log_mail_dev($subject, $toEmail, '', "BREVO 401: IP NOT AUTHORISED — add this server's public IP to app.brevo.com > Settings > Security > Authorized IPs$ipHint");
        return ['ok' => false, 'info' => 'brevo ip not authorized'];
    }

    log_mail_dev($subject, $toEmail, '', "BREVO ERROR ($code): $detail");
    return ['ok' => false, 'info' => "brevo error $code"];
}

// Build + send a "verify your email" mail. Always logs the link locally (dev/testable).
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
        log_mail_dev($subject, $email, $link, 'sent via Brevo');
    } else {
        log_mail_dev($subject, $email, $link, $res['info'] === 'dev' ? 'mail not configured (dev link below)' : $res['info']);
    }
    return $res;
}