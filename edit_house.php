<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');
if(!isset($_SESSION['csrf_token'])) csrf_token();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$house_id = (int)($_GET['id'] ?? 0);

$query = mysqli_query($conn, "SELECT * FROM houses WHERE id = $house_id AND user_id = $user_id");
$data = mysqli_fetch_assoc($query);

if(!$data){
    header("Location: manage_houses.php");
    exit();
}

$gallery = [];
$gq = mysqli_query($conn, "SELECT * FROM house_images WHERE house_id=$house_id ORDER BY sort_order ASC, id ASC");
if($gq){ while($g = mysqli_fetch_assoc($gq)) $gallery[] = $g; }

$current_amenities = [];
$aq = mysqli_query($conn, "SELECT amenity_id FROM house_amenities WHERE house_id=$house_id");
if($aq){ while($a = mysqli_fetch_assoc($aq)) $current_amenities[$a['amenity_id']] = $a['amenity_id']; }

$form = $data;

$toast_error = null;
$updated = false;

function process_upload($file, $upload_dir, $allowed){
    if($file['error'] !== UPLOAD_ERR_OK){
        $err_msg = match($file['error']){
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            default               => 'Unknown upload error (code: ' . $file['error'] . ').'
        };
        return [false, $err_msg];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $allowed, true)){
        return [false, 'Only JPG, PNG, WebP, GIF, HEIC or HEIF photos are allowed.'];
    }
    $fname  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = $upload_dir . '/' . $fname;
    if(!move_uploaded_file($file['tmp_name'], $target)){
        return [false, 'Could not save the uploaded file. Check server error log.'];
    }
    if(in_array($ext, ['heic', 'heif'], true)){
        $jpgName = preg_replace('/\.(heic|heif)$/i', '', $fname) . '.jpg';
        $out = shell_exec("/usr/bin/sips -s format jpeg " . escapeshellarg($target) . " --out " . escapeshellarg($upload_dir . '/' . $jpgName) . " 2>&1");
        if($out !== null && file_exists($upload_dir . '/' . $jpgName) && filesize($upload_dir . '/' . $jpgName) > 0){
            @unlink($target);
            $fname = $jpgName;
        } else {
            @unlink($target);
            return [false, 'Could not convert HEIC photo to JPEG.'];
        }
    }
    return [true, $fname];
}

if(isset($_POST['update'])){
    csrf_validate();
    $upload_dir = __DIR__ . '/uploads';
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $kebele   = isset($_POST['kebele'])   ? mysqli_real_escape_string($conn, trim($_POST['kebele']))   : '';
    $street   = isset($_POST['street'])   ? mysqli_real_escape_string($conn, trim($_POST['street']))   : '';
    $h_num    = isset($_POST['house_num']) ? mysqli_real_escape_string($conn, trim($_POST['house_num'])) : '';
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, $_POST['category'])         : '';
    $amount   = isset($_POST['amount'])   ? (int)$_POST['amount']                                      : 0;
    $phone    = isset($_POST['phone'])    ? mysqli_real_escape_string($conn, trim($_POST['phone']))    : '';
    $map      = isset($_POST['map_link']) ? mysqli_real_escape_string($conn, trim($_POST['map_link'])) : '';
    $desc     = isset($_POST['desc'])     ? mysqli_real_escape_string($conn, $_POST['desc'])           : '';

    $form['kebele'] = $kebele;
    $form['street'] = $street;
    $form['house_number'] = $h_num;
    $form['category'] = $category;
    $form['amount'] = $amount;
    $form['phone'] = $phone;
    $form['map_link'] = $map;
    $form['description'] = $desc;

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'];
    $created_files = [];
    $error = null;

    if($kebele === '' || $street === '' || $category === '' || $amount < 1 || $phone === ''){
        $error = 'Please fill in Kebele, Street, Category, Price and Phone.';
    } else {
        $imgName = $data['image'];

        if(!empty($_FILES['house_image']['name'])){
            list($ok, $res) = process_upload($_FILES['house_image'], $upload_dir, $allowed);
            if($ok){
                $imgName = $res;
                $created_files[] = $res;
            } else {
                $error = $res;
            }
        }

        $newGallery = [];
        if(!$error && !empty($_FILES['house_photos']['name'][0])){
            $photos = $_FILES['house_photos'];
            $total  = count($photos['name']);
            for($i = 0; $i < $total; $i++){
                if(empty($photos['name'][$i])) continue;
                $one = [
                    'name'     => $photos['name'][$i],
                    'tmp_name' => $photos['tmp_name'][$i],
                    'error'    => $photos['error'][$i],
                ];
                list($ok, $res) = process_upload($one, $upload_dir, $allowed);
                if(!$ok){ $error = $res; break; }
                $newGallery[] = $res;
                $created_files[] = $res;
                if(count($newGallery) >= 6) break;
            }
        }

        if(!$error){
            $img_safe = mysqli_real_escape_string($conn, $imgName);
            $update_sql = "UPDATE houses SET kebele='$kebele', street='$street', house_number='$h_num', category='$category',
                           amount=$amount, phone='$phone', map_link='$map', description='$desc', image='$img_safe',
                           status='Pending', is_approved=0
                           WHERE id=$house_id AND user_id=$user_id";

            if(mysqli_query($conn, $update_sql)){
                mysqli_query($conn, "INSERT INTO requests (user_id, house_id, status, created_at) VALUES ($user_id, $house_id, 0, NOW())");
                if($imgName !== $data['image'] && !empty($data['image'])){
                    @unlink($upload_dir . '/' . basename($data['image']));
                }

                if(isset($_POST['remove_img']) && is_array($_POST['remove_img'])){
                    foreach($_POST['remove_img'] as $rf){
                        $rf = basename($rf);
                        if(!$rf || $rf === $imgName) continue;
                        @unlink($upload_dir . '/' . $rf);
                        mysqli_query($conn, "DELETE FROM house_images WHERE house_id=$house_id AND filename='" . mysqli_real_escape_string($conn, $rf) . "'");
                    }
                }

                $next_order = 0;
                $gq2 = mysqli_query($conn, "SELECT MAX(sort_order) m FROM house_images WHERE house_id=$house_id");
                if($gq2 && $row2 = mysqli_fetch_assoc($gq2)) $next_order = (int)$row2['m'] + 1;
                foreach($newGallery as $fn){
                    mysqli_query($conn, "INSERT INTO house_images (house_id, filename, sort_order) VALUES ($house_id, '" . mysqli_real_escape_string($conn, $fn) . "', $next_order)");
                    $next_order++;
                }

                mysqli_query($conn, "DELETE FROM house_amenities WHERE house_id=$house_id");
                if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
                    foreach($_POST['amenities'] as $aid){
                        $aid = (int)$aid;
                        if($aid > 0) mysqli_query($conn, "INSERT IGNORE INTO house_amenities (house_id, amenity_id) VALUES ($house_id, $aid)");
                    }
                }

                $updated = true;
            } else {
                $error = 'Database error. Your changes were not saved.';
            }
        }
    }

    if($error){
        foreach($created_files as $f){ @unlink($upload_dir . '/' . $f); }
        if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
            $current_amenities = [];
            foreach($_POST['amenities'] as $aid){
                $aid = (int)$aid;
                if($aid > 0) $current_amenities[$aid] = $aid;
            }
        }
        $toast_error = $error;
    }
}
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
        .nav-right .btn-accent{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;font-weight:600}
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

        .form-page{max-width:760px;margin:40px auto;padding:0 20px}
        .form-header{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:28px;flex-wrap:wrap}
        .form-header h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .form-header p{color:#64748b;font-size:14px;margin-top:4px}
        .form-header .head-actions{display:flex;gap:10px}
        .form-header .view-link{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #e5e7eb;color:#475569;padding:10px 16px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s}
        .form-header .view-link:hover{border-color:#0d9488;color:#0d9488}
        .form-header .view-link .status-pill{margin-left:6px;background:#fef3c7;color:#b45309;padding:2px 8px;border-radius:50px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}

        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .form-section{margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9}
        .form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
        .form-section-title{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px}
        .form-section-title i{color:#0d9488;font-size:16px}
        .form-section-title .badge{margin-left:auto;font-size:11px;font-weight:700;color:#64748b;background:#f1f5f9;padding:3px 10px;border-radius:50px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group:last-child{margin-bottom:0}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group label .req{color:#dc2626}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff;color:#1e293b}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .form-group textarea{resize:vertical;min-height:110px}
        .form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
        .form-group .map-auto{background:rgba(13,148,136,.06);color:#0d9488;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;display:none;margin-top:8px}
        .form-group .map-auto i{margin-right:4px}
        .map-toggle{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#475569;cursor:pointer;margin-top:8px;user-select:none}
        .map-toggle input[type="checkbox"]{width:16px;height:16px;accent-color:#0d9488;cursor:pointer}

        .form-group label .badge{margin-left:6px;font-size:10px;font-weight:700;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:50px;vertical-align:middle}
        .amenity-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
        .amenity-item{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .2s;background:#fff;font-size:13px;font-weight:500;color:#475569;user-select:none}
        .amenity-item:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .amenity-item input{display:none}
        .amenity-item.checked{border-color:#0d9488;background:rgba(13,148,136,.06);color:#0d9488}
        .amenity-item .amenity-icon{width:18px;text-align:center;font-size:13px;color:#94a3b8;transition:color .2s}
        .amenity-item.checked .amenity-icon{color:#0d9488}

        .cover-block{display:flex;align-items:center;gap:20px}
        .cover-preview{width:180px;height:130px;border-radius:12px;overflow:hidden;border:2px solid #f1f5f9;background:#f1f5f9;flex-shrink:0;position:relative}
        .cover-preview img{width:100%;height:100%;object-fit:cover}
        .cover-preview .cover-tag{position:absolute;left:8px;top:8px;background:rgba(13,148,136,.92);color:#fff;font-size:10px;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px}
        .cover-actions{flex:1;min-width:0}
        .cover-actions .file-upload{border:2px dashed #e5e7eb;border-radius:12px;padding:18px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc;margin-bottom:8px}
        .cover-actions .file-upload:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .cover-actions .file-upload i{font-size:22px;color:#94a3b8;margin-bottom:4px;display:block}
        .cover-actions .file-upload p{font-size:13px;color:#64748b;font-weight:500}
        .cover-actions .file-upload input{display:none}
        .file-name{font-size:12px;color:#0d9488;font-weight:600;display:none}

        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px}
        .gallery-item{position:relative;border-radius:12px;overflow:hidden;border:2px solid #f1f5f9;aspect-ratio:4/3;background:#f1f5f9}
        .gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
        .gallery-item .remove-btn{position:absolute;top:6px;right:6px;width:26px;height:26px;border-radius:50%;background:rgba(15,23,42,.65);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .2s;z-index:2}
        .gallery-item .remove-btn:hover{background:#ef4444;transform:scale(1.1)}
        .gallery-item.removing{border-color:#ef4444;opacity:.55}
        .gallery-item.removing .remove-btn{background:#ef4444}
        .gallery-item.removing img{filter:grayscale(1)}
        .gallery-item input[type="checkbox"]{display:none}
        .gallery-item .remove-tag{position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(239,68,68,.55);color:#fff;font-size:12px;font-weight:700}
        .gallery-item.removing .remove-tag{display:flex}

        .gallery-add{border:2px dashed #e5e7eb;border-radius:12px;padding:22px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc;color:#64748b;font-size:13px;font-weight:500}
        .gallery-add:hover{border-color:#0d9488;background:rgba(13,148,136,.03);color:#0d9488}
        .gallery-add i{font-size:20px;display:block;margin-bottom:4px}
        .gallery-add input{display:none}

        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}

        .btn-row{display:grid;grid-template-columns:1fr auto;gap:12px;margin-top:28px;padding-top:24px;border-top:1px solid #f1f5f9}
        .btn-save{padding:14px 24px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-save:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}
        .btn-cancel{padding:14px 20px;background:#f1f5f9;color:#475569;border:none;border-radius:12px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-cancel:hover{background:#e2e8f0}

        /* SUCCESS MODAL */
        .ph-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:20px;animation:phFade .25s ease}
        .ph-overlay.ph-active{display:flex}
        @keyframes phFade{from{opacity:0}to{opacity:1}}
        .ph-card{background:#fff;border-radius:20px;max-width:430px;width:100%;padding:38px 34px;box-shadow:0 25px 60px rgba(0,0,0,.3);text-align:center;animation:phPop .35s cubic-bezier(.34,1.56,.64,1)}
        @keyframes phPop{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
        .ph-icon{width:76px;height:76px;margin:0 auto 20px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 10px 25px rgba(16,185,129,.35)}
        .ph-card h2{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px;letter-spacing:-.4px}
        .ph-card p{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:26px}
        .ph-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .ph-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 16px;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;transition:all .25s;border:none;font-family:inherit;text-decoration:none}
        .ph-btn-primary{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .ph-btn-primary:hover{box-shadow:0 6px 18px rgba(13,148,136,.4);transform:translateY(-1px)}
        .ph-btn-ghost{background:#f1f5f9;color:#334155}
        .ph-btn-ghost:hover{background:#e2e8f0}

        @media(max-width:640px){
            .form-row{grid-template-columns:1fr}
            .form-page{padding:0 12px;margin:20px auto}
            .form-card{padding:20px}
            .cover-block{flex-direction:column;align-items:stretch}
            .cover-preview{width:100%;height:170px}
            .btn-row{grid-template-columns:1fr}
        }
        @media(max-width:480px){
            .navbar{padding:11px 12px}
            .ph-actions{grid-template-columns:1fr}
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
            <a href="post_house.php" class="btn-accent"><i class="fas fa-plus"></i> New Post</a>
            <div class="user-avatar-wrap">
                <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))); ?></div>
                <div class="user-dropdown">
                    <div class="user-dropdown-header">
                        <div class="user-avatar-sm"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1))); ?></div>
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

    <div class="form-page">
        <div class="form-header">
            <div>
                <h1>Edit Property</h1>
                <p>Update your listing details below.</p>
            </div>
            <div class="head-actions">
                <a class="view-link" href="house_detail.php?house=<?php echo (int)$house_id; ?>"><i class="fas fa-external-link"></i> Preview
                    <span class="status-pill"><?php echo htmlspecialchars($data['status'] ?? 'Pending'); ?></span>
                </a>
            </div>
        </div>

        <div class="form-card">
            <?php if(isset($toast_error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($toast_error); ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <!-- Location -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-location-dot"></i> Location Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kebele <span class="req">*</span></label>
                            <input type="text" name="kebele" placeholder="e.g. 03 or 12" value="<?php echo htmlspecialchars($form['kebele']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_num" placeholder="e.g. 45" value="<?php echo htmlspecialchars($form['house_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Street Name <span class="req">*</span></label>
                        <input type="text" name="street" placeholder="e.g. Bole Road" value="<?php echo htmlspecialchars($form['street']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Map Link</label>
                        <input type="url" id="map_link" name="map_link" placeholder="Paste a Google Maps link here (optional)" value="<?php echo htmlspecialchars($form['map_link'] ?? ''); ?>">
                        <label class="map-toggle"><input type="checkbox" id="map_auto"> Auto-generate map link from location fields</label>
                        <div class="map-auto" id="auto-gen-note"><i class="fas fa-check-circle"></i> Auto-generated from location fields</div>
                    </div>
                </div>

                <!-- Property Info -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-home"></i> Property Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span class="req">*</span></label>
                            <select name="category" required>
                                <option value="">Select type...</option>
                                <optgroup label="Residential">
                                    <option value="Single Home" <?php echo $form['category'] === 'Single Home' ? 'selected' : ''; ?>>Single Home</option>
                                    <option value="Apartment" <?php echo $form['category'] === 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="Villa" <?php echo $form['category'] === 'Villa' ? 'selected' : ''; ?>>Villa</option>
                                </optgroup>
                                <optgroup label="Commercial">
                                    <option value="Office" <?php echo $form['category'] === 'Office' ? 'selected' : ''; ?>>Office</option>
                                    <option value="Shop" <?php echo $form['category'] === 'Shop' ? 'selected' : ''; ?>>Shop</option>
                                    <option value="Warehouse" <?php echo $form['category'] === 'Warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monthly Price (ETB) <span class="req">*</span></label>
                            <input type="number" name="amount" placeholder="e.g. 8000" value="<?php echo htmlspecialchars($form['amount']); ?>" required min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone <span class="req">*</span></label>
                        <input type="text" name="phone" placeholder="e.g. 0911234567" value="<?php echo htmlspecialchars($form['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="desc" placeholder="Describe your property - water, electricity, furnished status, etc."><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Photos -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-camera"></i> Photos</div>

                    <div class="cover-block" style="margin-bottom:20px">
                        <div class="cover-preview">
                            <span class="cover-tag">Cover</span>
                            <img src="uploads/<?php echo htmlspecialchars($data['image']); ?>" alt="Cover photo">
                        </div>
                        <div class="cover-actions">
                            <div class="file-upload" onclick="this.querySelector('input').click()">
                                <i class="fas fa-camera-retro"></i>
                                <p>Change Cover Photo</p>
                                <div class="file-name" id="cover-name"></div>
                                <input type="file" name="house_image" accept="image/*" onchange="updatePhotoList(this, 'cover-name')">
                            </div>
                            <div class="hint">JPG, PNG, WebP, GIF, HEIC or HEIF &middot; 5MB max &middot; HEIC is converted automatically</div>
                        </div>
                    </div>

                    <?php if(!empty($gallery)): ?>
                    <div class="form-group" style="margin-bottom:14px">
                        <label>Gallery Photos <span class="badge"><?php echo count($gallery); ?></span></label>
                        <div class="gallery-grid">
                            <?php foreach($gallery as $img): ?>
                            <div class="gallery-item" data-toggle="gallery">
                                <img src="uploads/<?php echo htmlspecialchars($img['filename']); ?>" alt="Gallery photo">
                                <button type="button" class="remove-btn" title="Remove photo"><i class="fas fa-xmark"></i></button>
                                <input type="checkbox" name="remove_img[]" value="<?php echo htmlspecialchars($img['filename']); ?>">
                                <span class="remove-tag"><i class="fas fa-trash"></i> Remove</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint">Click the <i class="fas fa-xmark"></i> on a photo to mark it for removal before saving.</div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Add More Photos</label>
                        <div class="gallery-add" onclick="this.querySelector('input').click()">
                            <i class="fas fa-image"></i>
                            Add Photo(s)
                            <div class="file-name" id="gallery-name"></div>
                            <input type="file" name="house_photos[]" accept="image/*" multiple onchange="updatePhotoList(this, 'gallery-name')">
                        </div>
                        <div class="hint">Up to 6 photos total &middot; JPG, PNG, WebP, GIF, HEIC or HEIF</div>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-star"></i> Amenities</div>
                    <p style="font-size:13px;color:#94a3b8;margin-bottom:14px">Select all amenities that apply to your property</p>
                    <div class="amenity-grid">
                        <?php
                        $amenities_result = mysqli_query($conn, "SELECT * FROM amenities ORDER BY sort_order ASC");
                        while($amenity = mysqli_fetch_assoc($amenities_result)):
                        ?>
                        <label class="amenity-item<?php echo isset($current_amenities[$amenity['id']]) ? ' checked' : ''; ?>">
                            <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" <?php echo isset($current_amenities[$amenity['id']]) ? 'checked' : ''; ?>>
                            <span class="amenity-icon"><i class="<?php echo htmlspecialchars($amenity['icon']); ?>"></i></span>
                            <?php echo htmlspecialchars($amenity['name']); ?>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" name="update" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="manage_houses.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.querySelectorAll('.amenity-item input[type="checkbox"]').forEach(function(cb){
        cb.addEventListener('change', function(){
            this.closest('.amenity-item').classList.toggle('checked', this.checked);
        });
    });

    document.querySelectorAll('.gallery-item').forEach(function(item){
        var btn = item.querySelector('.remove-btn');
        var box = item.querySelector('input[type="checkbox"]');
        function applyState(){
            var removing = box.checked;
            item.classList.toggle('removing', removing);
            btn.innerHTML = removing ? '<i class="fas fa-rotate-left"></i>' : '<i class="fas fa-xmark"></i>';
            btn.title = removing ? 'Undo remove' : 'Remove photo';
        }
        btn.addEventListener('click', function(){
            box.checked = !box.checked;
            applyState();
        });
    });

    function updatePhotoList(input, targetId){
        var el = document.getElementById(targetId);
        if(!el) return;
        var n = input.files.length;
        if(n === 0){ el.style.display = 'none'; return; }
        var names = [];
        for(var i = 0; i < n; i++) names.push(input.files[i].name);
        el.textContent = n + (n > 1 ? ' photos' : ' photo') + ' selected: ' + names.join(', ');
        el.style.display = 'block';
    }

    var kebeleInput = document.querySelector('input[name="kebele"]');
    var streetInput = document.querySelector('input[name="street"]');
    var houseNumInput = document.querySelector('input[name="house_num"]');
    var mapLinkInput = document.getElementById('map_link');
    var autoGenNote = document.getElementById('auto-gen-note');
    var mapAutoToggle = document.getElementById('map_auto');

    function updateMapLink(){
        if(!mapAutoToggle.checked) return;
        var kebele = (kebeleInput.value || '').trim();
        var street = (streetInput.value || '').trim();
        var houseNum = (houseNumInput.value || '').trim();
        if(kebele || street){
            var parts = [];
            if(kebele) parts.push('Kebele ' + kebele);
            if(street) parts.push(street);
            if(houseNum) parts.push('House ' + houseNum);
            parts.push('Adama, Ethiopia');
            mapLinkInput.value = 'https://www.google.com/maps/search/' + encodeURIComponent(parts.join(', '));
            autoGenNote.style.display = 'block';
        } else {
            mapLinkInput.value = '';
            autoGenNote.style.display = 'none';
        }
    }
    kebeleInput.addEventListener('input', updateMapLink);
    streetInput.addEventListener('input', updateMapLink);
    houseNumInput.addEventListener('input', updateMapLink);
    mapAutoToggle.addEventListener('change', function(){
        if(this.checked){ updateMapLink(); } else { autoGenNote.style.display = 'none'; }
    });
    </script>

    <?php include(__DIR__ . '/includes/popup.php'); ?>

    <?php if($updated): ?>
    <div class="ph-overlay ph-active" id="phSuccess">
        <div class="ph-card">
            <div class="ph-icon"><i class="fas fa-check"></i></div>
            <h2>Changes Saved</h2>
            <p>Your changes have been submitted and will be reviewed by an admin. The listing will go live again once approved.</p>
            <div class="ph-actions">
                <a href="edit_house.php?id=<?php echo (int)$house_id; ?>" class="ph-btn ph-btn-ghost"><i class="fas fa-pen"></i> Keep Editing</a>
                <a href="manage_houses.php" class="ph-btn ph-btn-primary"><i class="fas fa-th-large"></i> Go to Dashboard</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>