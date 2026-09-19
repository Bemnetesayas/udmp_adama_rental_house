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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Property - AdamaRent</title>
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

        .form-page{max-width:680px;margin:40px auto;padding:0 20px}
        .form-header{margin-bottom:28px}
        .form-header h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .form-header p{color:#64748b;font-size:14px;margin-top:4px}
        .form-header .user-tag{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#475569;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;margin-top:12px}
        .form-header .user-tag i{color:#0d9488}

        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .form-section{margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9}
        .form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
        .form-section-title{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px}
        .form-section-title i{color:#0d9488;font-size:16px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group:last-child{margin-bottom:0}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group label .req{color:#dc2626}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .form-group textarea{resize:vertical;min-height:90px}
        .form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
        .form-group .map-auto{background:rgba(13,148,136,.06);color:#0d9488;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;display:none;margin-top:8px}
        .form-group .map-auto i{margin-right:4px}
        .map-toggle{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#475569;cursor:pointer;margin-top:8px;user-select:none}
        .map-toggle input[type="checkbox"]{width:16px;height:16px;accent-color:#0d9488;cursor:pointer}

        .amenity-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
        .amenity-item{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .2s;background:#fff;font-size:13px;font-weight:500;color:#475569;user-select:none}
        .amenity-item:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .amenity-item input{display:none}
        .amenity-item.checked{border-color:#0d9488;background:rgba(13,148,136,.06);color:#0d9488}
        .amenity-item .amenity-icon{width:18px;text-align:center;font-size:13px;color:#94a3b8;transition:color .2s}
        .amenity-item.checked .amenity-icon{color:#0d9488}

        .file-upload{border:2px dashed #e5e7eb;border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc}
        .file-upload:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .file-upload i{font-size:28px;color:#94a3b8;margin-bottom:8px;display:block}
        .file-upload p{font-size:14px;color:#64748b;font-weight:500}
        .file-upload span{font-size:12px;color:#94a3b8}
        .file-upload input{display:none}
        .file-name{font-size:13px;color:#0d9488;font-weight:600;margin-top:8px;display:none}

        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:32px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}

        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}

        /* SUCCESS MODAL */
        .ph-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:20px;animation:phFade .25s ease}
        .ph-overlay.ph-active{display:flex}
        @keyframes phFade{from{opacity:0}to{opacity:1}}
        .ph-card{background:#fff;border-radius:20px;max-width:430px;width:100%;padding:38px 34px;box-shadow:0 25px 60px rgba(0,0,0,.3);text-align:center;animation:phPop .35s cubic-bezier(.34,1.56,.64,1)}
        @keyframes phPop{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
        .ph-icon{width:76px;height:76px;margin:0 auto 20px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 10px 25px rgba(16,185,129,.35)}
        .ph-card h2{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px;letter-spacing:-.4px}
        .ph-card p{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:26px}
        .ph-steps{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:28px}
        .ph-step{display:flex;flex-direction:column;align-items:center;gap:6px;width:80px}
        .ph-step .ph-dot{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;background:#f1f5f9;color:#94a3b8;font-weight:700}
        .ph-step.done .ph-dot{background:#10b981;color:#fff}
        .ph-step.active .ph-dot{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;box-shadow:0 5px 14px rgba(13,148,136,.4)}
        .ph-step span{font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.3px}
        .ph-step.done span,.ph-step.active span{color:#0f172a}
        .ph-bar{width:26px;height:2px;background:#e2e8f0;margin-bottom:22px}
        .ph-bar.done{background:#10b981}
        .ph-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .ph-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 16px;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;transition:all .25s;border:none;font-family:inherit;text-decoration:none}
        .ph-btn-primary{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .ph-btn-primary:hover{box-shadow:0 6px 18px rgba(13,148,136,.4);transform:translateY(-1px)}
        .ph-btn-ghost{background:#f1f5f9;color:#334155}
        .ph-btn-ghost:hover{background:#e2e8f0}
        @media(max-width:480px){
            .ph-actions{grid-template-columns:1fr}
            .ph-steps{gap:4px}
            .ph-step{width:70px}
            .ph-bar{width:18px}
        }

        @media(max-width:640px){
            .form-row{grid-template-columns:1fr}
            .form-page{padding:0 12px;margin:20px auto}
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
                    <a href="logout.php" class="logout"><i class="fas fa-right-from-bracket"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="form-page">
        <div class="form-header">
            <h1>Post a New Property</h1>
            <p>Fill in the details below to list your property for potential tenants.</p>
        </div>

        <div class="form-card">
            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                <!-- Location -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-location-dot"></i> Location Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kebele <span class="req">*</span></label>
                            <input type="text" name="kebele" placeholder="e.g. 03 or 12" required>
                        </div>
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_num" placeholder="e.g. 45">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Street Name <span class="req">*</span></label>
                        <input type="text" name="street" placeholder="e.g. Bole Road" required>
                    </div>
                    <div class="form-group">
                        <label>Map Link</label>
                        <input type="url" id="map_link" name="map_link" placeholder="Paste a Google Maps link here (optional)">
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
                                    <option value="Single Home">Single Home</option>
                                    <option value="Apartment">Apartment</option>
                                    <option value="Villa">Villa</option>
                                </optgroup>
                                <optgroup label="Commercial">
                                    <option value="Office">Office</option>
                                    <option value="Shop">Shop</option>
                                    <option value="Warehouse">Warehouse</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monthly Price (ETB) <span class="req">*</span></label>
                            <input type="number" name="amount" placeholder="e.g. 8000" required min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone <span class="req">*</span></label>
                        <input type="text" name="phone" placeholder="e.g. 0911234567" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="desc" placeholder="Describe your property - water, electricity, furnished status, etc."></textarea>
                    </div>
                </div>

                <!-- Photos -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-camera"></i> Photos</div>
                    <div class="form-group">
                        <label>Property Photos <span class="req">*</span></label>
                        <div class="file-upload" onclick="this.querySelector('input').click()">
                            <i class="fas fa-images"></i>
                            <p>Click to upload photos</p>
                            <span>JPG, PNG, WebP or GIF &middot; the first photo becomes the cover &middot; up to 6 photos (5MB each)</span>
                            <div class="file-name" id="img-name"></div>
                            <input type="file" name="house_photos[]" accept="image/*" multiple required onchange="updatePhotoList(this)">
                        </div>
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
                        <label class="amenity-item">
                            <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>">
                            <span class="amenity-icon"><i class="<?php echo htmlspecialchars($amenity['icon']); ?>"></i></span>
                            <?php echo htmlspecialchars($amenity['name']); ?>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
            </form>
        </div>
    </div>

    <script>
    const kebeleInput = document.querySelector('input[name="kebele"]');
    const amenityCheckboxes = document.querySelectorAll('.amenity-item input[type="checkbox"]');
    amenityCheckboxes.forEach(function(cb){
        cb.addEventListener('change', function(){
            this.closest('.amenity-item').classList.toggle('checked', this.checked);
        });
    });
    const streetInput = document.querySelector('input[name="street"]');
    const houseNumInput = document.querySelector('input[name="house_num"]');
    const mapLinkInput = document.getElementById('map_link');
    const autoGenNote = document.getElementById('auto-gen-note');
    const mapAutoToggle = document.getElementById('map_auto');

    function updateMapLink() {
        if(!mapAutoToggle.checked) return;
        const kebele = kebeleInput.value.trim();
        const street = streetInput.value.trim();
        const houseNum = houseNumInput.value.trim();
        if (kebele || street) {
            let parts = [];
            if (kebele) parts.push('Kebele ' + kebele);
            if (street) parts.push(street);
            if (houseNum) parts.push('House ' + houseNum);
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
        if(this.checked){
            updateMapLink();
        } else {
            autoGenNote.style.display = 'none';
        }
    });

    function updatePhotoList(input){
        var el = document.getElementById('img-name');
        var n = input.files.length;
        if(n === 0){
            el.style.display = 'none';
            return;
        }
        var names = [];
        for(var i = 0; i < n; i++) names.push(input.files[i].name);
        el.textContent = n + (n > 1 ? ' photos' : ' photo') + ' selected: ' + names.join(', ');
        el.style.display = 'block';
    }
    </script>

    <?php
    $toast_error = null;
    $submitted = false;

    if(isset($_POST['submit'])){
        csrf_validate();
        $upload_dir = __DIR__ . '/uploads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $kebele   = mysqli_real_escape_string($conn, $_POST['kebele']);
        $street   = mysqli_real_escape_string($conn, $_POST['street']);
        $h_num    = mysqli_real_escape_string($conn, $_POST['house_num']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $amount   = (int)$_POST['amount'];
        $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
        $map      = mysqli_real_escape_string($conn, $_POST['map_link']);
        $desc     = mysqli_real_escape_string($conn, $_POST['desc']);
        $user_id  = $_SESSION['user_id'];

        if(empty($_FILES['house_photos']['name'][0])){
            $toast_error = 'Upload failed: Please select at least one photo.';
        } elseif(!is_writable($upload_dir)){
            $toast_error = 'Upload failed: The uploads folder is not writable. Check permissions.';
        } else {
            $photos  = $_FILES['house_photos'];
            $names   = [];
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'];
            $total   = count($photos['name']);

            for($i = 0; $i < $total; $i++){
                if(empty($photos['name'][$i])) continue;

                if($photos['error'][$i] !== UPLOAD_ERR_OK){
                    $err_msg = match($photos['error'][$i]){
                        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL    => 'a file was only partially uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
                        default               => 'Unknown upload error (code: ' . $photos['error'][$i] . ').'
                    };
                    $toast_error = 'Upload failed: ' . $err_msg;
                    break;
                }

                $ext = strtolower(pathinfo($photos['name'][$i], PATHINFO_EXTENSION));
                if(!in_array($ext, $allowed, true)){
                    $toast_error = 'Upload failed: Only JPG, PNG, WebP, GIF, HEIC or HEIF photos are allowed.';
                    break;
                }

                $fname  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target = $upload_dir . '/' . $fname;

                if(move_uploaded_file($photos['tmp_name'][$i], $target)){
                    if(in_array($ext, ['heic', 'heif'], true)){
                        $jpgName = preg_replace('/\.(heic|heif)$/i', '', $fname) . '.jpg';
                        $out = shell_exec("/usr/bin/sips -s format jpeg " . escapeshellarg($target) . " --out " . escapeshellarg($upload_dir . '/' . $jpgName) . " 2>&1");
                        if($out !== null && file_exists($upload_dir . '/' . $jpgName) && filesize($upload_dir . '/' . $jpgName) > 0){
                            @unlink($target);
                            $fname = $jpgName;
                        } else {
                            @unlink($target);
                            $toast_error = 'Upload failed: Could not convert HEIC photo to JPEG.';
                            break;
                        }
                    }
                    $names[] = $fname;
                } else {
                    $toast_error = 'Upload failed: move_uploaded_file returned false. Check server error log.';
                    break;
                }

                if(count($names) >= 6) break;
            }

            if($toast_error){
                foreach($names as $f){
                    @unlink($upload_dir . '/' . $f);
                }
            } elseif(empty($names)){
                $toast_error = 'Upload failed: No valid photos were processed.';
            } else {
                $featured = array_shift($names);
                $featured_safe = mysqli_real_escape_string($conn, $featured);
                $sql = "INSERT INTO houses (kebele, street, house_number, category, amount, phone, map_link, image, description, user_id, status, is_approved, created_at) 
                        VALUES ('$kebele', '$street', '$h_num', '$category', '$amount', '$phone', '$map', '$featured_safe', '$desc', $user_id, 'Pending', 0, NOW())";

                if(mysqli_query($conn, $sql)){
                    $house_id = mysqli_insert_id($conn);
                    if(isset($_POST['amenities']) && is_array($_POST['amenities'])){
                        foreach($_POST['amenities'] as $amenity_id){
                            $amenity_id = (int)$amenity_id;
                            if($amenity_id > 0){
                                mysqli_query($conn, "INSERT IGNORE INTO house_amenities (house_id, amenity_id) VALUES ($house_id, $amenity_id)");
                            }
                        }
                    }
                    $req_sql = "INSERT INTO requests (user_id, house_id, status, created_at) VALUES ($user_id, $house_id, 0, NOW())";
                    mysqli_query($conn, $req_sql);
                    $order = 1;
                    foreach($names as $fn){
                        $fn_safe = mysqli_real_escape_string($conn, $fn);
                        mysqli_query($conn, "INSERT INTO house_images (house_id, filename, sort_order) VALUES ($house_id, '$fn_safe', $order)");
                        $order++;
                    }
                    $submitted = true;
                } else {
                    foreach($names as $f){
                        @unlink($upload_dir . '/' . $f);
                    }
                    @unlink($upload_dir . '/' . $featured);
                    $toast_error = 'Database error. Please try again.';
                }
            }
        }
    }
    ?>

    <?php include(__DIR__ . '/includes/popup.php'); ?>

    <?php if($submitted): ?>
    <div class="ph-overlay ph-active" id="phSuccess">
        <div class="ph-card">
            <div class="ph-icon"><i class="fas fa-check"></i></div>
            <h2>Submitted for Approval</h2>
            <p>Your property is now in review. Once an admin approves it, it will go live on the marketplace.</p>
            <div class="ph-steps">
                <div class="ph-step done"><div class="ph-dot"><i class="fas fa-check"></i></div><span>Submitted</span></div>
                <div class="ph-bar done"></div>
                <div class="ph-step active"><div class="ph-dot"><i class="fas fa-clock"></i></div><span>In Review</span></div>
                <div class="ph-bar"></div>
                <div class="ph-step"><div class="ph-dot"><i class="fas fa-home"></i></div><span>Live</span></div>
            </div>
            <div class="ph-actions">
                <a href="post_house.php" class="ph-btn ph-btn-ghost"><i class="fas fa-plus"></i> Add Another</a>
                <a href="manage_houses.php" class="ph-btn ph-btn-primary"><i class="fas fa-th-large"></i> Go to Dashboard</a>
            </div>
        </div>
    </div>
    <?php elseif($toast_error): ?>
        <script>window.addEventListener('DOMContentLoaded', function(){ showToast(<?php echo json_encode($toast_error); ?>, "error", "Upload failed"); });</script>
    <?php endif; ?>
</body>
</html>
