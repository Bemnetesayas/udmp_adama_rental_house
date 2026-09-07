<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "rental_db";

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    $fatal = "Cannot connect to MySQL. Check XAMPP/MySQL is running and the credentials in db.php.";
} else {
    mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbname");
    mysqli_select_db($conn, $dbname);

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_admin INT DEFAULT 0,
        status INT DEFAULT 0
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS houses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kebele VARCHAR(100) NOT NULL,
        street VARCHAR(255) NOT NULL,
        house_number VARCHAR(50),
        category VARCHAR(50) NOT NULL,
        amount INT NOT NULL,
        phone VARCHAR(50) NOT NULL,
        map_link TEXT,
        image VARCHAR(255) NOT NULL,
        description TEXT,
        user_id INT NOT NULL,
        video_file VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Pending',
        is_approved INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        delete_key VARCHAR(50)
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        house_id INT NOT NULL,
        status INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS house_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        house_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_house (house_id)
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS app_config (
        config_key VARCHAR(100) PRIMARY KEY,
        config_value TEXT
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        key_hash VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by INT
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rental_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        house_id INT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(20) NOT NULL DEFAULT 'info',
        title VARCHAR(120) NOT NULL,
        message TEXT,
        link VARCHAR(255),
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (user_id, is_read)
    )");

    $admin_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_admin >= 1"))[0];

    if ($admin_count == 0) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT config_value FROM app_config WHERE config_key='admin_setup_key'"));
        if ($row && !empty($row['config_value'])) {
            $key = $row['config_value'];
        } else {
            $key = bin2hex(random_bytes(16));
            $safe = mysqli_real_escape_string($conn, $key);
            mysqli_query($conn, "INSERT INTO app_config (config_key, config_value) VALUES ('admin_setup_key', '$safe')");
        }
        $msg = "Database installed successfully. No admin exists yet.";
        $msg .= "<br>Register an account at <b>register.php</b> with the admin setup key";
        $msg .= " (below) and that account becomes the admin.";
        $stage = "setup-key";
    } else {
        mysqli_query($conn, "DELETE FROM app_config WHERE config_key='admin_setup_key'");
        $msg = "Database is ready. An admin account already exists — log in via login.php.";
        $stage = "done";
    }

    @mkdir(__DIR__ . '/uploads', 0777, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Inter',sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px}
        .card{background:#fff;border-radius:16px;padding:40px;max-width:520px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.06);text-align:center}
        .logo{width:56px;height:56px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:22px;color:#fff;margin:0 auto 24px}
        h1{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:16px}
        .msg{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:16px;border-radius:10px;font-size:14px;line-height:1.8;margin-bottom:24px;text-align:left}
        .key{background:#fefce8;border:1px solid #fde047;color:#854d0e;padding:12px 16px;border-radius:10px;font-family:monospace;font-size:13px;font-weight:700;word-break:break-all;margin-bottom:16px}
        .btn{display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;transition:all .2s;border:none;cursor:pointer;margin:4px}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .btn-ghost{background:#f1f5f9;color:#334155}
        .note{margin-top:20px;font-size:12px;color:#94a3b8;line-height:1.6}
        .note code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:11px}
        .fatal{background:#fef2f2;border-color:#fecaca;color:#dc2626}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">AR</div>
        <h1>AdamaRent Setup</h1>
        <?php if (isset($fatal)): ?>
            <div class="msg fatal"><?php echo $fatal; ?></div>
        <?php else: ?>
            <div class="msg"><?php echo $msg; ?></div>
            <?php if ($stage === "setup-key"): ?>
                <p style="font-size:13px;color:#64748b;margin-bottom:6px">Admin setup key (use this when registering your first account):</p>
                <div class="key"><?php echo htmlspecialchars($key); ?></div>
                <a href="register.php" class="btn">Register as Admin</a>
                <p class="note">Store this key securely. After your first admin is created, this key is no longer usable. Delete <code>setup.php</code> once done.</p>
            <?php else: ?>
                <a href="login.php" class="btn">Go to Login</a>
                <p class="note">Delete <code>setup.php</code> now that setup is complete.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
