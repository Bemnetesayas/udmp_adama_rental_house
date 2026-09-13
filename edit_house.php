<?php 
include('session_config.php');
session_start();
include('db.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$house_id = (int)$_GET['id'];

$query = mysqli_query($conn, "SELECT * FROM houses WHERE id = $house_id AND user_id = $user_id");
$data = mysqli_fetch_assoc($query);

if(!$data){
    die("Error: Listing not found or you do not have permission to edit it.");
}

if(isset($_POST['update'])){
    $kebele = mysqli_real_escape_string($conn, $_POST['kebele']);
    $amount = (int)$_POST['amount'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);

    $imgName = $data['image']; 
    if(!empty($_FILES['house_image']['name'])){
        $imgName = time() . "_" . $_FILES['house_image']['name'];
        move_uploaded_file($_FILES['house_image']['tmp_name'], "uploads/" . $imgName);
    }

    $update_sql = "UPDATE houses SET kebele='$kebele', amount='$amount', phone='$phone', description='$desc', image='$imgName' 
                   WHERE id=$house_id AND user_id=$user_id";
    
    if(mysqli_query($conn, $update_sql)){
        $updated = true;
    } else {
        $update_error = true;
    }
}

if(!empty($updated)):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body style="margin:0;background:#f8fafc;font-family:'Inter',sans-serif">
    <?php include(__DIR__ . '/popup.php'); ?>
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            showToast("Listing updated successfully!", "success", "Changes saved");
            setTimeout(function(){ window.location = "manage_houses.php"; }, 1800);
        });
    </script>
</body>
</html>
<?php
exit();
endif;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}
        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}
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
        .form-page{max-width:580px;margin:40px auto;padding:0 20px}
        .form-header{margin-bottom:24px}
        .form-header h1{font-size:24px;font-weight:800;color:#0f172a}
        .form-header p{color:#64748b;font-size:14px;margin-top:4px}
        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group input,.form-group textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus,.form-group textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .form-group textarea{resize:vertical;min-height:80px}
        .current-img{margin:12px 0}
        .current-img img{width:100px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #f1f5f9}
        .btn-row{display:flex;gap:10px;margin-top:24px}
        .btn-save{flex:1;padding:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s}
        .btn-save:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(13,148,136,.4)}
        .btn-cancel{padding:12px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-cancel:hover{background:#e2e8f0}
        @media(max-width:480px){
            .navbar{padding:11px 12px}
            .nav-right a{padding:7px 10px;font-size:12px}
            .form-page{padding:0 14px;margin:20px auto}
            .form-card{padding:20px}
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
            <a href="index.php"><i class="fas fa-search"></i> Browse</a>
            <a href="manage_houses.php"><i class="fas fa-th-large"></i> Dashboard</a>
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

    <div class="form-page">
        <div class="form-header">
            <h1>Edit Property</h1>
            <p>Update your listing details below</p>
        </div>
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Kebele</label>
                    <input type="text" name="kebele" value="<?php echo htmlspecialchars($data['kebele']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Monthly Price (ETB)</label>
                    <input type="number" name="amount" value="<?php echo htmlspecialchars($data['amount']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="desc"><?php echo htmlspecialchars($data['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Photo</label>
                    <div class="current-img">
                        <img src="uploads/<?php echo htmlspecialchars($data['image']); ?>" alt="Current">
                    </div>
                    <label>Upload New Photo (Optional)</label>
                    <input type="file" name="house_image" accept="image/*">
                </div>
                <div class="btn-row">
                    <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="manage_houses.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include(__DIR__ . '/popup.php'); ?>
    <?php if(isset($update_error)): ?>
        <script>window.addEventListener('DOMContentLoaded', function(){ showToast("Database error. Your changes were not saved.", "error", "Update failed"); });</script>
    <?php endif; ?>
</body>
</html>
