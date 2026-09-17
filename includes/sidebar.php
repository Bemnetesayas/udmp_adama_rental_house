<?php
if (session_status() !== PHP_SESSION_ACTIVE) { include(__DIR__ . '/session_config.php'); @session_start(); }
if(!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar,.sidebar *{box-sizing:border-box;margin:0;padding:0}
.sidebar{width:260px;background:linear-gradient(180deg,#0f172a 0%,#0c1524 100%);color:#fff;height:100vh;position:fixed;top:0;left:0;border-right:1px solid rgba(255,255,255,.04);font-family:'Inter',system-ui,sans-serif;z-index:50;display:flex;flex-direction:column;transition:width .28s cubic-bezier(.4,0,.2,1),padding .28s cubic-bezier(.4,0,.2,1);overflow:hidden;white-space:nowrap}

/* Header row: brand left, toggle right */
.sidebar-header{display:flex;align-items:center;justify-content:space-between;padding:20px 16px 12px;border-bottom:1px solid rgba(255,255,255,.06);min-height:64px}
.sidebar-brand{display:flex;align-items:center;gap:10px;border-bottom:none;padding:0;min-height:auto;user-select:none}
.sidebar-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px;flex-shrink:0}
.sidebar-brand-icon .brand-toggle-icon{display:none;font-size:15px;line-height:1}
.sidebar-brand-text{color:#fff;font-size:17px;font-weight:800;letter-spacing:-.3px;opacity:1;transition:opacity .2s}
.sidebar-brand-text span{color:#2dd4bf}

/* Toggle button — inside header, right side */
.sidebar-toggle{margin-left:8px;width:34px;height:34px;border-radius:8px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);box-shadow:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .28s cubic-bezier(.4,0,.2,1);color:rgba(255,255,255,.75);font-size:15px;flex-shrink:0}
.sidebar-toggle:hover{background:rgba(255,255,255,.12);border-color:#0d9488;color:#2dd4bf;box-shadow:0 2px 12px rgba(13,148,136,.2)}
.sidebar-toggle i{transition:transform .25s}

.sidebar-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1.2px;padding:20px 16px 8px;opacity:1;transition:opacity .2s}
.sidebar .nav-link{display:flex;align-items:center;gap:12px;padding:11px 16px;color:rgba(255,255,255,.6);text-decoration:none;border-radius:0;margin-bottom:0;transition:all .2s;font-size:13px;font-weight:500;border-left:3px solid transparent}
.sidebar .nav-link i{width:20px;text-align:center;font-size:15px;opacity:.6;flex-shrink:0;transition:opacity .2s}
.sidebar .nav-link span{opacity:1;transition:opacity .2s}
.sidebar .nav-link:hover{background:rgba(255,255,255,.04);color:rgba(255,255,255,.9)}
.sidebar .nav-link.active{background:rgba(13,148,136,.1);color:#2dd4bf;border-left-color:#0d9488}
.sidebar .nav-link.active i{opacity:1}
.sidebar-section-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1.2px;padding:24px 16px 8px;opacity:1;transition:opacity .2s}
.sidebar-footer{margin-top:auto;padding:12px 16px;border-top:1px solid rgba(255,255,255,.06)}
.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;background:rgba(239,68,68,.08);color:#fca5a5;transition:all .2s}
.sidebar-footer a:hover{background:rgba(239,68,68,.18);color:#fff}
.sidebar-footer a span{opacity:1;transition:opacity .2s}

/* Overlay (mobile) */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:45;backdrop-filter:blur(2px);transition:opacity .25s;opacity:0}

/* ---- Collapsed (desktop): brand becomes the toggle on hover + icon tooltips ---- */
body.sidebar-collapsed .sidebar{width:72px;padding:0;overflow:visible;transition:width .3s ease}
body.sidebar-collapsed .sidebar-header{justify-content:center;padding:16px 0;gap:0;min-height:auto;border-bottom-color:transparent}
body.sidebar-collapsed .sidebar-brand{width:100%;justify-content:center;cursor:pointer}
body.sidebar-collapsed .sidebar-brand-icon{width:28px;height:28px;font-size:12px;border-radius:8px;transition:all .2s}
body.sidebar-collapsed .sidebar-brand:hover .sidebar-brand-icon{background:rgba(255,255,255,.12);color:#2dd4bf;box-shadow:0 2px 10px rgba(13,148,136,.25)}
body.sidebar-collapsed .sidebar-brand:hover .brand-mark{display:none}
body.sidebar-collapsed .sidebar-brand:hover .brand-toggle-icon{display:block}
body.sidebar-collapsed .sidebar-toggle{display:none}
body.sidebar-collapsed .sidebar-brand-text,
body.sidebar-collapsed .sidebar .sidebar-label,
body.sidebar-collapsed .sidebar .sidebar-section-label{display:none}
body.sidebar-collapsed .sidebar .nav-link{width:52px;height:44px;margin:2px auto;padding:0;justify-content:center;align-items:center;border-left:none;border-radius:12px;position:relative}
body.sidebar-collapsed .sidebar .nav-link i{margin:0;opacity:.75}
body.sidebar-collapsed .sidebar .nav-link:hover{background:rgba(255,255,255,.08);transform:none;color:#fff}
body.sidebar-collapsed .sidebar .nav-link.active{background:linear-gradient(135deg,rgba(13,148,136,.35),rgba(13,148,136,.15));color:#2dd4bf;box-shadow:0 2px 10px rgba(13,148,136,.25)}
body.sidebar-collapsed .sidebar .nav-link.active i{opacity:1}
body.sidebar-collapsed .sidebar-footer{padding:16px 0;margin-top:auto}
body.sidebar-collapsed .sidebar-footer a{width:52px;height:44px;margin:2px auto;padding:0;justify-content:center;border-radius:12px;position:relative}
/* Icon tooltips (collapsed rail) */
body.sidebar-collapsed .sidebar .nav-link span,
body.sidebar-collapsed .sidebar .sidebar-footer a span{
    display:block;position:absolute;left:calc(100% + 10px);top:50%;
    transform:translateY(-50%) translateX(-6px);
    white-space:nowrap;background:#0f172a;color:#e2e8f0;
    font-size:12px;font-weight:600;letter-spacing:.2px;
    padding:7px 11px;border-radius:6px;
    border:1px solid rgba(255,255,255,.08);
    box-shadow:0 6px 18px rgba(0,0,0,.4);
    opacity:0;visibility:hidden;z-index:80;
    transition:opacity .18s ease,transform .18s ease,visibility .18s;
}
body.sidebar-collapsed .sidebar .nav-link span::before,
body.sidebar-collapsed .sidebar .sidebar-footer a span::before{
    content:'';position:absolute;left:-6px;top:50%;transform:translateY(-50%);
    border:6px solid transparent;border-right-color:#0f172a;
}
body.sidebar-collapsed .sidebar .nav-link:hover span,
body.sidebar-collapsed .sidebar .sidebar-footer a:hover span{
    opacity:1;visibility:visible;transform:translateY(-50%) translateX(0);
}
body.sidebar-collapsed .content,
body.sidebar-collapsed .main-content,
body.sidebar-collapsed .main{margin-left:72px;width:calc(100% - 72px)}

/* ---- Mobile: fixed horizontal icon bar ---- */
@media(max-width:768px){
    .sidebar{position:fixed;top:0;left:0;width:100%;height:auto;flex-direction:row;overflow-x:auto;overflow-y:hidden;white-space:nowrap;border-radius:0;padding:0;gap:2px;justify-content:flex-end}
    .sidebar-header{position:absolute;left:14px;top:50%;transform:translateY(-50%);z-index:1;padding:12px 0;border-bottom:none;min-height:auto}
    .sidebar-brand-text,.sidebar .sidebar-label,.sidebar .sidebar-section-label,
    .sidebar .nav-link span,.sidebar .sidebar-footer span{display:none}
    .sidebar .nav-link{padding:12px 16px;border-left:none;white-space:nowrap}
    .sidebar-toggle{display:none}
    .sidebar-footer{margin-top:0;border-top:none;border-left:1px solid rgba(255,255,255,.1);padding:0}
    .sidebar-footer a{padding:12px 16px}
    .sidebar-overlay{display:none !important}
    .content,.main-content,.main{margin-left:0;width:100%;padding:16px;padding-top:64px}
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <span class="brand-mark">AR</span>
                <i class="fas fa-bars brand-toggle-icon"></i>
            </div>
            <div class="sidebar-brand-text">Adama<span>Rent</span></div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-label">Admin Console</div>

    <a href="admin_panel.php" class="nav-link <?= ($current_page=='admin_panel.php') ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
    </a>
    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 1): ?>
    <a href="admin_manage_users.php" class="nav-link <?= ($current_page=='admin_manage_users.php') ? 'active' : '' ?>">
        <i class="fas fa-users"></i> <span>Manage Users</span>
    </a>
    <?php endif; ?>
    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] >= 2): ?>
    <a href="admin_invite.php" class="nav-link <?= ($current_page=='admin_invite.php') ? 'active' : '' ?>">
        <i class="fas fa-user-shield"></i> <span>Invite Admin</span>
    </a>
    <?php endif; ?>
    <a href="admin_manage_houses.php" class="nav-link <?= ($current_page=='admin_manage_houses.php') ? 'active' : '' ?>">
        <i class="fas fa-building"></i> <span>Manage Houses</span>
    </a>
    <a href="admin_manage_requests.php" class="nav-link <?= ($current_page=='admin_manage_requests.php') ? 'active' : '' ?>">
        <i class="fas fa-inbox"></i> <span>Requests</span>
    </a>

    <div class="sidebar-footer">
        <a href="logout.php" data-no-ajax="1"><i class="fas fa-right-from-bracket"></i> <span>Sign Out</span></a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
(function(){
    var sidebar = document.getElementById('sidebar');
    var toggle  = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');
    var isMobile = window.matchMedia('(max-width:768px)').matches;

    function setIcon(){
        var open = isMobile ? document.body.classList.contains('sidebar-open') : !document.body.classList.contains('sidebar-collapsed');
        toggle.innerHTML = open ? '<i class="fas fa-xmark"></i>' : '<i class="fas fa-bars"></i>';
    }

    function applyMode(){
        if(isMobile){
            document.body.classList.remove('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-open');
            if(localStorage.getItem('ar_sidebar') === 'collapsed'){
                document.body.classList.add('sidebar-collapsed');
            }
        }
        setIcon();
    }

    toggle.addEventListener('click', function(){
        if(isMobile){
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('ar_sidebar', document.body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'open');
        }
        setIcon();
    });

    /* Brand acts as toggle when the sidebar is collapsed */
    sidebar.querySelector('.sidebar-brand').addEventListener('click', function(){
        if(!isMobile && document.body.classList.contains('sidebar-collapsed')){
            document.body.classList.remove('sidebar-collapsed');
            localStorage.setItem('ar_sidebar', 'open');
            setIcon();
        }
    });

    overlay.addEventListener('click', function(){
        document.body.classList.remove('sidebar-open');
        setIcon();
    });

    sidebar.querySelectorAll('.nav-link, .sidebar-footer a').forEach(function(a){
        a.addEventListener('click', function(){
            document.body.classList.remove('sidebar-open');
            setIcon();
        });
    });

    applyMode();

    window.addEventListener('resize', function(){
        var nowMobile = window.matchMedia('(max-width:768px)').matches;
        if(nowMobile !== isMobile){
            isMobile = nowMobile;
            applyMode();
        }
    });
})();
</script>
