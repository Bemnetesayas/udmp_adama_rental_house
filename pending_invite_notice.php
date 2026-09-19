<?php
// Pending admin invite banner — include AFTER session_start() and db.php
if(isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0){
    $pin_uid = (int)$_SESSION['user_id'];
    $pin_check = mysqli_query($conn, "SELECT id FROM admin_invites WHERE user_id=$pin_uid AND status='pending' LIMIT 1");
    if($pin_check && mysqli_num_rows($pin_check) > 0): ?>
        <div class="invite-notice" style="background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:center;gap:12px;font-size:14px;font-weight:600;position:relative;z-index:60;flex-wrap:wrap">
            <i class="fas fa-user-shield"></i>
            <span>You have been invited to become an administrator.</span>
            <a href="admin_key.php" style="background:#fff;color:#0d9488;padding:7px 16px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px">Enter Invite Key</a>
        </div>
    <?php endif;
}