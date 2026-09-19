<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header('Location: login.php');
    exit();
}

$requests = mysqli_query($conn, "SELECT * FROM requests WHERE status = 0 ORDER BY created_at DESC");

$flash = [
    'approved' => ['Request approved.', 'green'],
    'rejected' => ['Request rejected.', 'red'],
];
$msg = isset($_GET['msg'], $flash[$_GET['msg']]) ? $flash[$_GET['msg']] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - AdamaRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-inbox"></i></div>
            <div>
                <h1>Pending Approvals</h1>
                <div class="page-sub">Review property requests before they go live</div>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="flash flash-<?php echo $msg[1]; ?>">
            <i class="fas fa-<?php echo $msg[1] === 'green' ? 'check-circle' : 'times-circle'; ?>"></i>
            <?php echo $msg[0]; ?>
        </div>
    <?php endif; ?>

    <?php if($requests && mysqli_num_rows($requests) > 0): ?>
        <?php while($req = mysqli_fetch_assoc($requests)): 
            $user_res = mysqli_query($conn, "SELECT full_name FROM users WHERE id = " . (int)$req['user_id']);
            $user = $user_res ? mysqli_fetch_assoc($user_res) : null;
            $house_res = mysqli_query($conn, "SELECT * FROM houses WHERE id = " . (int)$req['house_id']);
            $house = $house_res ? mysqli_fetch_assoc($house_res) : null;
        ?>
            <div class="request-card">
                <div class="req-img">
                    <?php if(!empty($house['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($house['image']); ?>" alt="Property">
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: ($house['category'] ?? 'New Listing')); ?></h3>
                    <div class="req-meta">
                        <span><i class="fas fa-tag"></i> <strong><?php echo htmlspecialchars($house['category'] ?? ''); ?></strong></span>
                        <span><i class="fas fa-money-bill"></i> <strong><?php echo number_format($house['amount'] ?? 0); ?> ETB</strong></span>
                        <span><i class="fas fa-location-dot"></i> Kebele <?php echo htmlspecialchars($house['kebele'] ?? ''); ?></span>
                    </div>
                    <div class="req-meta">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></span>
                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($house['phone'] ?? 'N/A'); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <form action="admin_actions.php" method="POST" style="display:inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form action="admin_actions.php" method="POST" style="display:inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                        <button type="submit" class="btn btn-danger-ghost"><i class="fas fa-times"></i> Reject</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>All caught up!</h3>
            <p>No pending requests to review at this time.</p>
        </div>
    <?php endif; ?>

    <?php
    $pending_houses_q = "SELECT h.*, u.full_name FROM houses h LEFT JOIN requests r ON r.house_id = h.id LEFT JOIN users u ON h.user_id = u.id WHERE (h.status = 'Pending' OR h.is_approved = 0 OR h.is_approved IS NULL) AND r.id IS NULL ORDER BY h.created_at DESC";
    $pending_res = mysqli_query($conn, $pending_houses_q);
    if($pending_res && mysqli_num_rows($pending_res) > 0): ?>
        <div class="section-title"><i class="fas fa-clock"></i> Pending Listings (without request record)</div>
        <?php while($house = mysqli_fetch_assoc($pending_res)): ?>
            <div class="request-card">
                <div class="req-img">
                    <?php if(!empty($house['image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($house['image']); ?>" alt="Property">
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: 'New Listing'); ?></h3>
                    <div class="req-meta">
                        <span><i class="fas fa-tag"></i> <strong><?php echo htmlspecialchars($house['category']); ?></strong></span>
                        <span><i class="fas fa-money-bill"></i> <strong><?php echo number_format($house['amount']); ?> ETB</strong></span>
                        <span><i class="fas fa-location-dot"></i> Kebele <?php echo htmlspecialchars($house['kebele']); ?></span>
                    </div>
                    <div class="req-meta">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($house['full_name'] ?? 'Unknown'); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($house['created_at'])); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <form action="admin_actions.php" method="POST" style="display:inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="approve_house">
                        <input type="hidden" name="id" value="<?php echo (int)$house['id']; ?>">
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form action="admin_actions.php" method="POST" style="display:inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reject_house">
                        <input type="hidden" name="id" value="<?php echo (int)$house['id']; ?>">
                        <button type="submit" class="btn btn-danger-ghost"><i class="fas fa-times"></i> Reject</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
