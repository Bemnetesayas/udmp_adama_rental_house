<?php 
include('session_config.php');
session_start();
include('db.php'); 

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

function timeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff/60) . ' min ago';
    if($diff < 86400) return floor($diff/3600) . ' hr ago';
    if($diff < 604800) return floor($diff/86400) . ' d ago';
    return date('M j', strtotime($datetime));
}

$notifs = [];
$unread_count = 0;
if(isset($_SESSION['user_id'])){
    $uid = (int)$_SESSION['user_id'];
    $nq = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC, id DESC LIMIT 10");
    if($nq) $notifs = mysqli_fetch_all($nq, MYSQLI_ASSOC);
    $cq = mysqli_query($conn, "SELECT COUNT(*) c FROM notifications WHERE user_id=$uid AND is_read=0");
    if($cq) $unread_count = (int)mysqli_fetch_assoc($cq)['c'];
}

$LISTING_LIMIT = 8;

function buildListingQuery($conn) {
    $where = "houses.status IN ('Available', 'Rented') AND houses.is_approved = 1";
    if(!empty($_GET['cat'])) {
        $c = mysqli_real_escape_string($conn, $_GET['cat']);
        $where .= " AND houses.category = '$c'";
    }
    if(!empty($_GET['kb'])) {
        $k = mysqli_real_escape_string($conn, $_GET['kb']);
        $where .= " AND houses.kebele LIKE '%$k%'";
    }
    if(!empty($_GET['max_pr'])) {
        $max = (int)$_GET['max_pr'];
        $where .= " AND houses.amount <= $max";
    }
    $sort = $_GET['sort'] ?? 'newest';
    $order = ($sort == 'price_low') ? 'amount ASC' : (($sort == 'price_high') ? 'amount DESC' : 'created_at DESC');
    return [
        'where' => $where,
        'sql'   => "SELECT houses.*, users.full_name FROM houses LEFT JOIN users ON houses.user_id = users.id WHERE $where ORDER BY $order"
    ];
}

function renderPropertyCard($row, $all_amenities, $house_amenities, $house_images) {
    $status = $row['status'] ?? 'Available';
    $badgeClass = ($status == 'Rented') ? 'badge-rented' : 'badge-available';
    $images = $house_images[$row['id']] ?? [];
?>
                <div class="card" data-href="house_detail.php?house=<?php echo (int)$row['id']; ?>">
                    <div class="card-img">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Property" loading="lazy">
                        <span class="card-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                        <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
                    </div>
                    <?php if(!empty($images)): ?>
                    <div class="card-thumbs">
                        <?php foreach($images as $img): ?>
                            <img class="card-thumb" src="uploads/<?php echo htmlspecialchars($img); ?>" alt="Property photo" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="card-price"><?php echo number_format($row['amount']); ?> <span>ETB/month</span></div>
                        <div class="card-location">
                            <i class="fas fa-location-dot"></i>
                            Kebele <?php echo htmlspecialchars($row['kebele']); ?>, <?php echo htmlspecialchars($row['street']); ?>
                        </div>
                        <div class="card-desc"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                        <?php if(!empty($house_amenities[$row['id']])): ?>
                        <div class="card-amenities">
                            <?php foreach($house_amenities[$row['id']] as $aid):
                                if(!isset($all_amenities[$aid])) continue;
                            ?>
                                <span class="card-amenity" title="<?php echo htmlspecialchars($all_amenities[$aid]['name']); ?>"><i class="<?php echo htmlspecialchars($all_amenities[$aid]['icon']); ?>"></i> <?php echo htmlspecialchars($all_amenities[$aid]['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="card-meta">
                            <div class="card-owner"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['full_name'] ?? 'Private'); ?></div>
                        </div>
                    </div>
                </div>
<?php
}

function loadAmenities($conn) {
    $all = [];
    $res = mysqli_query($conn, "SELECT id, name, icon FROM amenities");
    if($res) { while($r = mysqli_fetch_assoc($res)) $all[$r['id']] = $r; }
    $map = [];
    $res2 = mysqli_query($conn, "SELECT house_id, amenity_id FROM house_amenities");
    if($res2) { while($r = mysqli_fetch_assoc($res2)) $map[$r['house_id']][] = $r['amenity_id']; }
    return [$all, $map];
}

function loadHouseImages($conn) {
    $map = [];
    $res = mysqli_query($conn, "SELECT house_id, filename FROM house_images ORDER BY sort_order ASC, id ASC");
    if($res) { while($r = mysqli_fetch_assoc($res)) $map[$r['house_id']][] = $r['filename']; }
    return $map;
}

if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: text/html; charset=UTF-8');
    $q = buildListingQuery($conn);
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : $LISTING_LIMIT;
    list($all_amenities, $house_amenities) = loadAmenities($conn);
    $house_images = loadHouseImages($conn);
    $res = mysqli_query($conn, $q['sql'] . " LIMIT $limit OFFSET $offset");
    $count = 0;
    if($res && mysqli_num_rows($res) > 0) {
        while($row = mysqli_fetch_assoc($res)) {
            renderPropertyCard($row, $all_amenities, $house_amenities, $house_images);
            $count++;
        }
    }
    header('X-Items-Count: ' . $count);
    exit;
}

$q = buildListingQuery($conn);
$total_filtered = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM houses WHERE " . $q['where']))[0];
list($all_amenities, $house_amenities) = loadAmenities($conn);
$house_images = loadHouseImages($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Properties - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-center{display:flex;align-items:center}
        .nav-home-btn{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.75);text-decoration:none;font-size:14px;font-weight:600;padding:9px 18px;border-radius:9px;transition:all .2s;border:1px solid transparent}
        .nav-home-btn:hover{color:#fff;background:rgba(255,255,255,.08)}
        .nav-home-btn.active{color:#fff;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.12)}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{position:relative;color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:background .25s cubic-bezier(.4,0,.2,1),color .25s}
        .nav-right a::after{content:'';position:absolute;left:14px;bottom:5px;width:0;height:2px;border-radius:2px;background:linear-gradient(90deg,#2dd4bf,#14b8a6);transition:width .3s cubic-bezier(.4,0,.2,1)}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right a:hover::after{width:calc(100% - 28px)}
        .nav-right a i{transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
        .nav-right a:hover i{transform:translateX(3px)}
        .nav-right .btn-accent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .nav-right .btn-accent:hover{box-shadow:0 4px 15px rgba(13,148,136,.4);transform:translateY(-1px)}
        .nav-right .btn-accent:hover i{transform:rotate(90deg) scale(1.15)}
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

        /* NOTIFICATION BELL */
        .bell-wrap{position:relative}
        .bell-btn{position:relative;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;transition:background .25s cubic-bezier(.4,0,.2,1),transform .25s}
        .bell-btn:hover{background:rgba(255,255,255,.14);transform:scale(1.08)}
        .bell-btn:hover i{animation:bellShake .5s ease}
        @keyframes bellShake{0%,100%{transform:rotate(0)}20%{transform:rotate(14deg)}40%{transform:rotate(-10deg)}60%{transform:rotate(8deg)}80%{transform:rotate(-4deg)}}
        .bell-btn i{font-size:15px}
        .bell-badge{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;background:#ef4444;color:#fff;border-radius:50%;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid #0f172a}
        .notif-dropdown{position:absolute;top:calc(100% + 10px);right:0;width:340px;max-width:calc(100vw - 32px);background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity .3s cubic-bezier(.34,1.56,.64,1),transform .3s cubic-bezier(.34,1.56,.64,1),visibility .3s;z-index:1002}
        .bell-wrap.open .notif-dropdown{opacity:1;visibility:visible;transform:translateY(0)}
        .notif-header{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between}
        .notif-header h4{color:#f1f5f9;font-size:14px;font-weight:700}
        .notif-header .notif-unread-count{color:#94a3b8;font-size:12px}
        .notif-list{max-height:340px;overflow-y:auto}
        .notif-item{display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.05);text-decoration:none;transition:background .15s}
        .notif-item:hover{background:rgba(255,255,255,.05)}
        .notif-item.unread{background:rgba(13,148,136,.08)}
        .notif-icon{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
        .notif-icon.type-listing{background:linear-gradient(135deg,#3b82f6,#60a5fa)}
        .notif-icon.type-rejection{background:linear-gradient(135deg,#ef4444,#f87171)}
        .notif-text{flex:1;min-width:0}
        .notif-text strong{color:#f1f5f9;font-size:13px;display:block;margin-bottom:2px}
        .notif-text p{color:#94a3b8;font-size:12px;line-height:1.5}
        .notif-text time{color:#64748b;font-size:10px;display:block;margin-top:4px}
        .notif-empty{padding:32px 16px;text-align:center;color:#94a3b8;font-size:13px}
        .notif-empty i{font-size:26px;color:#475569;display:block;margin-bottom:8px}
        .notif-footer{padding:10px 16px;border-top:1px solid rgba(255,255,255,.08)}
        .notif-footer button{width:100%;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2dd4bf;padding:9px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
        .notif-footer button:hover{background:rgba(13,148,136,.25)}

        /* SEARCH */
        .search-section{background:#fff;border-bottom:1px solid #f1f5f9;padding:24px 32px}
        .search-inner{max-width:1200px;margin:0 auto}
        .search-title{display:flex;align-items:center;gap:10px;margin-bottom:16px}
        .search-title h1{font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-.3px}
        .search-title .count{background:#f1f5f9;color:#64748b;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:600}
        .search-form{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .search-form select,.search-form input{padding:11px 16px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:#374151;transition:border-color .2s;min-width:160px}
        .search-form select:focus,.search-form input:focus{outline:none;border-color:#0d9488}
        .search-form .btn-search{padding:11px 24px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .3s}
        .search-form .btn-search:hover{box-shadow:0 4px 15px rgba(13,148,136,.4)}
        .search-form .btn-reset{padding:11px 16px;background:transparent;color:#64748b;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-weight:500;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s}
        .search-form .btn-reset:hover{border-color:#0d9488;color:#0d9488}

        /* GRID */
        .listings{max-width:1200px;margin:0 auto;padding:24px 32px;flex:1}
        .card-grid{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:auto;align-items:start;gap:20px}
        @media(max-width:1200px){.card-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.card-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:600px){.card-grid{grid-template-columns:1fr}}
        .card{background:#fff;border-radius:14px;overflow:hidden;border:1px solid #f1f5f9;transition:all .3s;position:relative;cursor:pointer}
        .card:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(0,0,0,.08);border-color:#e2e8f0}
        .card-img{position:relative;height:210px;overflow:hidden}
        .card-img img{width:100%;height:100%;object-fit:cover;background:#f1f5f9}
        .card-badge{position:absolute;top:12px;left:12px;padding:5px 12px;border-radius:8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;backdrop-filter:blur(8px)}
        .badge-available{background:rgba(16,185,129,.9);color:#fff}
        .badge-rented{background:rgba(239,68,68,.9);color:#fff}
        .card-category{position:absolute;top:12px;right:12px;background:rgba(255,255,255,.92);color:#0f172a;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;backdrop-filter:blur(8px)}
        .card-body{padding:18px}
        .card-price{font-size:22px;font-weight:800;color:#0d9488;margin-bottom:4px}
        .card-price span{font-size:13px;font-weight:500;color:#94a3b8}
        .card-location{display:flex;align-items:center;gap:6px;font-size:13px;color:#64748b;margin-bottom:12px}
        .card-location i{color:#0d9488;font-size:12px}
        .card-desc{font-size:13px;color:#64748b;line-height:1.6;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .card-amenities{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
        .card-amenity{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:500}
        .card-amenity i{color:#0d9488;font-size:10px}
        .card-meta{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f5f9}
        .card-owner{font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:4px}

        /* THUMBNAILS */
        .card-thumbs{display:flex;flex-wrap:wrap;gap:6px;padding:10px 12px 0;background:#fff}
        .card-thumb{width:52px;height:52px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:all .2s;background:#f1f5f9}
        .card-thumb:hover{border-color:#0d9488;transform:scale(1.08)}

        /* LIGHTBOX */
        .lb-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.92);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:24px}
        .lb-overlay.open{display:flex}
        .lb-overlay img{max-width:90vw;max-height:82vh;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
        .lb-close{position:absolute;top:20px;right:24px;background:none;border:none;color:#fff;font-size:30px;cursor:pointer;z-index:10001;transition:transform .2s}
        .lb-close:hover{transform:rotate(90deg)}
        .lb-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);border:none;color:#fff;width:48px;height:48px;border-radius:50%;font-size:20px;cursor:pointer;transition:all .2s;z-index:10001}
        .lb-nav:hover{background:rgba(255,255,255,.25)}
        .lb-prev{left:16px}
        .lb-next{right:16px}

        .empty-state{text-align:center;padding:80px 20px;grid-column:1/-1}
        .empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#374151;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px}

        .load-more-wrap{text-align:center;padding:40px 0}
        .load-more-wrap[hidden]{display:none}
        .load-more-spinner{font-size:24px;color:#0d9488}

        @media(max-width:768px){
            .navbar{padding:12px 16px}
            .search-section{padding:16px}
            .listings{padding:16px}
            .search-form{flex-direction:column}
            .search-form select,.search-form input{width:100%}
        }
        @media(max-width:480px){
            .navbar{padding:10px 12px}
            .nav-right .btn-accent{font-size:0;padding:9px 11px}
            .nav-right .btn-accent i{font-size:15px}
            .nav-brand-text{font-size:16px}
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/pending_invite_notice.php'); ?>
    <nav class="navbar">
        <a href="Home.php" class="nav-brand">
            <div class="nav-brand-icon">AR</div>
            <div class="nav-brand-text">Adama<span>Rent</span></div>
        </a>
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> New Posts</a>
                <div class="bell-wrap">
                    <button class="bell-btn" onclick="toggleNotif(this)" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if($unread_count > 0): ?><span class="bell-badge" id="bellBadge"><?php echo min($unread_count, 99); ?></span><?php endif; ?>
                    </button>
                    <div class="notif-dropdown">
                        <div class="notif-header">
                            <h4>Notifications</h4>
                            <span class="notif-unread-count" id="notifCount"><?php echo $unread_count; ?> unread</span>
                        </div>
                        <div class="notif-list">
                            <?php if(empty($notifs)): ?>
                                <div class="notif-empty"><i class="fas fa-bell-slash"></i>No notifications yet</div>
                            <?php else: foreach($notifs as $n):
                                $nType = $n['type'] === 'listing' ? 'listing' : ($n['type'] === 'rejection' ? 'rejection' : '');
                                $nIcon = $n['type'] === 'rent_request' ? 'fa-hand-holding-heart' : ($n['type'] === 'rejection' ? 'fa-circle-xmark' : 'fa-circle-check');
                                $nLink = $n['link'] ? htmlspecialchars($n['link']) : '#';
                            ?>
                                <a href="<?php echo $nLink; ?>" class="notif-item<?php echo $n['is_read'] ? '' : ' unread'; ?>">
                                    <span class="notif-icon<?php echo $nType ? ' type-' . $nType : ''; ?>"><i class="fas <?php echo $nIcon; ?>"></i></span>
                                    <span class="notif-text">
                                        <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                        <p><?php echo htmlspecialchars($n['message']); ?></p>
                                        <time><?php echo timeAgo($n['created_at']); ?></time>
                                    </span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php if(!empty($notifs)): ?>
                        <div class="notif-footer">
                            <button onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark all as read</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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
            <?php else: ?>
                <a href="login.php"><i class="fas fa-right-to-bracket"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="search-section">
        <div class="search-inner">
            <div class="search-title">
                <h1>Find Properties in Adama</h1>
                <span class="count">
                    <?php echo $total_filtered . ' listings'; ?>
                </span>
            </div>
            <form method="GET" action="index.php" class="search-form">
                <select name="cat">
                    <option value="">All Categories</option>
                    <optgroup label="Residential">
                        <option value="Single Home" <?php if(isset($_GET['cat']) && $_GET['cat']=='Single Home') echo 'selected'; ?>>Single Home</option>
                        <option value="Apartment" <?php if(isset($_GET['cat']) && $_GET['cat']=='Apartment') echo 'selected'; ?>>Apartment</option>
                        <option value="Villa" <?php if(isset($_GET['cat']) && $_GET['cat']=='Villa') echo 'selected'; ?>>Villa</option>
                    </optgroup>
                    <optgroup label="Commercial">
                        <option value="Office" <?php if(isset($_GET['cat']) && $_GET['cat']=='Office') echo 'selected'; ?>>Office</option>
                        <option value="Shop" <?php if(isset($_GET['cat']) && $_GET['cat']=='Shop') echo 'selected'; ?>>Shop</option>
                        <option value="Warehouse" <?php if(isset($_GET['cat']) && $_GET['cat']=='Warehouse') echo 'selected'; ?>>Warehouse</option>
                    </optgroup>
                </select>
                <input type="text" name="kb" placeholder="Search by Kebele..." value="<?php echo isset($_GET['kb']) ? htmlspecialchars($_GET['kb']) : ''; ?>">
                <input type="number" name="max_pr" placeholder="Max Price (ETB)" value="<?php echo isset($_GET['max_pr']) ? htmlspecialchars($_GET['max_pr']) : ''; ?>">
                <select name="sort">
                    <option value="newest" <?php if(isset($_GET['sort']) && $_GET['sort']=='newest') echo 'selected'; ?>>Newest First</option>
                    <option value="price_low" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_low') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_high" <?php if(isset($_GET['sort']) && $_GET['sort']=='price_high') echo 'selected'; ?>>Price: High to Low</option>
                </select>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <a href="index.php" class="btn-reset">Reset</a>
            </form>
        </div>
    </div>

    <div class="listings">
        <div class="card-grid" id="listingGrid">
            <?php
            $offset = 0;
            $res = mysqli_query($conn, $q['sql'] . " LIMIT $LISTING_LIMIT OFFSET $offset");
            $rendered = 0;
            if($res && mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    renderPropertyCard($row, $all_amenities, $house_amenities, $house_images);
                    $rendered++;
                }
            } else {
                echo '<div class="empty-state"><i class="fas fa-home"></i><h3>No properties found</h3><p>Try adjusting your search filters or check back later.</p></div>';
            }
            ?>
        </div>
        <div id="loadMoreWrap" class="load-more-wrap" style="display:none">
            <button id="loadMoreBtn" class="btn-search" style="padding:12px 32px">
                <i class="fas fa-angle-down"></i> Load More
            </button>
            <i id="loadMoreSpinner" class="load-more-spinner fas fa-spinner fa-spin" style="display:none"></i>
        </div>
    </div>

    <div class="lb-overlay" id="lbOverlay">
        <button class="lb-close" id="lbClose" aria-label="Close"><i class="fas fa-xmark"></i></button>
        <button class="lb-nav lb-prev" id="lbPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <img id="lbImg" src="" alt="Property photo">
        <button class="lb-nav lb-next" id="lbNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
    </div>

    <script>
    document.querySelectorAll('.card').forEach(function(card){
        card.addEventListener('click', function(e){
            if(e.target.closest('a')) return;
            var href = card.getAttribute('data-href');
            if(href) window.location = href;
        });
    });

    document.addEventListener('click', function(e){
        var thumb = e.target.closest('.card-thumb');
        if(thumb){
            var thumbs = thumb.parentElement.querySelectorAll('.card-thumb');
            var idx = Array.prototype.indexOf.call(thumbs, thumb);
            openLightbox(thumbs, idx);
        }
    });

    var lbOverlay = document.getElementById('lbOverlay');
    var lbImg = document.getElementById('lbImg');
    var lbGroup = [];
    var lbIndex = 0;

    function openLightbox(thumbs, idx){
        lbGroup = Array.prototype.map.call(thumbs, function(t){ return t.src; });
        lbIndex = idx;
        showLbImage();
        lbOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function showLbImage(){
        lbImg.src = lbGroup[lbIndex] || '';
    }
    function closeLightbox(){
        lbOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    function lbStep(dir){
        if(!lbGroup.length) return;
        lbIndex = (lbIndex + dir + lbGroup.length) % lbGroup.length;
        showLbImage();
    }
    lbOverlay.addEventListener('click', function(e){
        if(e.target === lbOverlay || e.target.closest('.lb-close')) closeLightbox();
        if(e.target.closest('.lb-prev')) lbStep(-1);
        if(e.target.closest('.lb-next')) lbStep(1);
    });
    document.addEventListener('keydown', function(e){
        if(!lbOverlay.classList.contains('open')) return;
        if(e.key === 'Escape') closeLightbox();
        if(e.key === 'ArrowLeft') lbStep(-1);
        if(e.key === 'ArrowRight') lbStep(1);
    });

    function toggleNotif(btn){
        var wrap = btn.closest('.bell-wrap');
        var isOpen = wrap.classList.contains('open');
        document.querySelectorAll('.bell-wrap.open').forEach(function(w){ w.classList.remove('open'); });
        if(!isOpen) wrap.classList.add('open');
    }
    document.addEventListener('click', function(e){
        if(!e.target.closest('.bell-wrap')) document.querySelectorAll('.bell-wrap.open').forEach(function(w){ w.classList.remove('open'); });
    });

    function markAllRead(){
        fetch('mark_notifications_read.php', {method: 'POST'}).then(function(){
            var badge = document.getElementById('bellBadge');
            if(badge) badge.remove();
            var count = document.getElementById('notifCount');
            if(count) count.textContent = '0 unread';
            document.querySelectorAll('.notif-item.unread').forEach(function(i){ i.classList.remove('unread'); });
        });
    }

    (function(){
        var grid = document.getElementById('listingGrid');
        var wrap = document.getElementById('loadMoreWrap');
        var btn = document.getElementById('loadMoreBtn');
        var spinner = document.getElementById('loadMoreSpinner');
        if(!grid || !wrap || !btn) return;
        var total = <?php echo (int)$total_filtered; ?>;
        var limit = <?php echo (int)$LISTING_LIMIT; ?>;
        var loaded = <?php echo (int)$rendered; ?>;
        if(loaded >= total) return;
        wrap.style.display = 'block';
        var isLoading = false;
        var params = new URLSearchParams(window.location.search);
        btn.addEventListener('click', function(){
            if(isLoading || loaded >= total) return;
            isLoading = true;
            btn.style.display = 'none';
            spinner.style.display = 'inline-block';
            params.set('ajax', '1');
            params.set('offset', loaded);
            params.set('limit', limit);
            fetch('index.php?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function(res){ return res.text(); })
                .then(function(html){
                    grid.insertAdjacentHTML('beforeend', html);
                    loaded = parseInt(grid.querySelectorAll('.card').length, 10);
                    isLoading = false;
                    spinner.style.display = 'none';
                    if(loaded >= total){
                        wrap.style.display = 'none';
                    } else {
                        btn.style.display = 'inline-block';
                    }
                })
                .catch(function(){
                    isLoading = false;
                    spinner.style.display = 'none';
                    btn.style.display = 'inline-block';
                });
        });
    })();
    </script>

    <?php include('footer.php'); ?>
</body>
</html>
