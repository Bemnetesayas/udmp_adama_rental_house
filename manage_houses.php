<?php 
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status='Rented' THEN 1 ELSE 0 END) as rented,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending
    FROM houses WHERE user_id = $current_user"));

$rental_reqs = [];
$rq = mysqli_query($conn, "
    SELECT rr.id, rr.status AS req_status, rr.created_at, rr.message,
           h.kebele, h.street, h.amount, h.category, u.full_name, u.email
    FROM rental_requests rr
    JOIN houses h ON rr.house_id = h.id
    LEFT JOIN users u ON rr.user_id = u.id
    WHERE h.user_id = $current_user
    ORDER BY CASE rr.status WHEN 'pending' THEN 0 ELSE 1 END, rr.created_at DESC
    LIMIT 50");
if($rq) $rental_reqs = mysqli_fetch_all($rq, MYSQLI_ASSOC);
$pending_req_count = 0;
foreach($rental_reqs as $r){ if($r['req_status'] === 'pending') $pending_req_count++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{position:relative;color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:background .25s cubic-bezier(.4,0,.2,1),color .25s}
        .nav-right a::after{content:'';position:absolute;left:14px;bottom:5px;width:0;height:2px;border-radius:2px;background:linear-gradient(90deg,#2dd4bf,#14b8a6);transition:width .3s cubic-bezier(.4,0,.2,1)}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right a:hover::after{width:calc(100% - 28px)}
        .nav-right a i{transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
        .nav-right a:hover i{transform:translateX(3px)}
        .nav-right .btn-post{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .nav-right .btn-post:hover{box-shadow:0 4px 15px rgba(13,148,136,.4);transform:translateY(-1px)}
        .nav-right .btn-post:hover i{transform:rotate(90deg) scale(1.15)}
        .user-avatar-wrap{position:relative}
        .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;cursor:pointer;border:2px solid rgba(255,255,255,.2);transition:all .2s}
        .user-avatar:hover{border-color:rgba(255,255,255,.5);transform:scale(1.05)}
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:220px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity .3s cubic-bezier(.34,1.56,.64,1),transform .3s cubic-bezier(.34,1.56,.64,1),visibility .3s;z-index:1001}
        .user-avatar-wrap:hover .user-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
        .user-dropdown-header{padding:16px;display:flex;align-items:center;gap:10px}
        .user-avatar-sm{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
        .user-dropdown-name{color:#f1f5f9;font-size:13px;font-weight:600}
        .user-dropdown-role{color:#94a3b8;font-size:11px}
        .user-dropdown-divider{height:1px;background:rgba(255,255,255,.08)}
        .user-dropdown a{display:flex;align-items:center;gap:8px;padding:10px 16px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:all .15s}
        .user-dropdown a:hover{background:rgba(255,255,255,.05);color:#fff}
        .user-dropdown a.logout{color:#f87171;border-top:1px solid rgba(255,255,255,.08)}
        .user-dropdown a.logout:hover{background:rgba(248,113,113,.1);color:#fca5a5}

        .dashboard{max-width:1200px;margin:0 auto;padding:32px}

        /* STATS */
        .welcome{margin-bottom:28px}
        .welcome h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .welcome p{color:#64748b;font-size:14px;margin-top:4px}
        .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px}
        .stat-box{background:#fff;border-radius:12px;padding:20px;border:1px solid #f1f5f9;transition:all .3s}
        .stat-box:hover{border-color:#e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.04)}
        .stat-box .stat-label{font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
        .stat-box .stat-number{font-size:28px;font-weight:800}
        .stat-box:nth-child(1) .stat-number{color:#0f172a}
        .stat-box:nth-child(2) .stat-number{color:#0d9488}
        .stat-box:nth-child(3) .stat-number{color:#ef4444}
        .stat-box:nth-child(4) .stat-number{color:#f59e0b}

        /* CARDS */
        .section-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
        .section-title h2{font-size:18px;font-weight:700;color:#0f172a}
        .card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
        .card{background:#fff;border-radius:14px;overflow:hidden;border:1px solid #f1f5f9;transition:all .3s}
        .card:hover{border-color:#e2e8f0;box-shadow:0 8px 25px rgba(0,0,0,.06)}
        .card-img{position:relative;height:200px;overflow:hidden}
        .card-img img{width:100%;height:100%;object-fit:cover;background:#f1f5f9}
        .card-badge{position:absolute;top:12px;left:12px;padding:5px 12px;border-radius:8px;font-size:11px;font-weight:700;text-transform:uppercase}
        .badge-available{background:rgba(16,185,129,.9);color:#fff}
        .badge-rented{background:rgba(239,68,68,.9);color:#fff}
        .badge-pending{background:rgba(245,158,11,.9);color:#fff}
        .card-body{padding:18px}
        .card-info{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
        .card-category{background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;text-transform:uppercase}
        .card-price{font-size:20px;font-weight:800;color:#0d9488}
        .card-location{font-size:13px;color:#64748b;margin-bottom:16px;display:flex;align-items:center;gap:6px}
        .card-location i{color:#0d9488;font-size:12px}
        .card-actions{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
        .card-actions a,.card-actions button{padding:10px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .2s;text-align:center;font-family:inherit}
        .btn-toggle{background:#f1f5f9;color:#475569}
        .btn-toggle:hover{background:#e2e8f0}
        .btn-disabled{background:#f8fafc;color:#94a3b8;cursor:not-allowed;opacity:.7}
        .btn-edit{background:rgba(245,158,11,.1);color:#d97706}
        .btn-edit:hover{background:#f59e0b;color:#fff}
        .btn-delete{background:rgba(239,68,68,.08);color:#dc2626;border:1px solid rgba(239,68,68,.2)}
        .btn-delete:hover{background:#ef4444;color:#fff}

        /* RENTAL REQUESTS */
        .req-list{display:flex;flex-direction:column;gap:12px;margin-bottom:32px}
        .req-item{background:#fff;border:1px solid #f1f5f9;border-radius:12px;padding:16px;display:flex;align-items:center;gap:16px;transition:all .3s}
        .req-item:hover{border-color:#e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.04)}
        .req-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0}
        .req-info{flex:1;min-width:0}
        .req-info .req-tenant{font-size:14px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .req-info .req-prop{font-size:13px;color:#64748b;margin-top:3px}
        .req-info .req-prop i{color:#0d9488;font-size:11px;margin-right:3px}
        .req-info .req-time{font-size:11px;color:#94a3b8;margin-top:4px}
        .req-status{padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase}
        .req-status.pending{background:rgba(245,158,11,.12);color:#d97706}
        .req-status.accepted{background:rgba(16,185,129,.12);color:#059669}
        .req-status.rejected{background:rgba(239,68,68,.12);color:#dc2626}
        .req-status.completed{background:rgba(100,116,139,.12);color:#64748b}
        .req-actions{display:flex;gap:8px;flex-shrink:0}
        .req-actions a{padding:9px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;transition:all .2s;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
        .btn-accept{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
        .btn-accept:hover{box-shadow:0 4px 15px rgba(16,185,129,.4);transform:translateY(-1px)}
        .btn-reject{background:rgba(239,68,68,.08);color:#dc2626;border:1px solid rgba(239,68,68,.2)}
        .btn-reject:hover{background:#ef4444;color:#fff}
        .req-empty{background:#fff;border:1px dashed #e2e8f0;border-radius:12px;padding:32px;text-align:center;color:#94a3b8;font-size:14px;margin-bottom:32px}
        .req-empty i{font-size:24px;display:block;color:#cbd5e1;margin-bottom:8px}
        @media(max-width:600px){
            .req-item{flex-direction:column;align-items:flex-start}
            .req-actions{width:100%}
            .req-actions a{flex:1;justify-content:center}
        }

        .flash{margin-bottom:20px;padding:13px 18px;border-radius:10px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px}
        .flash-ok{background:rgba(16,185,129,.1);color:#059669;border:1px solid rgba(16,185,129,.3)}
        .flash-err{background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.3)}

        .empty-state{text-align:center;padding:80px 20px;background:#fff;border-radius:14px;border:1px solid #f1f5f9;grid-column:1/-1}
        .empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#374151;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px;margin-bottom:20px}
        .empty-state a{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:12px;transition:all .3s}
        .empty-state a:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}

        @media(max-width:768px){
            .dashboard{padding:16px}
            .stats-row{grid-template-columns:repeat(2,1fr)}
            .card-grid{grid-template-columns:1fr}
            .card-actions{grid-template-columns:1fr}
        }
        @media(max-width:480px){
            .navbar{padding:10px 12px}
            .nav-right .btn-post{font-size:0;padding:9px 11px}
            .nav-right .btn-post i{font-size:15px}
            .nav-brand-text{font-size:16px}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="Home.php" class="nav-brand">
            <div class="nav-brand-icon">AR</div>
            <div class="nav-brand-text">Adama<span>Rent</span></div>
        </a>
        <div class="nav-right">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="post_house.php" class="btn-post"><i class="fas fa-plus"></i> New Posts</a>
            <div class="user-avatar-wrap">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></div>
                <div class="user-dropdown">
                    <div class="user-dropdown-header">
                        <div class="user-avatar-sm"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></div>
                        <div><div class="user-dropdown-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                        <div class="user-dropdown-role"><?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1 ? 'Admin' : 'Landlord'; ?></div></div>
                    </div>
                    <div class="user-dropdown-divider"></div>
                    <a href="manage_houses.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="welcome">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <p>Manage your property listings from your personal dashboard</p>
        </div>

        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-label">Total Listings</div>
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Available</div>
                <div class="stat-number"><?php echo $stats['available'] ?? 0; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Rented</div>
                <div class="stat-number"><?php echo $stats['rented'] ?? 0; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Pending</div>
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
        </div>

        <div class="section-title">
            <h2>Rental Requests<?php if($pending_req_count > 0): ?><span class="card-category" style="background:rgba(245,158,11,.15);color:#d97706;margin-left:8px"><?php echo $pending_req_count; ?> pending</span><?php endif; ?></h2>
        </div>

        <?php if(isset($_GET['msg'])):
            if($_GET['msg'] == 'status_saved'): ?>
                <div class="flash flash-ok"><i class="fas fa-check-circle"></i> Listing status updated.</div>
            <?php elseif($_GET['msg'] == 'accepted'): ?>
                <div class="flash flash-ok"><i class="fas fa-check-circle"></i> Rental request accepted. The tenant has been notified and the property is marked as rented.</div>
            <?php elseif($_GET['msg'] == 'rejected'): ?>
                <div class="flash flash-err"><i class="fas fa-circle-xmark"></i> Rental request declined. The tenant has been notified.</div>
            <?php endif;
        endif; ?>

        <?php if(empty($rental_reqs)): ?>
            <div class="req-empty"><i class="fas fa-hand-holding-heart"></i>No rental requests yet. When someone wants to rent your property, their request will appear here.</div>
        <?php else: ?>
            <div class="req-list">
                <?php foreach($rental_reqs as $r): ?>
                    <div class="req-item">
                        <div class="req-avatar"><?php echo strtoupper(substr($r['full_name'] ?? 'T', 0, 1)); ?></div>
                        <div class="req-info">
                            <div class="req-tenant">
                                <?php echo htmlspecialchars($r['full_name'] ?? 'Tenant'); ?>
                                <span class="req-status <?php echo htmlspecialchars($r['req_status']); ?>"><?php echo htmlspecialchars($r['req_status']); ?></span>
                            </div>
                            <div class="req-prop"><i class="fas fa-location-dot"></i><?php echo htmlspecialchars($r['category']); ?> in Kebele <?php echo htmlspecialchars($r['kebele']); ?>, <?php echo htmlspecialchars($r['street']); ?> &middot; <?php echo number_format($r['amount']); ?> ETB</div>
                            <div class="req-time"><?php echo date('M j, g:i a', strtotime($r['created_at'])); ?></div>
                        </div>
                        <?php if($r['req_status'] === 'pending'): ?>
                            <div class="req-actions">
                                <a href="javascript:void(0)" onclick="confirmAccept(<?php echo $r['id']; ?>)" class="btn-accept"><i class="fas fa-check"></i> Accept</a>
                                <a href="javascript:void(0)" onclick="confirmReject(<?php echo $r['id']; ?>)" class="btn-reject"><i class="fas fa-xmark"></i> Decline</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-title">
            <h2>Your Listings</h2>
        </div>

        <div class="card-grid">
            <?php
            $query = "SELECT * FROM houses WHERE user_id = $current_user ORDER BY id DESC";
            $result = mysqli_query($conn, $query);

            if($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $status = $row['status'] ?? 'Available';
                    $badgeClass = 'badge-available';
                    if(strcasecmp($status,'Rented')===0) $badgeClass = 'badge-rented';
                    elseif(strcasecmp($status,'Pending')===0) $badgeClass = 'badge-pending';
            ?>
                <div class="card">
                    <div class="card-img">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Property" loading="lazy">
                        <span class="card-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="card-info">
                            <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
                            <div class="card-price"><?php echo number_format($row['amount']); ?> ETB</div>
                        </div>
                        <div class="card-location">
                            <i class="fas fa-location-dot"></i>
                            Kebele <?php echo htmlspecialchars($row['kebele']); ?>, <?php echo htmlspecialchars($row['street']); ?>
                        </div>
                        <div class="card-actions">
                            <?php if($status === 'Available' || strcasecmp($status,'Rented')===0): ?>
                                <a href="toggle_status.php?id=<?php echo (int)$row['id']; ?>" class="btn-toggle">
                                    <i class="fas fa-sync-alt"></i> <?php echo ($status=='Available') ? 'Mark Rented' : 'Mark Available'; ?>
                                </a>
                            <?php elseif(strcasecmp($status,'Pending')===0): ?>
                                <span class="btn-toggle btn-disabled"><i class="fas fa-clock"></i> Awaiting Approval</span>
                            <?php else: ?>
                                <span class="btn-toggle btn-disabled"><i class="fas fa-ban"></i> Not Available</span>
                            <?php endif; ?>
                            <a href="edit_house.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="delete.php" method="POST" id="del-myhouse-<?php echo $row['id']; ?>" style="display:contents">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="delete_btn" value="1">
                                <button type="button" class="btn-delete" onclick="confirmMyListingDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo '<div class="empty-state"><i class="fas fa-home"></i><h3>No listings yet</h3><p>Start listing your properties and reach potential tenants.</p><a href="post_house.php"><i class="fas fa-plus"></i> Post Your First House</a></div>';
            }
            ?>
        </div>
    </div>

    <?php include(__DIR__ . '/popup.php'); ?>
    <script>
    function confirmAccept(id){
        adamaConfirm({
            title: "Accept rental request",
            message: "Accept this rental request? The property will be marked as rented.",
            confirmText: "Accept",
            onConfirm: function(){ window.location = "rental_request_action.php?action=accept&id=" + id; }
        });
    }
    function confirmReject(id){
        adamaConfirm({
            title: "Decline rental request",
            message: "Decline this rental request?",
            confirmText: "Decline",
            onConfirm: function(){ window.location = "rental_request_action.php?action=reject&id=" + id; }
        });
    }
    function confirmMyListingDelete(id){
        adamaConfirm({
            title: "Delete listing",
            message: "Delete this listing permanently? This cannot be undone.",
            confirmText: "Delete",
            onConfirm: function(){ document.getElementById('del-myhouse-' + id).submit(); }
        });
    }
    </script>

    <?php include('footer.php'); ?>
</body>
</html>
