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

function get_house_images($conn, $house_id){
    $house_id = (int)$house_id;
    $imgs = [];
    $fr = mysqli_query($conn, "SELECT image FROM houses WHERE id=$house_id AND image IS NOT NULL AND image <> ''");
    if($fr && ($f = mysqli_fetch_row($fr))) $imgs[] = $f[0];
    $gi = mysqli_query($conn, "SELECT filename FROM house_images WHERE house_id=$house_id ORDER BY sort_order ASC, id ASC");
    if($gi){ while($g = mysqli_fetch_assoc($gi)) $imgs[] = $g['filename']; }
    return array_values(array_unique($imgs));
}

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
    <style>
        .request-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px;margin-bottom:16px;display:flex;gap:22px;align-items:stretch;box-shadow:0 1px 3px rgba(15,23,42,.04);transition:box-shadow .25s ease,transform .25s ease,border-color .25s ease}
        .request-card:hover{border-color:#cbd5e1;box-shadow:0 10px 28px rgba(15,23,42,.08);transform:translateY(-2px)}
        .req-img{flex:0 0 220px;position:relative;border-radius:12px;overflow:hidden;background:#f1f5f9;align-self:center}
        .req-img img{width:220px;height:150px;object-fit:cover;display:block}
        .req-img .img-count{position:absolute;right:8px;bottom:8px;background:rgba(15,23,42,.72);color:#fff;font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;display:inline-flex;align-items:center;gap:5px;backdrop-filter:blur(4px)}
        .req-img .no-img{width:220px;height:150px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:#94a3b8;font-size:13px;font-weight:500}
        .req-img .no-img i{font-size:26px;color:#cbd5e1}
        .req-body{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;padding:4px 0}
        .req-body h3{font-size:16px;font-weight:700;color:#0f172a;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .req-meta{display:flex;flex-wrap:wrap;gap:8px;margin:0}
        .req-meta span{display:inline-flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #eef2f7;color:#475569;font-size:13px;font-weight:500;padding:7px 13px;border-radius:9px}
        .req-meta span i{color:#0d9488;font-size:12px}
        .req-meta strong{color:#0f172a;font-weight:700}
        .req-meta span.chip-price{border-color:rgba(13,148,136,.18);background:rgba(13,148,136,.06);color:#0d9488;font-weight:700}
        .req-meta span.chip-price i{color:#0d9488}
        .req-actions{flex:0 0 auto;display:flex;align-items:center;justify-content:center;padding-left:22px;border-left:1px solid #f1f5f9}
        .req-actions a.btn{background:#0d9488;color:#fff;border-radius:10px;font-weight:600;font-size:13px;padding:10px 22px;justify-content:center;border:none;box-shadow:0 3px 0 rgba(13,148,136,.15)}
        .req-actions a.btn:hover{background:#0f9c8f;box-shadow:0 6px 16px rgba(13,148,136,.3);transform:translateY(-1px)}
        @media(max-width:768px){
            .request-card{flex-direction:column;gap:14px}
            .req-img,.req-img img,.req-img .no-img{width:100%;height:180px;flex:0 0 auto}
            .req-actions{padding-left:0;border-left:none;border-top:1px solid #f1f5f9;padding-top:14px}
        }
    </style>
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
                <?php $photos = get_house_images($conn, $house['id']); $thumb = !empty($photos) ? $photos[0] : ''; $photo_count = count($photos); ?>
                <div class="req-img">
                    <?php if(!empty($thumb)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($thumb); ?>" alt="Property">
                        <?php if($photo_count > 1): ?><span class="img-count"><i class="fas fa-images"></i> <?php echo $photo_count; ?></span><?php endif; ?>
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: ($house['category'] ?? 'New Listing')); ?></h3>
                    <div class="req-meta">
                        <span><i class="fas fa-tag"></i> <strong><?php echo htmlspecialchars($house['category'] ?? ''); ?></strong></span>
                        <span class="chip-price"><i class="fas fa-money-bill"></i> <?php echo number_format($house['amount'] ?? 0); ?> ETB</span>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <a href="admin_review_house.php?id=<?php echo $house['id']; ?>" class="btn"><i class="fas fa-search"></i> Review</a>
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
                <?php $photos = get_house_images($conn, $house['id']); $thumb = !empty($photos) ? $photos[0] : ''; $photo_count = count($photos); ?>
                <div class="req-img">
                    <?php if(!empty($thumb)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($thumb); ?>" alt="Property">
                        <?php if($photo_count > 1): ?><span class="img-count"><i class="fas fa-images"></i> <?php echo $photo_count; ?></span><?php endif; ?>
                    <?php else: ?>
                        <div class="no-img"><i class="fas fa-image"></i> No Image</div>
                    <?php endif; ?>
                </div>
                <div class="req-body">
                    <h3><?php echo htmlspecialchars($house['description'] ?: 'New Listing'); ?></h3>
                    <div class="req-meta">
                        <span><i class="fas fa-tag"></i> <strong><?php echo htmlspecialchars($house['category']); ?></strong></span>
                        <span class="chip-price"><i class="fas fa-money-bill"></i> <?php echo number_format($house['amount']); ?> ETB</span>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($house['full_name'] ?? 'Unknown'); ?></span>
                    </div>
                </div>
                <div class="req-actions">
                    <a href="admin_review_house.php?id=<?php echo $house['id']; ?>" class="btn"><i class="fas fa-search"></i> Review</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
</body>
</html>
