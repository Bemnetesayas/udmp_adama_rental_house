<?php
include('session_config.php');
session_start();
include('db.php');

function dTimeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff/60) . ' min ago';
    if($diff < 86400) return floor($diff/3600) . ' hr ago';
    if($diff < 604800) return floor($diff/86400) . ' d ago';
    return date('M j, Y', strtotime($datetime));
}

$id = (int)($_GET['house'] ?? 0);
$house = null;

if($id > 0){
    $q = mysqli_query($conn, "SELECT h.*, u.full_name FROM houses h
                              LEFT JOIN users u ON h.user_id = u.id
                              WHERE h.id=$id AND h.is_approved=1
                              AND h.status IN ('Available','Rented')");
    $house = $q ? mysqli_fetch_assoc($q) : null;
}

$notFound = !$house;
$photos = [];
if($house){
    if(!empty($house['image'])) $photos[] = $house['image'];
    $gq = mysqli_query($conn, "SELECT filename FROM house_images WHERE house_id=$id ORDER BY sort_order ASC, id ASC");
    if($gq){
        while($g = mysqli_fetch_assoc($gq)){
            if(!empty($g['filename'])) $photos[] = $g['filename'];
        }
    }
    $photos = array_values(array_unique($photos));
}

$status    = $house['status'] ?? '';
$isAvail   = ($status === 'Available');
$rentHref  = isset($_SESSION['user_id'])
    ? 'rent_request.php?house=' . $id
    : 'login.php?redirect=' . urlencode('rent_request.php?house=' . $id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $notFound ? 'Listing Not Found' : 'Property in Kebele ' . htmlspecialchars($house['kebele']) . ' - AdamaRent'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right .btn-accent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .nav-right .btn-accent:hover{box-shadow:0 4px 15px rgba(13,148,136,.4)}

        /* LAYOUT */
        .page{max-width:1150px;margin:0 auto;width:100%;padding:20px 24px 60px;flex:1}

        .notfound{max-width:560px;margin:80px auto;text-align:center;background:#fff;border-radius:20px;padding:56px 40px;border:1px solid #f1f5f9}
        .notfound i{font-size:54px;color:#94a3b8;margin-bottom:18px;display:block}
        .notfound h1{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:10px}
        .notfound p{color:#64748b;font-size:14px;line-height:1.7;margin-bottom:24px}
        .notfound a{display:inline-flex;align-items:center;gap:8px;padding:13px 26px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:11px;text-decoration:none;font-weight:700;font-size:14px;transition:all .3s}
        .notfound a:hover{box-shadow:0 6px 20px rgba(13,148,136,.4);transform:translateY(-1px)}

        .layout{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}

        /* GALLERY */
        .gallery{background:#fff;border-radius:18px;overflow:hidden;border:1px solid #f1f5f9;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .g-main{position:relative;height:440px;background:#0f172a}
        .g-main img{width:100%;height:100%;object-fit:cover}
        .g-nav{position:absolute;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.92);border:none;color:#0f172a;font-size:14px;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25);transition:all .2s;display:flex;align-items:center;justify-content:center}
        .g-nav:hover{background:#fff;transform:translateY(-50%) scale(1.08)}
        .g-nav.prev{left:14px}
        .g-nav.next{right:14px}
        .g-count{position:absolute;bottom:14px;right:14px;background:rgba(15,23,42,.75);color:#fff;font-size:12px;font-weight:600;padding:6px 12px;border-radius:50px;backdrop-filter:blur(6px)}
        .g-thumbs{display:flex;gap:10px;padding:12px;overflow-x:auto}
        .g-thumbs img{width:86px;height:64px;object-fit:cover;border-radius:9px;border:2px solid transparent;cursor:pointer;transition:all .2s;flex-shrink:0}
        .g-thumbs img:hover{transform:translateY(-2px)}
        .g-thumbs img.on{border-color:#0d9488;box-shadow:0 3px 10px rgba(13,148,136,.3)}

        /* INFO PANEL */
        .info{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:28px;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .info-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
        .chip{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
        .chip-avail{background:rgba(16,185,129,.12);color:#059669}
        .chip-rented{background:rgba(239,68,68,.12);color:#dc2626}
        .chip-cat{background:#f1f5f9;color:#475569}
        .info-price{font-size:34px;font-weight:800;color:#0d9488;letter-spacing:-1px;margin-bottom:2px}
        .info-price span{font-size:15px;font-weight:500;color:#94a3b8;letter-spacing:0}
        .info-loc{display:flex;align-items:center;gap:8px;color:#64748b;font-size:14px;margin-bottom:22px}
        .info-loc i{color:#0d9488}
        .info-title{font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0 0 10px;padding-bottom:10px;border-bottom:1px solid #f1f5f9}
        .info-desc{font-size:14px;color:#475569;line-height:1.75;margin-bottom:22px;white-space:pre-line}

        .facts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:22px}
        .fact{background:#f8fafc;border:1px solid #f1f5f9;border-radius:11px;padding:12px 14px}
        .fact .k{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
        .fact .v{font-size:14px;color:#0f172a;font-weight:700;margin-top:3px;word-break:break-word}

        .actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
        .btn-action{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px;border-radius:11px;font-size:14px;font-weight:700;text-decoration:none;transition:all .25s;border:none;cursor:pointer;font-family:inherit}
        .btn-rent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;grid-column:1/-1}
        .btn-rent:hover{box-shadow:0 6px 20px rgba(13,148,136,.45);transform:translateY(-1px)}
        .btn-call{background:rgba(13,148,136,.1);color:#0d9488}
        .btn-call:hover{background:#0d9488;color:#fff}
        .btn-map{background:rgba(59,130,246,.1);color:#3b82f6}
        .btn-map:hover{background:#3b82f6;color:#fff}
        .rented-note{grid-column:1/-1;background:#f8faf9;border:1px dashed #e2e8f0;color:#94a3b8;border-radius:11px;padding:14px;text-align:center;font-size:13px;font-weight:600}

        /* OWNER CARD */
        .owner{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:14px}
        .owner-av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;flex-shrink:0}
        .owner-name{font-size:14px;font-weight:700;color:#0f172a}
        .owner-sub{font-size:12px;color:#94a3b8;margin-top:2px}

        /* SUMMARY CARD */
        .summary{background:#fff;border-radius:18px;border:1px solid #f1f5f9;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,.04)}
        .summary h3{font-size:15px;font-weight:800;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:8px}
        .summary h3 i{color:#0d9488}
        .summary ul{list-style:none}
        .summary li{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #f8fafc;font-size:13px}
        .summary li:last-child{border-bottom:none}
        .summary li span{color:#94a3b8}
        .summary li strong{color:#334155;font-weight:600;text-align:right}

        @media(max-width:860px){
            .layout{grid-template-columns:1fr}
            .g-main{height:300px}
        }
        @media(max-width:560px){
            .navbar{padding:12px 16px}
            .page{padding:16px;padding-bottom:48px}
            .info{padding:20px}
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
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> New Posts</a>
                <a href="manage_houses.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-right-to-bracket"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if($notFound): ?>
        <div class="notfound">
            <i class="fas fa-house-circle-xmark"></i>
            <h1>Listing unavailable</h1>
            <p>This property is either pending approval, rented, or no longer listed. Browse other available properties in Adama.</p>
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Listings</a>
        </div>
    <?php else: ?>
    <div class="page">
        <div class="layout">
            <!-- GALLERY -->
            <div class="gallery">
                <div class="g-main">
                    <img id="mainImg" src="uploads/<?php echo htmlspecialchars($photos[0]); ?>" alt="Property photo">
                    <?php if(count($photos) > 1): ?>
                        <button class="g-nav prev" onclick="stepPhoto(-1)" aria-label="Previous photo"><i class="fas fa-chevron-left"></i></button>
                        <button class="g-nav next" onclick="stepPhoto(1)" aria-label="Next photo"><i class="fas fa-chevron-right"></i></button>
                        <span class="g-count" id="photoCount">1 / <?php echo count($photos); ?></span>
                    <?php endif; ?>
                </div>
                <?php if(count($photos) > 1): ?>
                <div class="g-thumbs">
                    <?php foreach($photos as $i => $p): ?>
                        <img src="uploads/<?php echo htmlspecialchars($p); ?>" class="<?php echo $i === 0 ? 'on' : ''; ?>" data-idx="<?php echo $i; ?>" onclick="gotoPhoto(<?php echo $i; ?>)" alt="Thumbnail">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- INFO -->
            <div class="info">
                <div class="info-head">
                    <span class="chip <?php echo $isAvail ? 'chip-avail' : 'chip-rented'; ?>"><i class="fas fa-circle" style="font-size:6px"></i> <?php echo htmlspecialchars($status); ?></span>
                    <span class="chip chip-cat"><?php echo htmlspecialchars($house['category']); ?></span>
                </div>
                <div class="info-price"><?php echo number_format((int)$house['amount']); ?> <span>ETB / month</span></div>
                <div class="info-loc"><i class="fas fa-location-dot"></i> Kebele <?php echo htmlspecialchars($house['kebele']); ?><?php echo !empty($house['street']) ? ', ' . htmlspecialchars($house['street']) : ''; ?></div>

                <?php if(!empty($house['description'])): ?>
                    <div class="info-title">About this property</div>
                    <p class="info-desc"><?php echo nl2br(htmlspecialchars($house['description'])); ?></p>
                <?php endif; ?>

                <div class="info-title">Quick facts</div>
                <div class="facts">
                    <div class="fact"><div class="k">Kebele</div><div class="v"><?php echo htmlspecialchars($house['kebele']); ?></div></div>
                    <div class="fact"><div class="k">Street</div><div class="v"><?php echo htmlspecialchars($house['street']); ?></div></div>
                    <?php if(!empty($house['house_number'])): ?>
                        <div class="fact"><div class="k">House No.</div><div class="v"><?php echo htmlspecialchars($house['house_number']); ?></div></div>
                    <?php endif; ?>
                    <div class="fact"><div class="k">Category</div><div class="v"><?php echo htmlspecialchars($house['category']); ?></div></div>
                </div>

                <div class="actions">
                    <?php if($isAvail): ?>
                        <a href="<?php echo htmlspecialchars($rentHref); ?>" class="btn-action btn-rent"><i class="fas fa-hand-holding-heart"></i> Request to Rent</a>
                        <a href="tel:<?php echo htmlspecialchars($house['phone']); ?>" class="btn-action btn-call"><i class="fas fa-phone"></i> Call Owner</a>
                    <?php else: ?>
                        <div class="rented-note"><i class="fas fa-lock"></i> This property is currently rented and cannot be reserved.</div>
                    <?php endif; ?>
                    <?php if(!empty($house['map_link'])): ?>
                        <a href="<?php echo htmlspecialchars($house['map_link']); ?>" target="_blank" rel="noopener" class="btn-action btn-map"><i class="fas fa-map-location-dot"></i> View on Map</a>
                    <?php endif; ?>
                </div>

                <div class="owner">
                    <div class="owner-av"><?php echo strtoupper(substr($house['full_name'] ?? 'O', 0, 1)); ?></div>
                    <div>
                        <div class="owner-name"><?php echo htmlspecialchars($house['full_name'] ?? 'Property Owner'); ?></div>
                        <div class="owner-sub"><i class="fas fa-clock" style="margin-right:4px"></i>Listed <?php echo dTimeAgo($house['created_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <script>
        <?php if(!$notFound && count($photos) > 1): ?>
        var gallery = <?php echo json_encode($photos); ?>;
        var gIdx = 0;
        function renderPhoto(){
            document.getElementById('mainImg').src = 'uploads/' + gallery[gIdx];
            document.getElementById('photoCount').textContent = (gIdx + 1) + ' / ' + gallery.length;
            document.querySelectorAll('.g-thumbs img').forEach(function(t){
                t.classList.toggle('on', parseInt(t.getAttribute('data-idx'), 10) === gIdx);
            });
        }
        function stepPhoto(dir){
            gIdx = (gIdx + dir + gallery.length) % gallery.length;
            renderPhoto();
        }
        function gotoPhoto(i){
            gIdx = i;
            renderPhoto();
        }
        <?php endif; ?>
    </script>
</body>
</html>