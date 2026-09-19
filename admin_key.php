<?php
include('session_config.php');
session_start();
include('db.php');

// Must be logged in
if(!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0){
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['user_id'];
$error = '';
$msg = '';

// Auto-detect any pending invite for this user (no re-login required)
$inv_check = mysqli_query($conn, "SELECT id FROM admin_invites WHERE user_id=$uid AND status='pending' LIMIT 1");
if($inv_check && mysqli_num_rows($inv_check) > 0){
    $_SESSION['pending_admin_key'] = 1;
} elseif(!isset($_SESSION['pending_admin_key'])){
    header("Location: index.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $key = trim($_POST['key'] ?? '');
    $key_hash = hash('sha256', $key);

    $kres = mysqli_query($conn, "SELECT * FROM admin_invites WHERE user_id=$uid AND status='pending' LIMIT 1");
    $inv = $kres ? mysqli_fetch_assoc($kres) : null;

    if($inv && hash_equals($inv['key_hash'], $key_hash)){
        // Promote to admin
        $inv_id = (int)$inv['id'];
        mysqli_query($conn, "UPDATE users SET is_admin = 1 WHERE id=$uid");
        mysqli_query($conn, "UPDATE admin_invites SET status='accepted' WHERE id=$inv_id");

        unset($_SESSION['pending_admin_key']);
        $_SESSION['is_admin'] = 1;
        header("Location: admin_panel.php?msg=promoted");
        exit();
    } else {
        $error = "Invalid or expired admin key. Please contact the super admin for a new key.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Key - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;padding:24px}
        .card{background:#fff;border-radius:16px;padding:40px;max-width:440px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);text-align:center}
        .icon{width:56px;height:56px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;margin:0 auto 20px}
        h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:8px}
        p{color:#64748b;font-size:14px;line-height:1.7;margin-bottom:24px}
        .error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px}
        label{display:block;font-size:13px;font-weight:600;color:#374151;text-align:left;margin-bottom:6px}
        input[type=text]{width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:15px;font-family:monospace;text-align:center;letter-spacing:2px;transition:all .2s}
        input[type=text]:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
        .btn{width:100%;padding:14px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:16px;transition:all .2s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .skip{display:inline-block;margin-top:20px;color:#94a3b8;font-size:13px;text-decoration:none}
        .skip:hover{color:#0d9488}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-key"></i></div>
        <h1>Enter Admin Key</h1>
        <p>You have been invited to become an administrator. Enter the key provided by the super admin to activate your admin access.</p>

        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <label>Admin Invite Key</label>
            <input type="text" name="key" placeholder="Paste key here" required autocomplete="off">
            <button type="submit" class="btn">Activate Admin Access</button>
        </form>
        <a href="manage_houses.php" class="skip">Skip for now — stay a landlord</a>
    </div>
</body>
</html>
