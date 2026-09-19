<?php
include('includes/session_config.php');
session_start();
include('includes/db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    header("Location: login.php"); 
    exit();
}

$landlords = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin = 0"));
$admins    = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE is_admin >= 1"));
$total_h   = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM houses WHERE NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)"));

$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE (status = '0' OR status = 'Available') AND NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)");
$active_listings = $res_active ? mysqli_fetch_assoc($res_active)['total'] : 0;

$res_rented = mysqli_query($conn, "SELECT COUNT(*) as total FROM houses WHERE (status = '1' OR status = 'Rented') AND NOT (status = 'Pending' OR is_approved = 0 OR is_approved IS NULL)");
$occupied_units = $res_rented ? mysqli_fetch_assoc($res_rented)['total'] : 0;

$pending_req = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM requests WHERE status = 0"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AdamaRent Admin</title>
    <?php include(__DIR__ . '/includes/header.php'); ?>
</head>
<body>
    <main class="content">
        <div class="page-header">
            <div class="page-title">
                <div class="icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <h1>Dashboard</h1>
                    <div class="page-sub">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong> &mdash; <?php echo ($_SESSION['is_admin'] == 2) ? 'Super Admin' : 'Staff'; ?></div>
                </div>
            </div>
        </div>

        <section class="stats-grid">
            <a href="admin_manage_users.php?role=landlords" class="stat-link">
                <div class="stat-box accent-blue">
                    <h3>Total Landlords</h3>
                    <div class="number"><?php echo $landlords; ?></div>
                    <i class="fas fa-users"></i>
                </div>
            </a>
            <a href="admin_manage_users.php?role=staff" class="stat-link">
                <div class="stat-box accent-purple">
                    <h3>Internal Staff</h3>
                    <div class="number"><?php echo $admins; ?></div>
                    <i class="fas fa-user-shield"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php" class="stat-link">
                <div class="stat-box accent-dark">
                    <h3>Total Inventory</h3>
                    <div class="number"><?php echo $total_h; ?></div>
                    <i class="fas fa-building"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php?status=Available" class="stat-link">
                <div class="stat-box accent-green">
                    <h3>Active Listings</h3>
                    <div class="number"><?php echo $active_listings; ?></div>
                    <i class="fas fa-check-double"></i>
                </div>
            </a>
            <a href="admin_manage_houses.php?status=Rented" class="stat-link">
                <div class="stat-box accent-orange">
                    <h3>Occupied Units</h3>
                    <div class="number"><?php echo $occupied_units; ?></div>
                    <i class="fas fa-key"></i>
                </div>
            </a>
            <a href="admin_manage_requests.php" class="stat-link">
                <div class="stat-box accent-red">
                    <h3>Pending Approvals</h3>
                    <div class="number"><?php echo $pending_req; ?></div>
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </a>
        </section>

        <div class="info-bar">
            <div class="info-icon"><i class="fas fa-info"></i></div>
            <div><strong>Note:</strong> "Active Listings" shows properties marked as Available. Once marked as Rented by a landlord or admin, they move to "Occupied Units."</div>
        </div>
    </main>
</body>
</html>
