<?php
// SECRETS TEMPLATE — copy this file to config_secrets.php on each machine and fill in.
// config_secrets.php is gitignored. Never commit real keys.

// Local dev: if APP_BASE_URL is set, verification/Google links use this URL instead of auto-detecting.
// Use the app machine's LAN IP so links in emails work when opened from ANY device/laptop:
//   e.g. http://192.168.1.50/udmp_adama_rental_house
// Leave EMPTY to auto-detect (fine when registering + verifying on the SAME machine).
define('APP_BASE_URL', '');

// MySQL database credentials (db.php reads these).
// LOCAL XAMPP defaults: host localhost, user root, password empty, db rental_db.
// On INFINITYFREE use the values shown in your control panel (Databases > MySQL),
// e.g. host sql303.infinityfree.com, user if0_12345678, db if0_12345678_rental_db.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rental_db');

// Brevo (Sendinblue) transactional API v3
define('BREVO_API_KEY', '');                // "xkeysib-..." API v3 key
define('BREVO_FROM_EMAIL', '');             // verified sender address, e.g. noreply@yourdomain.com
define('BREVO_FROM_NAME', 'AdamaRent');

// Google Sign-In (OAuth 2.0) — Google Cloud Console
define('GOOGLE_CLIENT_ID', '');             // "xxxx.apps.googleusercontent.com"
define('GOOGLE_CLIENT_SECRET', '');         // OAuth client secret