<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');
include('includes/security.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    header("Location: login.php");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT houses.*, users.full_name FROM houses JOIN users ON houses.user_id = users.id WHERE NOT (houses.status = 'Pending' OR houses.is_approved = 0 OR houses.is_approved IS NULL)";

if($status_filter == 'Available') {
    $page_title = "Active Listings";
    $sql .= " AND (houses.status = '0' OR houses.status = 'Available')";
} elseif($status_filter == 'Rented') {
    $page_title = "Occupied Units";
    $sql .= " AND (houses.status = '1' OR houses.status = 'Rented')";
} else {
    $page_title = "All Properties";
}

$sql .= " ORDER BY houses.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AdamaRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-building"></i></div>
            <div>
                <h1><?php echo $page_title; ?></h1>
                <div class="page-sub">Manage all property listings</div>
            </div>
        </div>
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="admin_manage_houses.php" class="filter-tab <?= ($status_filter=='all')?'active':'' ?>">All</a>
                <a href="admin_manage_houses.php?status=Available" class="filter-tab <?= ($status_filter=='Available')?'active':'' ?>">Available</a>
                <a href="admin_manage_houses.php?status=Rented" class="filter-tab <?= ($status_filter=='Rented')?'active':'' ?>">Rented</a>
            </div>
        </div>
    </div>
    
    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Landlord</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>  
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><span class="badge badge-gray">#<?php echo $row['id']; ?></span></td>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                    <td>Kebele <?php echo htmlspecialchars($row['kebele']); ?></td>
                    <td><strong><?php echo number_format($row['amount']); ?> ETB</strong></td>
                    <td>
                        <?php 
                        $status_val = $row['status'] ?? '';
                        if($status_val === '0' || strcasecmp($status_val, 'Available') === 0)
                            echo '<span class="status-chip chip-available"><i class="fas fa-circle" style="font-size:6px"></i> Available</span>';
                        elseif($status_val === '1' || strcasecmp($status_val, 'Rented') === 0)
                            echo '<span class="status-chip chip-rented"><i class="fas fa-circle" style="font-size:6px"></i> Rented</span>';
                        elseif(strcasecmp($status_val, 'Pending') === 0 || $row['is_approved'] == 0)
                            echo '<span class="status-chip chip-pending"><i class="fas fa-circle" style="font-size:6px"></i> Pending</span>';
                        else
                            echo '<span class="status-chip chip-unknown">Unknown</span>';
                        ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                        <button class="btn-icon btn-icon-view" title="View"
                            onclick="showDetails(<?php echo htmlspecialchars(json_encode((string)$row['full_name']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode(number_format($row['amount'])), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode((string)($row['description'] ?? '')), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode((string)$row['image']), ENT_QUOTES); ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <form action="process_request.php" method="POST" style="display:inline" id="del-house-<?php echo $row['id']; ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="action" value="delete_house">
                            <button type="button" class="btn-icon btn-icon-del" title="Delete" onclick="confirmListingDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Property Details</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImg" src="" class="detail-img">
            <p><strong>Landlord:</strong> <span id="modalLandlord"></span></p>
            <p><strong>Price:</strong> <span id="modalPrice"></span> ETB/month</p>
            <p><strong>Description:</strong></p>
            <div id="modalDesc" style="background:#f8fafc;padding:14px;border-radius:8px;font-size:14px;line-height:1.6;color:#475569"></div>
        </div>
    </div>
</div>
</body>
</html>
