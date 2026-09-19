<?php
// admin_layout.php
function render_sidebar($current_page) {
    $rank = $_SESSION['is_admin'] ?? 0;
?>
<style>
    :root {
        --sidebar-bg: #0f172a; --brand: #6366f1; --bg: #f8fafc;
        --text: #1e293b; --muted: #64748b; --border: #e2e8f0;
        --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; }
    .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 30px 20px; display: flex; flex-direction: column; }
    .brand { color: #fff; font-weight: 800; font-size: 1.2rem; text-decoration: none; margin-bottom: 40px; display: block; }
    .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px; color: #94a3b8; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: 0.3s; }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: #fff; }
    .nav-link.active { background: var(--brand); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); }
    .content { margin-left: 260px; width: 100%; padding: 50px; }
    .stat-card { background: #fff; padding: 25px; border-radius: 15px; border: 1px solid var(--border); transition: 0.3s; text-decoration: none; color: inherit; display: block; }
    .stat-card:hover { transform: translateY(-5px); border-color: var(--brand); }
    .table-container { background: #fff; border-radius: 15px; border: 1px solid var(--border); overflow: hidden; margin-top: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 15px; background: #f9fafb; font-size: 12px; text-transform: uppercase; color: var(--muted); }
    td { padding: 15px; border-top: 1px solid var(--border); }
    .badge { padding: 4px 8px; border-radius: 5px; font-size: 11px; font-weight: 700; }
</style>

<aside class="sidebar">
    <a href="admin_panel.php" class="brand">ADAMA RENT</a>
    <a href="admin_panel.php" class="nav-link <?= ($current_page == 'admin_panel.php') ? 'active' : '' ?>">Dashboard</a>
    <a href="admin_manage_users.php" class="nav-link <?= ($current_page == 'admin_manage_users.php') ? 'active' : '' ?>">Landlords</a>
    <a href="admin_manage_houses.php" class="nav-link <?= ($current_page == 'admin_manage_houses.php') ? 'active' : '' ?>">Properties</a>
    <a href="admin_manage_requests.php" class="nav-link <?= ($current_page == 'admin_manage_requests.php') ? 'active' : '' ?>">Requests</a>
    
    <?php if($rank == 2): ?>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #1e293b;">
            <a href="admin_manage_staff.php" class="nav-link <?= ($current_page == 'admin_manage_staff.php') ? 'active' : '' ?>">Staff Management</a>
        </div>
    <?php endif; ?>
    
    <a href="logout.php" class="nav-link" style="margin-top: auto; color: var(--danger);">Logout</a>
</aside>
<?php } ?>