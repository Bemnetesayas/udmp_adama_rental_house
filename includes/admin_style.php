/* ===== AdamaRent Admin Shared Styles ===== */
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;margin:0}

.main-content,.main{margin-left:260px;padding:40px;width:calc(100% - 260px);min-height:100vh;transition:margin-left .28s cubic-bezier(.4,0,.2,1),width .28s cubic-bezier(.4,0,.2,1)}

/* Layout */
.content{margin-left:260px;padding:40px;width:calc(100% - 260px);min-height:100vh;transition:margin-left .28s cubic-bezier(.4,0,.2,1),width .28s cubic-bezier(.4,0,.2,1)}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.page-title{display:flex;align-items:center;gap:12px}
.page-title h1{font-size:24px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
.page-title .icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.page-title h1 i{color:#0d9488}
.page-sub{color:#64748b;font-size:14px;margin-top:4px}

/* Cards & tables */
.data-card{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0}
.card{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.05);border:1px solid #e2e8f0;padding:24px;margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th{background:#f8fafc;padding:14px 16px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:1px solid #e2e8f0}
td{padding:14px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#374151}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafbfc}

/* Status badges */
.badge{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:5px;letter-spacing:.3px}
.badge-green{background:rgba(16,185,129,.12);color:#059669}
.badge-orange{background:rgba(245,158,11,.12);color:#d97706}
.badge-red{background:rgba(239,68,68,.12);color:#dc2626}
.badge-gray{background:rgba(100,116,139,.12);color:#475569}
.badge-purple{background:rgba(139,92,246,.12);color:#7c3aed}

.status-chip{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
.chip-available{background:rgba(16,185,129,.12);color:#059669}
.chip-rented{background:rgba(239,68,68,.12);color:#dc2626}
.chip-pending{background:rgba(245,158,11,.12);color:#d97706}
.chip-unknown{background:#f1f5f9;color:#64748b}

/* Buttons */
.btn{padding:9px 16px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.btn-primary{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
.btn-primary:hover{box-shadow:0 4px 14px rgba(13,148,136,.35)}
.btn-success{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger-ghost{background:rgba(239,68,68,.08);color:#dc2626}
.btn-danger-ghost:hover{background:#ef4444;color:#fff}
.btn-outline{background:transparent;border:1.5px solid #e2e8f0;color:#475569}
.btn-outline:hover{border-color:#0d9488;color:#0d9488}
.btn-sm{padding:6px 12px;border-radius:7px;font-size:12px}

/* Action icon buttons */
.btn-icon{width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s;font-size:13px}
.btn-icon-edit{background:rgba(13,148,136,.1);color:#0d9488}
.btn-icon-edit:hover{background:#0d9488;color:#fff}
.btn-icon-del{background:rgba(239,68,68,.1);color:#dc2626}
.btn-icon-del:hover{background:#ef4444;color:#fff}
.btn-icon-view{background:rgba(14,165,233,.1);color:#0284c7}
.btn-icon-view:hover{background:#0284c7;color:#fff}
.btn-icon-promote{background:rgba(139,92,246,.1);color:#7c3aed}
.btn-icon-promote:hover{background:#7c3aed;color:#fff}
.btn-icon-revoke{background:rgba(245,158,11,.1);color:#d97706}
.btn-icon-revoke:hover{background:#d97706;color:#fff}

/* Flash messages */
.flash{padding:14px 18px;border-radius:10px;font-size:14px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.flash-green{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.flash-red{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
.flash-blue{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8}

/* Forms */
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
input[type=text],input[type=email],input[type=password],input[type=number],select,textarea{
    width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:#334155;transition:all .2s}
input:focus,select:focus,textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.12)}

/* Role badges */
.badge-role{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.role-super{background:rgba(139,92,246,.12);color:#7c3aed}
.role-admin{background:rgba(245,158,11,.12);color:#d97706}
.role-landlord{background:rgba(100,116,139,.12);color:#475569}

/* Filters / toolbar */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center}
.filter-bar .filter-tabs{display:flex;background:#e2e8f0;border-radius:10px;padding:4px;gap:4px}
.filter-bar .filter-tab{padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:#64748b;transition:all .2s}
.filter-bar .filter-tab.active{background:#fff;color:#0d9488;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.filter-bar .filter-tab:hover{color:#0f172a}

/* Empty state */
.empty-state{text-align:center;padding:60px 20px;background:#fff;border-radius:14px;border:1px solid #e2e8f0}
.empty-state i{font-size:48px;color:#cbd5e1;margin-bottom:16px}
.empty-state h3{font-size:18px;font-weight:700;color:#475569;margin-bottom:8px}
.empty-state p{color:#94a3b8;font-size:14px}

/* Modal */
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(15,23,42,.5);backdrop-filter:blur(4px)}
.modal-content{background:#fff;margin:6% auto;width:90%;max-width:520px;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:modalIn .25s ease}
@keyframes modalIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.modal-header{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:16px;font-weight:700;color:#0f172a}
.close-btn{background:none;border:none;font-size:22px;cursor:pointer;color:#94a3b8;width:32px;height:32px;border-radius:8px;transition:all .2s}
.close-btn:hover{background:#f1f5f9;color:#0f172a}
.modal-body{padding:24px}

/* ===== Dashboard stats ===== */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.stat-link{text-decoration:none;color:inherit;display:block}
.stat-box{background:#fff;padding:24px;border-radius:14px;border:1px solid #e2e8f0;transition:all .3s;position:relative;overflow:hidden}
.stat-box:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.07);border-color:#cbd5e1}
.stat-box h3{font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0;font-weight:600}
.stat-box .number{font-size:34px;font-weight:800;margin:12px 0 0}
.stat-box i{position:absolute;right:-8px;bottom:-8px;font-size:64px;opacity:.04}
.accent-blue{border-top:3px solid #0d9488}
.accent-purple{border-top:3px solid #8b5cf6}
.accent-dark{border-top:3px solid #0f172a}
.accent-green{border-top:3px solid #10b981}
.accent-orange{border-top:3px solid #f59e0b}
.accent-red{border-top:3px solid #ef4444}
.accent-blue .number{color:#0d9488}
.accent-purple .number{color:#8b5cf6}
.accent-dark .number{color:#0f172a}
.accent-green .number{color:#10b981}
.accent-orange .number{color:#f59e0b}
.accent-red .number{color:#ef4444}
.info-bar{margin-top:32px;background:#fff;border:1px solid #e2e8f0;padding:20px;border-radius:14px;font-size:14px;color:#475569;display:flex;align-items:center;gap:16px}
.info-bar .info-icon{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ===== User cell (manage users) ===== */
.cell-user{display:flex;align-items:center;gap:12px}
.user-avatar-ms{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
.user-email{font-size:12px;color:#94a3b8}

/* ===== House detail modal (manage houses) ===== */
.detail-img{width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:16px}
.modal-body p{margin:8px 0;font-size:14px;color:#374151}
.modal-body strong{color:#0f172a}

/* ===== Request cards (manage requests) ===== */
.request-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:16px;display:flex;gap:20px;transition:all .3s}
.request-card:hover{border-color:#cbd5e1;box-shadow:0 6px 18px rgba(0,0,0,.05);transform:translateY(-2px)}
.req-img{flex:0 0 200px}
.req-img img{width:200px;height:140px;object-fit:cover;border-radius:10px}
.req-img .no-img{width:200px;height:140px;background:#f1f5f9;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px}
.req-body{flex:1}
.req-body h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:8px}
.req-meta{display:flex;flex-wrap:wrap;gap:16px;font-size:13px;color:#64748b;margin-bottom:8px}
.req-meta strong{color:#374151}
.req-meta span{display:inline-flex;align-items:center;gap:4px}
.req-actions{display:flex;flex-direction:column;gap:8px;align-items:flex-end;justify-content:center;min-width:120px}
.section-title{font-size:16px;font-weight:700;color:#0f172a;margin:28px 0 14px;display:flex;align-items:center;gap:8px}
.section-title i{color:#0d9488}

/* ===== Invite admin ===== */
.card h3{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:4px}
.card .desc{font-size:13px;color:#64748b;margin-bottom:16px}
.key-box{background:#0f172a;color:#2dd4bf;padding:14px 16px;border-radius:10px;font-family:monospace;font-size:16px;font-weight:700;letter-spacing:1px;margin:12px 0;word-break:break-all}

/* ===== Edit user form ===== */
.form-card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:32px;max-width:500px}
.form-card h2{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:4px;display:flex;align-items:center;gap:10px}
.form-card .sub{font-size:13px;color:#94a3b8;margin-bottom:24px}
.form-group{margin-bottom:20px}
.btn-row{display:flex;gap:10px;margin-top:24px}

/* Responsive */
@media(max-width:768px){
    .main-content,.main,.content{margin-left:0;padding:16px;padding-top:64px;width:100%;transition:none}
    th,td{padding:10px 12px}
    .data-card{overflow-x:auto;-webkit-overflow-scrolling:touch}
    .data-card table{min-width:640px}
    .req-img{flex:0 0 100px}
    .req-img img,.req-img .no-img{width:100px;height:80px}
    .request-card{flex-direction:column}
    .req-actions{flex-direction:row;align-items:center;justify-content:flex-start;min-width:0}
}
