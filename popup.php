<?php
/* Shared professional popup system: toasts + confirm modal */
?>
<style>
    /* Toast notifications */
    .pd-toast-wrap{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:12px;max-width:380px}
    .pd-toast{display:flex;align-items:flex-start;gap:12px;background:#fff;border-radius:12px;padding:14px 16px;box-shadow:0 10px 30px rgba(0,0,0,.15);border:1px solid #f1f5f9;border-left:4px solid #0d9488;animation:pdToastIn .3s cubic-bezier(.34,1.56,.64,1);min-width:280px}
    .pd-toast.removing{animation:pdToastOut .25s ease forwards}
    @keyframes pdToastIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    @keyframes pdToastOut{to{opacity:0;transform:translateX(30px)}}
    .pd-toast .pd-toast-icon{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0}
    .pd-toast.pd-success{border-left-color:#10b981}
    .pd-toast.pd-success .pd-toast-icon{background:#10b981}
    .pd-toast.pd-error{border-left-color:#ef4444}
    .pd-toast.pd-error .pd-toast-icon{background:#ef4444}
    .pd-toast.pd-info{border-left-color:#3b82f6}
    .pd-toast.pd-info .pd-toast-icon{background:#3b82f6}
    .pd-toast-body{flex:1}
    .pd-toast-title{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:2px}
    .pd-toast-msg{font-size:13px;color:#64748b;line-height:1.5}
    .pd-toast-close{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;line-height:1;padding:2px}

    /* Confirm modal */
    .pd-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:20px;animation:pdfadeIn .25s ease}
    .pd-overlay.pd-active{display:flex}
    @keyframes pdfadeIn{from{opacity:0}to{opacity:1}}
    .pd-confirm{background:#fff;border-radius:18px;max-width:420px;width:100%;padding:32px 28px;box-shadow:0 25px 60px rgba(0,0,0,.3);text-align:center;animation:pdPop .3s cubic-bezier(.34,1.56,.64,1)}
    @keyframes pdPop{from{opacity:0;transform:scale(.93)}to{opacity:1;transform:scale(1)}}
    .pd-confirm-icon{width:68px;height:68px;margin:0 auto 18px;border-radius:50%;background:#fef2f2;color:rgba(239,68,68,.9);border:1px solid #fecaca;display:flex;align-items:center;justify-content:center;font-size:26px}
    .pd-confirm h3{font-size:19px;font-weight:800;color:#0f172a;margin-bottom:8px}
    .pd-confirm p{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:24px}
    .pd-confirm .pd-confirm-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .pd-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;border:none;font-family:inherit;text-decoration:none}
    .pd-btn-cancel{background:#f1f5f9;color:#334155}
    .pd-btn-cancel:hover{background:#e2e8f0}
    .pd-btn-danger{background:#ef4444;color:#fff}
    .pd-btn-danger:hover{background:#dc2626;box-shadow:0 6px 18px rgba(239,68,68,.35);transform:translateY(-1px)}
    @media(max-width:480px){
        .pd-toast-wrap{right:16px;left:16px;max-width:none}
        .pd-toast{min-width:0}
        .pd-confirm .pd-confirm-actions{grid-template-columns:1fr}
    }
</style>
<script>
(function(){
    // Toast container
    var wrap = document.createElement('div');
    wrap.className = 'pd-toast-wrap';
    document.body.appendChild(wrap);

    function showToast(message, type, title, duration){
        type = type || 'success';
        var icons = {success:'fa-check', error:'fa-circle-xmark', info:'fa-circle-info'};
        var titles = {success: title || 'Success', error: title || 'Error', info: title || 'Notice'};
        var toast = document.createElement('div');
        toast.className = 'pd-toast pd-' + type;
        toast.innerHTML =
            '<div class="pd-toast-icon"><i class="fas ' + (icons[type]||icons.info) + '"></i></div>' +
            '<div class="pd-toast-body">' +
                '<div class="pd-toast-title"></div>' +
                '<div class="pd-toast-msg"></div>' +
            '</div>' +
            '<button class="pd-toast-close">&times;</button>';
        toast.querySelector('.pd-toast-title').textContent = titles[type];
        toast.querySelector('.pd-toast-msg').textContent = message;
        toast.querySelector('.pd-toast-close').addEventListener('click', function(){ removeToast(toast); });
        wrap.appendChild(toast);
        var t = duration || 3500;
        var timer = setTimeout(function(){ removeToast(toast); }, t);
        toast.addEventListener('mouseenter', function(){ clearTimeout(timer); });
        toast.addEventListener('mouseleave', function(){ timer = setTimeout(function(){ removeToast(toast); }, t); });
    }
    function removeToast(toast){
        if(!toast.parentNode) return;
        toast.classList.add('removing');
        setTimeout(function(){ if(toast.parentNode) toast.parentNode.removeChild(toast); }, 250);
    }

    // Confirm modal
    function openConfirm(opts){
        opts = opts || {};
        var overlay = document.createElement('div');
        overlay.className = 'pd-overlay pd-active';
        overlay.innerHTML =
            '<div class="pd-confirm">' +
                '<div class="pd-confirm-icon"><i class="fas fa-triangle-exclamation"></i></div>' +
                '<h3></h3>' +
                '<p></p>' +
                '<div class="pd-confirm-actions">' +
                    '<button class="pd-btn pd-btn-cancel" data-act="cancel">Cancel</button>' +
                    '<button class="pd-btn pd-btn-danger" data-act="confirm"><i class="fas fa-trash-can"></i> Delete</button>' +
                '</div>' +
            '</div>';
        overlay.querySelector('h3').textContent = opts.title || 'Are you sure?';
        overlay.querySelector('p').textContent = opts.message || 'This action cannot be undone.';
        if(opts.confirmText) overlay.querySelector('[data-act="confirm"]').lastChild.textContent = ' ' + opts.confirmText;
        var done = false;
        function close(cb){
            if(done) return; done = true;
            overlay.classList.remove('pd-active');
            setTimeout(function(){ if(overlay.parentNode) overlay.parentNode.removeChild(overlay); }, 250);
            if(cb) cb();
        }
        overlay.querySelector('[data-act="cancel"]').addEventListener('click', function(){ close(); });
        overlay.querySelector('[data-act="confirm"]').addEventListener('click', function(){ close(opts.onConfirm); });
        overlay.addEventListener('click', function(e){ if(e.target === overlay) close(); });
        document.body.appendChild(overlay);
    }

    window.showToast = showToast;
    window.adamaConfirm = openConfirm;
})();
</script>
