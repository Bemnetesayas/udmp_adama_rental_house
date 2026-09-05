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
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}

        /* NAVBAR */
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 20px rgba(0,0,0,.15)}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
        .nav-right .btn-post{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
        .nav-right .btn-post:hover{box-shadow:0 4px 15px rgba(13,148,136,.4)}
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
        .btn-edit{background:rgba(245,158,11,.1);color:#d97706}
        .btn-edit:hover{background:#f59e0b;color:#fff}
        .btn-delete{background:rgba(239,68,68,.08);color:#dc2626;border:1px solid rgba(239,68,68,.2)}
        .btn-delete:hover{background:#ef4444;color:#fff}

        .empty-state{text-align:center;padding:80px 20px;background:#fff;border-radius:14px;border:1px solid #f1f5f9;grid-column:1/-1}
        .empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
        .empty-state h3{font-size:18px;font-weight:700;color:#374151;margin-bottom:8px}
        .empty-state p{color:#64748b;font-size:14px;margin-bottom:20px}
        .empty-state a{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;transition:all .3s}
        .empty-state a:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}

        @media(max-width:768px){
            .dashboard{padding:16px}
            .stats-row{grid-template-columns:repeat(2,1fr)}
            .card-grid{grid-template-columns:1fr}
            .card-actions{grid-template-columns:1fr}
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
                            <a href="toggle_status.php?id=<?php echo $row['id']; ?>" class="btn-toggle">
                                <i class="fas fa-sync-alt"></i> <?php echo ($status=='Available') ? 'Mark Rented' : 'Mark Available'; ?>
                            </a>
                            <a href="edit_house.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="delete.php" method="POST" onsubmit="return confirm('Permanently delete this listing?')" style="display:contents">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_btn" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
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

    <?php include('footer.php'); ?>
</body>
</html>
