<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

// Only super admin can invite
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 2){
    header("Location: login.php");
    exit();
}

$msg = '';
$error = '';
$generated_key = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    csrf_validate();
    $action = $_POST['action'] ?? '';

    if($action == 'generate'){
        $email = trim($_POST['email']);
        $email = mysqli_real_escape_string($conn, $email);

        // find the user by email
        $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
        if($res && ($user = mysqli_fetch_assoc($res))){
            $uid = (int)$user['id'];
            if((int)$user['is_admin'] >= 1){
                $error = "This user is already an admin.";
            } else {
                // generate a one-time key for this email
                $key = bin2hex(random_bytes(16)); // 32 char
                $key_hash = hash('sha256', $key);
                $safe_hash = mysqli_real_escape_string($conn, $key_hash);

                // invalidate any existing pending invite for this user
                mysqli_query($conn, "DELETE FROM admin_invites WHERE user_id=$uid AND status='pending'");

                $insert = mysqli_query($conn, "INSERT INTO admin_invites (user_id, email, key_hash, status, created_by) VALUES ($uid, '$email', '$safe_hash', 'pending', {$_SESSION['user_id']})");
                if($insert){
                    $generated_key = $key;
                    $msg = "Invite generated for <b>" . htmlspecialchars($email) . "</b>. Share the key below with this user — it is valid for a single use.";
                } else {
                    $error = "Failed to create invite: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "No registered user found with email: " . htmlspecialchars($email);
        }
    }

    if($action == 'revoke'){
        $invite_id = (int)($_POST['invite_id'] ?? 0);
        mysqli_query($conn, "DELETE FROM admin_invites WHERE id=$invite_id");
        $msg = "Invite revoked.";
    }
}

// list pending & recent invites
$invites = mysqli_query($conn, "SELECT ai.*, u.full_name, u.is_admin FROM admin_invites ai LEFT JOIN users u ON u.id = ai.user_id ORDER BY ai.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Admin - AdamaRent</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-user-shield"></i></div>
            <div>
                <h1>Invite Admin</h1>
                <div class="page-sub">Generate one-time invite keys to promote landlords to admin</div>
            </div>
        </div>
    </div>

    <?php if($msg): ?><div class="flash flash-green"><?php echo $msg; ?></div><?php endif; ?>
    <?php if($error): ?><div class="flash flash-red"><?php echo $error; ?></div><?php endif; ?>

    <?php if($generated_key): ?>
        <div class="card">
            <h3>Invite Key Generated</h3>
            <div class="desc">Share this key with the invited user. They must enter it after logging in with their email. This key works one time.</div>
            <div class="key-box"><?php echo htmlspecialchars($generated_key); ?></div>
            <button class="btn btn-primary" onclick="navigator.clipboard.writeText('<?php echo $generated_key; ?>')"><i class="fas fa-copy"></i> Copy Key</button>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Generate Admin Invite</h3>
        <div class="desc">Enter the email of a registered landlord. They will be invited to become an admin.</div>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <label>Landlord's Email Address</label>
            <input type="email" name="email" placeholder="landlord@example.com" required>
            <button type="submit" name="action" value="generate" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-envelope"></i> Generate Invite Key</button>
        </form>
    </div>

    <div class="card">
        <h3>Invite History</h3>
        <?php if($invites && mysqli_num_rows($invites) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Account</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while($inv = mysqli_fetch_assoc($invites)): 
                    $st = strtolower($inv['status'] ?? 'pending');
                    $bclass = ($st=='accepted') ? 'badge-green' : (($st=='pending') ? 'badge-orange' : 'badge-gray');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['email']); ?></td>
                    <td><?php echo htmlspecialchars($inv['full_name'] ?? 'N/A'); ?></td>
                    <td><span class="badge <?php echo $bclass; ?>"><?php echo strtoupper($inv['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($inv['created_at']); ?></td>
                    <td>
                        <?php if($st == 'pending'): ?>
                        <form method="POST" style="display:inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="revoke">
                            <input type="hidden" name="invite_id" value="<?php echo (int)$inv['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger-ghost">Revoke</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="margin:0">No invites have been generated yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
