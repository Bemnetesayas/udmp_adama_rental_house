<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1){
    header('Location: login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if($id <= 0){
    header('Location: admin_manage_requests.php');
    exit();
}

$q = mysqli_query($conn, "SELECT h.*, u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone, u.phone2 AS owner_phone2 FROM houses h LEFT JOIN users u ON h.user_id = u.id WHERE h.id = $id");
$house = $q ? mysqli_fetch_assoc($q) : null;
if(!$house){
    header('Location: admin_manage_requests.php');
    exit();
}

$imgs = [];
if(!empty($house['image'])) $imgs[] = $house['image'];
$gi = mysqli_query($conn, "SELECT filename FROM house_images WHERE house_id=$id ORDER BY sort_order ASC, id ASC");
if($gi){ while($g = mysqli_fetch_assoc($gi)) $imgs[] = $g['filename']; }
$imgs = array_values(array_unique($imgs));

$amenities = [];
$aq = mysqli_query($conn, "SELECT a.name, a.icon FROM amenities a JOIN house_amenities ha ON ha.amenity_id = a.id WHERE ha.house_id = $id ORDER BY a.sort_order");
if($aq){ while($a = mysqli_fetch_assoc($aq)) $amenities[] = $a; }

$status_val = $house['status'] ?? '';
if($status_val === '0' || strcasecmp($status_val,'Available')===0) $status_label = 'Available';
elseif($status_val === '1' || strcasecmp($status_val,'Rented')===0) $status_label = 'Rented';
elseif(strcasecmp($status_val,'Rejected')===0) $status_label = 'Rejected';
else $status_label = 'Pending';

$is_pending = (strcasecmp($status_val,'Pending')===0 || (int)$house['is_approved'] === 0);
$is_approved = (int)$house['is_approved'] === 1;

$req_type = 'new';
$changes = [];
$rq = mysqli_query($conn, "SELECT type, changes FROM requests WHERE house_id=$id AND status=0 ORDER BY id DESC LIMIT 1");
if($rq && $rrow = mysqli_fetch_assoc($rq)){
    $req_type = strtolower($rrow['type'] ?? 'new');
    if(!empty($rrow['changes'])){
        $decoded = json_decode($rrow['changes'], true);
        if(is_array($decoded)) $changes = $decoded;
    }
}
$prev_app = mysqli_query($conn, "SELECT COUNT(*) FROM requests WHERE house_id=$id AND status=1");
$was_prev_approved = ($prev_app && ($c = mysqli_fetch_row($prev_app)) && (int)$c[0] > 0);
$is_edited = ($req_type === 'edit') || ($was_prev_approved && $is_pending && !$is_approved);

$amenity_names = [];
$anq = mysqli_query($conn, "SELECT id, name FROM amenities");
if($anq){ while($a = mysqli_fetch_assoc($anq)) $amenity_names[(int)$a['id']] = $a['name']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Listing #<?php echo $house['id']; ?> - AdamaRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
    <style>
        .review-wrap{max-width:980px;margin:0 auto}
        .review-head{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:20px}
        .review-head .back{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e2e8f0;color:#475569;text-decoration:none;font-size:13px;font-weight:600;padding:9px 14px;border-radius:9px;transition:all .2s}
        .review-head .back:hover{border-color:#0d9488;color:#0d9488}
        .review-head .h1{font-size:20px;font-weight:800;color:#0f172a}
        .review-head .badge-st{font-size:12px;font-weight:700;padding:6px 12px;border-radius:999px}
        .badge-st.bs-avail{background:rgba(16,185,129,.14);color:#059669}
        .badge-st.bs-rent{background:rgba(99,102,241,.14);color:#4f46e5}
        .badge-st.bs-pend{background:rgba(245,158,11,.14);color:#d97706}
        .badge-st.bs-rej{background:rgba(239,68,68,.14);color:#dc2626}
        .rv-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;margin-bottom:16px}
        .rv-title{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:800;color:#0f172a;margin-bottom:16px}
        .rv-title i{color:#0d9488}
        .rv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
        .rv-field{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px}
        .rv-field .rv-label{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
        .rv-field .rv-value{font-size:14px;font-weight:600;color:#0f172a;word-break:break-word}
        .rv-field.full{grid-column:1 / -1}
        .rv-desc{font-size:14px;color:#475569;line-height:1.7;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;white-space:pre-wrap}
        .rv-amenities{display:flex;flex-wrap:wrap;gap:8px}
        .rv-amenity{display:inline-flex;align-items:center;gap:7px;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;padding:7px 12px;border-radius:9px;font-size:13px;font-weight:600}
        .rv-photos{display:flex;flex-direction:column;gap:12px}
        .rv-photos img{width:100%;height:auto;border-radius:10px;border:1px solid #e2e8f0;cursor:zoom-in}
        .rvactions{display:flex;gap:12px;flex-wrap:wrap;margin-top:4px}
        .rvactions .btn{flex:1;min-width:180px;justify-content:center;padding:13px}
        .btn-success{background:#10b981;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-family:inherit;transition:all .2s}
        .btn-success:hover{background:#059669;transform:translateY(-1px)}
        .btn-danger-ghost{background:#fff;color:#dc2626;border:1.5px solid #fecaca;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-family:inherit;transition:all .2s}
        .btn-danger-ghost:hover{background:#fef2f2;border-color:#fca5a5}
        .btn-secondary{background:#fff;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-family:inherit;transition:all .2s;padding:11px 16px}
        .btn-secondary:hover{border-color:#94a3b8;color:#334155}
        .lb-dark{position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.93);display:none;align-items:center;justify-content:center;cursor:zoom-out}
        .lb-dark.open{display:flex}
        .lb-dark img{max-width:94vw;max-height:92vh;border-radius:8px}
        .rv-changes{display:flex;flex-direction:column;gap:10px}
        .rv-change{background:#f8fafc;border:1px solid #e2e8f0;border-left:3px solid #8b5cf6;border-radius:10px;padding:12px 14px}
        .rv-change-field{font-size:12px;font-weight:800;color:#6d28d9;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;display:flex;align-items:center;gap:7px}
        .rv-change-field i{font-size:11px;opacity:.7}
        .rv-change-body{display:flex;flex-wrap:wrap;align-items:center;gap:9px;font-size:13px}
        .rv-old{color:#b91c1c;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);padding:5px 11px;border-radius:7px;text-decoration:line-through;text-decoration-color:rgba(185,28,28,.5);word-break:break-word}
        .rv-new{color:#047857;background:rgba(16,185,129,.09);border:1px solid rgba(16,185,129,.22);padding:5px 11px;border-radius:7px;font-weight:600;word-break:break-word}
        .rv-arrow{color:#94a3b8;font-size:13px}
        .rv-muted{color:#94a3b8;font-size:12px}
        .rv-tags{display:flex;flex-wrap:wrap;align-items:center;gap:7px;width:100%}
        .rv-tags + .rv-tags{margin-top:6px}
        .rv-tag-label{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.3px}
        .rv-tag{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px}
        .rv-tag-add{background:rgba(16,185,129,.12);color:#047857;border:1px solid rgba(16,185,129,.25)}
        .rv-tag-del{background:rgba(239,68,68,.1);color:#b91c1c;border:1px solid rgba(239,68,68,.22)}
        .rv-change-line{display:flex;align-items:center;gap:9px}
    </style>
</head>
<body>
<div class="content" style="padding-top:28px">
    <div class="review-wrap">
        <div class="review-head">
            <a href="admin_manage_requests.php" class="back"><i class="fas fa-arrow-left"></i> Back to Approvals</a>
            <span class="h1">Listing Review #<?php echo $house['id']; ?></span>
            <span class="badge-st <?php echo $is_approved ? 'bs-avail' : (($status_label==='Rejected') ? 'bs-rej' : 'bs-pend'); ?>">
                <?php echo $is_approved ? 'Approved' : $status_label; ?>
            </span>
        </div>

        <?php if($is_edited): ?>
        <div style="display:flex;align-items:center;gap:12px;background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.25);color:#6d28d9;padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:16px">
            <i class="fas fa-pen-to-square" style="font-size:18px"></i>
            This listing was previously approved and has been edited by the owner. Review the changes below before re-approving.
        </div>
        <?php endif; ?>

        <?php if($is_edited && count($changes) > 0):
            $short = function($v, $limit = 140){
                $v = (string)$v;
                if(mb_strlen($v) > $limit) return mb_substr($v, 0, $limit) . '…';
                return $v;
            };
        ?>
        <div class="rv-card" style="border-color:rgba(139,92,246,.3)">
            <div class="rv-title"><i class="fas fa-code-compare"></i> Changes Made vs. Previously Approved (<?php echo count($changes); ?>)</div>
            <div class="rv-changes">
                <?php foreach($changes as $ch):
                    $field = $ch['field'] ?? 'Field';
                ?>
                    <div class="rv-change">
                        <div class="rv-change-field"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($field); ?></div>
                        <?php if(isset($ch['added']) && isset($ch['removed'])): ?>
                            <div class="rv-change-body">
                                <?php if($field === 'Amenities'): ?>
                                    <?php if(!empty($ch['removed'])): ?>
                                        <div class="rv-tags"><span class="rv-tag-label">Removed:</span>
                                            <?php foreach($ch['removed'] as $aid): ?>
                                                <span class="rv-tag rv-tag-del"><i class="fas fa-minus"></i> <?php echo htmlspecialchars($amenity_names[(int)$aid] ?? ('#' . $aid)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($ch['added'])): ?>
                                        <div class="rv-tags"><span class="rv-tag-label">Added:</span>
                                            <?php foreach($ch['added'] as $aid): ?>
                                                <span class="rv-tag rv-tag-add"><i class="fas fa-plus"></i> <?php echo htmlspecialchars($amenity_names[(int)$aid] ?? ('#' . $aid)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="rv-change-line">
                                        <span class="rv-old"><?php echo (int)$ch['from']; ?> photo<?php echo (int)$ch['from'] == 1 ? '' : 's'; ?></span>
                                        <i class="fas fa-arrow-right-long rv-arrow"></i>
                                        <span class="rv-new"><?php echo (int)$ch['to']; ?> photo<?php echo (int)$ch['to'] == 1 ? '' : 's'; ?></span>
                                    </div>
                                    <?php if(!empty($ch['removed'])): ?>
                                        <div style="font-size:12px;color:#94a3b8;margin-top:4px">Removed: <?php echo htmlspecialchars(implode(', ', array_map('basename', $ch['removed']))); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($ch['added'])): ?>
                                        <div style="font-size:12px;color:#94a3b8;margin-top:2px">Added: <?php echo htmlspecialchars(implode(', ', array_map('basename', $ch['added']))); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="rv-change-body">
                                <?php
                                    $from = (string)($ch['from'] ?? '');
                                    $to   = (string)($ch['to'] ?? '');
                                    $is_img = ($field === 'Cover photo');
                                ?>
                                <?php if($from === ''): ?>
                                    <span class="rv-new" style="font-weight:600"><?php echo $is_img ? 'New cover set' : 'Set to "' . htmlspecialchars($short($to)) . '"'; ?></span>
                                <?php elseif($to === ''): ?>
                                    <span class="rv-old"><?php echo $is_img ? 'Cover removed' : '"' . htmlspecialchars($short($from)) . '"'; ?></span> <span class="rv-muted">(cleared)</span>
                                <?php else: ?>
                                    <span class="rv-old">"<?php echo htmlspecialchars($short($from)); ?>"</span>
                                    <i class="fas fa-arrow-right-long rv-arrow"></i>
                                    <span class="rv-new">"<?php echo htmlspecialchars($short($to)); ?>"</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif($is_edited): ?>
        <div class="rv-card">
            <div class="rv-title"><i class="fas fa-code-compare"></i> Changes Made</div>
            <p style="font-size:14px;color:#64748b;margin:0">The listing was re-submitted. Compare the details below against what you approved earlier.</p>
        </div>
        <?php endif; ?>

        <div class="rv-card">
            <div class="rv-title"><i class="fas fa-user-shield"></i> Submitted By (Landlord)</div>
            <div class="rv-grid">
                <div class="rv-field"><div class="rv-label">Full Name</div><div class="rv-value"><?php echo htmlspecialchars($house['owner_name'] ?? 'Unknown'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Email</div><div class="rv-value"><?php echo htmlspecialchars($house['owner_email'] ?? 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Phone 1</div><div class="rv-value"><?php echo htmlspecialchars($house['owner_phone'] ?: 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Phone 2</div><div class="rv-value"><?php echo htmlspecialchars($house['owner_phone2'] ?: 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Submitted On</div><div class="rv-value"><?php echo date('M d, Y', strtotime($house['created_at'])); ?></div></div>
            </div>
        </div>

        <div class="rv-card">
            <div class="rv-title"><i class="fas fa-house"></i> Property Details (What the user entered)</div>
            <div class="rv-grid">
                <div class="rv-field"><div class="rv-label">Category</div><div class="rv-value"><?php echo htmlspecialchars($house['category'] ?? 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Price</div><div class="rv-value"><?php echo number_format($house['amount'] ?? 0); ?> ETB/mo</div></div>
                <div class="rv-field"><div class="rv-label">Kebele</div><div class="rv-value"><?php echo htmlspecialchars($house['kebele']); ?></div></div>
                <div class="rv-field"><div class="rv-label">Street</div><div class="rv-value"><?php echo htmlspecialchars($house['street'] ?: 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">House Number</div><div class="rv-value"><?php echo htmlspecialchars($house['house_number'] ?: 'N/A'); ?></div></div>
                <div class="rv-field"><div class="rv-label">Contact Phone</div><div class="rv-value"><?php echo htmlspecialchars($house['phone'] ?: 'N/A'); ?></div></div>
                <div class="rv-field full">
                    <div class="rv-label">Map Link</div>
                    <div class="rv-value">
                        <?php if(!empty($house['map_link'])): ?>
                            <a href="<?php echo htmlspecialchars($house['map_link']); ?>" target="_blank" rel="noopener" style="color:#0d9488;font-weight:700"><?php echo htmlspecialchars($house['map_link']); ?> <i class="fas fa-up-right-from-square" style="font-size:11px"></i></a>
                        <?php else: ?> N/A <?php endif; ?>
                    </div>
                </div>
                <div class="rv-field full"><div class="rv-label">Description</div><div class="rv-desc"><?php echo htmlspecialchars($house['description'] ?: 'No description provided.'); ?></div></div>
            </div>
        </div>

        <?php if(count($amenities) > 0): ?>
        <div class="rv-card">
            <div class="rv-title"><i class="fas fa-star"></i> Selected Amenities</div>
            <div class="rv-amenities">
                <?php foreach($amenities as $a): ?>
                    <span class="rv-amenity"><i class="<?php echo htmlspecialchars($a['icon'] ?: 'fas fa-check'); ?>"></i> <?php echo htmlspecialchars($a['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="rv-card">
            <div class="rv-title"><i class="fas fa-images"></i> Photos (<?php echo count($imgs); ?>)</div>
            <div class="rv-photos">
                <?php if(count($imgs) > 0): ?>
                    <?php foreach($imgs as $i => $ph): ?>
                        <img src="uploads/<?php echo htmlspecialchars($ph); ?>" alt="Photo <?php echo $i+1; ?>" loading="lazy" onclick="openZoom('uploads/<?php echo htmlspecialchars($ph); ?>')">
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-img" style="width:100%;height:160px;background:#f1f5f9;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px"><i class="fas fa-image" style="margin-right:8px"></i> No Image</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($is_pending): ?>
        <div class="rv-card">
            <div class="rvactions">
                    <form action="admin_actions.php" method="POST" style="flex:1;min-width:180px">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="approve_review">
                        <input type="hidden" name="id" value="<?php echo (int)$house['id']; ?>">
                        <button type="submit" class="btn btn-success" style="width:100%"><i class="fas fa-check"></i> Approve Listing</button>
                    </form>
                    <form action="admin_actions.php" method="POST" style="flex:1;min-width:180px">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reject_review">
                        <input type="hidden" name="id" value="<?php echo (int)$house['id']; ?>">
                        <button type="submit" class="btn btn-danger-ghost" style="width:100%"><i class="fas fa-times"></i> Reject Listing</button>
                    </form>
                </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="zoomBox" class="lb-dark" onclick="closeZoom()" title="Click to close">
    <img id="zoomImg" src="" alt="Full size photo">
</div>
<script>
function openZoom(src){
    document.getElementById('zoomImg').src = src;
    document.getElementById('zoomBox').classList.add('open');
}
function closeZoom(){
    document.getElementById('zoomBox').classList.remove('open');
}
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeZoom();
});
</script>
</body>
</html>