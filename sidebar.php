<?php
if (session_status() !== PHP_SESSION_ACTIVE) { include('session_config.php'); @session_start(); }
if(!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar,.sidebar *{box-sizing:border-box;margin:0;padding:0}
.sidebar{width:260px;background:linear-gradient(180deg,#0f172a 0%,#0c1524 100%);color:#fff;height:100vh;position:fixed;top:0;left:0;padding:24px 16px;border-right:1px solid rgba(255,255,255,.04);font-family:'Inter',system-ui,sans-serif;z-index:50;display:flex;flex-direction:column}
.sidebar-brand{display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:8px;padding:0 8px}
.sidebar-brand-icon{width:38px;height:38px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:15px;flex-shrink:0}
.sidebar-brand-text{color:#fff;font-size:17px;font-weight:800;letter-spacing:-.3px}
.sidebar-brand-text span{color:#2dd4bf}
.sidebar-label{font-size:11px;font-weight:600;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:1px;padding:20px 12px 8px}
.sidebar .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:rgba(255,255,255,.65);text-decoration:none;border-radius:10px;margin-bottom:4px;transition:all .2s;font-size:14px;font-weight:500}
.sidebar .nav-link i{width:20px;text-align:center;font-size:15px;opacity:.7}
.sidebar .nav-link:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.95);transform:translateX(4px)}
.sidebar .nav-link.active{background:linear-gradient(135deg,rgba(13,148,136,.2),rgba(13,148,136,.1));color:#2dd4bf;box-shadow:inset 3px 0 0 #0d9488}
.sidebar .nav-link.active i{opacity:1}
.sidebar-footer{margin-top:auto;padding:12px 0;border-top:1px solid rgba(255,255,255,.06)}
.sidebar-footer a{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;background:rgba(239,68,68,.1);color:#fca5a5;transition:all .2s}
.sidebar-footer a:hover{background:rgba(239,68,68,.2);color:#fca5a5}

/* Mobile: collapse the fixed sidebar into an in-flow horizontal nav bar */
@media(max-width:768px){
    .sidebar{position:static;width:100%;height:auto;flex-direction:row;align-items:center;gap:2px;padding:10px 14px;overflow-x:auto;justify-content:flex-start;-webkit-overflow-scrolling:touch}
    .sidebar-brand{margin-bottom:0;flex-shrink:0}
    .sidebar-label{display:none}
    .sidebar .nav-link{width:auto;flex-shrink:0;margin-bottom:0;padding:9px 12px;white-space:nowrap;font-size:13px}
    .sidebar .nav-link i{display:none}
    .sidebar-footer{margin-top:0;border-top:none;padding:0;flex-shrink:0}
    .sidebar-footer a{padding:9px 12px;white-space:nowrap}
}
</style>

<div class="sidebar">
    <a href="admin_panel.php" class="sidebar-brand">
        <div class="sidebar-brand-icon">AR</div>
        <div class="sidebar-brand-text">Adama<span>Rent</span></div>
    </a>
    <div class="sidebar-label">Admin Console</div>

    <a href="admin_panel.php" class="nav-link <?= ($current_page=='admin_panel.php') ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> Dashboard
    </a>
    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1): ?>
    <a href="admin_manage_users.php" class="nav-link <?= ($current_page=='admin_manage_users.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Manage Users
    </a>
    <?php endif; ?>
    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 2): ?>
    <a href="admin_invite.php" class="nav-link <?= ($current_page=='admin_invite.php') ? 'active' : '' ?>">
        <i class="fas fa-user-shield"></i> Invite Admin
    </a>
    <?php endif; ?>
    <a href="admin_manage_houses.php" class="nav-link <?= ($current_page=='admin_manage_houses.php') ? 'active' : '' ?>">
        <i class="fas fa-building"></i> Manage Houses
    </a>
    <a href="admin_manage_requests.php" class="nav-link <?= ($current_page=='admin_manage_requests.php') ? 'active' : '' ?>">
        <i class="fas fa-inbox"></i> Requests
    </a>

    <div class="sidebar-footer">
        <a href="logout.php" data-no-ajax="1"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
</div>
