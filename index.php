<?php 
include('session_config.php');
session_start();
include('db.php'); 

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
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
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh;display:flex;flex-direction:column}

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
        .user-avatar-wrap{position:relative}
        .user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;cursor:pointer;border:2px solid rgba(255,255,255,.2);transition:all .2s}
        .user-avatar:hover{border-color:rgba(255,255,255,.5);transform:scale(1.05)}
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;width:220px;background:#1e293b;border-radius:12px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 40px rgba(0,0,0,.3);opacity:0;visibility:hidden;transform:translateY(-8px);transition:all .2s;z-index:1001}
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
        .card-grid{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:auto;gap:20px}
        @media(max-width:1200px){.card-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.card-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:600px){.card-grid{grid-template-columns:1fr}}
        .card{background:#fff;border-radius:14px;overflow:hidden;border:1px solid #f1f5f9;transition:all .3s;position:relative}
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
        .card-meta{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f5f9}
        .card-owner{font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:4px}
        .card-actions{display:flex;gap:6px}
        .card-actions a{padding:7px 12px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s}
        .btn-phone{background:rgba(13,148,136,.1);color:#0d9488}
        .btn-phone:hover{background:#0d9488;color:#fff}
        .btn-map{background:rgba(59,130,246,.1);color:#3b82f6}
        .btn-map:hover{background:#3b82f6;color:#fff}
        .phone-hidden{background:#f8f9fa;color:#9ca3af;padding:7px 12px;border-radius:8px;font-size:12px;font-weight:500}
        .video-overlay{position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:0;transition:opacity .3s}
        .card-img:hover .video-overlay{opacity:1}
        .video-play{width:48px;height:48px;background:rgba(255,255,255,.95);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#0f172a;font-size:16px}

        .empty-state{text-align:center;padding:80px 20px;grid-column:1/-1}
        .empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#374151;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px}

        @media(max-width:768px){
            .navbar{padding:12px 16px}
            .search-section{padding:16px}
            .listings{padding:16px}
            .search-form{flex-direction:column}
            .search-form select,.search-form input{width:100%}
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
                    <?php
                    $count_sql = "SELECT COUNT(*) as c FROM houses WHERE status IN ('Available','Rented') AND is_approved = 1";
                    $count_res = mysqli_query($conn, $count_sql);
                    $total = $count_res ? mysqli_fetch_assoc($count_res)['c'] : 0;
                    echo $total . ' listings';
                    ?>
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
        <div class="card-grid">
            <?php
            $sql = "SELECT houses.*, users.full_name FROM houses 
                    LEFT JOIN users ON houses.user_id = users.id 
                    WHERE houses.status IN ('Available', 'Rented') AND houses.is_approved = 1";

            if(!empty($_GET['cat'])) {
                $c = mysqli_real_escape_string($conn, $_GET['cat']);
                $sql .= " AND category = '$c'"; 
            }
            if(!empty($_GET['kb'])) { 
                $k = mysqli_real_escape_string($conn, $_GET['kb']); 
                $sql .= " AND kebele LIKE '%$k%'"; 
            }
            if(!empty($_GET['max_pr'])) { 
                $max = (int)$_GET['max_pr']; 
                $sql .= " AND amount <= $max"; 
            }

            $sort = $_GET['sort'] ?? 'newest';
            if($sort == 'price_low') $sql .= " ORDER BY amount ASC";
            elseif($sort == 'price_high') $sql .= " ORDER BY amount DESC";
            else $sql .= " ORDER BY created_at DESC";

            $res = mysqli_query($conn, $sql);

            if($res && mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) { 
                    $status = $row['status'] ?? 'Available';
                    $badgeClass = ($status == 'Rented') ? 'badge-rented' : 'badge-available'; 
            ?> 
                <div class="card">
                    <div class="card-img">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Property" loading="lazy">
                        <span class="card-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                        <span class="card-category"><?php echo htmlspecialchars($row['category']); ?></span>
                        <?php if(!empty($row['video_file']) && $status == 'Available'): ?>
                            <div class="video-overlay" onclick="startVideo(this, 'uploads/<?php echo htmlspecialchars($row['video_file']); ?>')">
                                <div class="video-play"><i class="fas fa-play"></i></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="card-price"><?php echo number_format($row['amount']); ?> <span>ETB/month</span></div>
                        <div class="card-location">
                            <i class="fas fa-location-dot"></i>
                            Kebele <?php echo htmlspecialchars($row['kebele']); ?>, <?php echo htmlspecialchars($row['street']); ?>
                        </div>
                        <div class="card-desc"><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                        <div class="card-meta">
                            <div class="card-owner"><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['full_name'] ?? 'Private'); ?></div>
                            <div class="card-actions">
                                <?php if($status == 'Available'): ?>
                                    <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="btn-phone"><i class="fas fa-phone"></i> Call</a>
                                <?php else: ?>
                                    <span class="phone-hidden"><i class="fas fa-lock"></i> Rented</span>
                                <?php endif; ?>
                                <?php if(!empty($row['map_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['map_link']); ?>" target="_blank" class="btn-map"><i class="fas fa-map-marker-alt"></i> Map</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                } 
            } else {
                echo '<div class="empty-state"><i class="fas fa-home"></i><h3>No properties found</h3><p>Try adjusting your search filters or check back later.</p></div>';
            }
            ?>
        </div>
    </div>

    <script>
    function startVideo(container, videoSrc) {
        container.innerHTML = '<video width="100%" height="210" controls autoplay style="object-fit:cover;border-radius:14px 14px 0 0"><source src="'+videoSrc+'" type="video/mp4"></video>';
    }
    </script>

    <?php include('footer.php'); ?>
</body>
</html>
