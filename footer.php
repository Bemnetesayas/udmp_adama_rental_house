<footer style="background:#0f172a;color:rgba(255,255,255,.7);padding:60px 24px 30px;font-family:'Inter',system-ui,sans-serif">
    <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px">
        <div>
            <h3 style="color:#fff;font-size:20px;font-weight:800;margin-bottom:12px">Adama<span style="color:#2dd4bf">Rent</span></h3>
            <p style="font-size:14px;line-height:1.8;margin-bottom:20px">The trusted digital marketplace for property rentals in Adama City. Connecting landlords and tenants directly.</p>
            <div style="display:flex;gap:10px">
                <a href="https://t.me/Get_dope" target="_blank" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:rgba(0,136,204,.2);color:#0088cc;transition:all .3s;font-size:16px"><i class="fab fa-telegram-plane"></i></a>
                <a href="https://facebook.com/Getdope" target="_blank" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:rgba(24,119,242,.2);color:#1877f2;transition:all .3s;font-size:16px"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com/get_dope_men" target="_blank" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:rgba(225,48,108,.2);color:#e1306c;transition:all .3s;font-size:16px"><i class="fab fa-instagram"></i></a>
                <a href="mailto:getahun930@gmail.com" style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:rgba(234,67,53,.2);color:#ea4335;transition:all .3s;font-size:16px"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
        <div>
            <h4 style="color:#fff;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">Quick Links</h4>
            <a href="index.php" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0;transition:color .2s">Browse Properties</a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'post_house.php' : 'register.php'; ?>" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0;transition:color .2s">List Your Property</a>
            <a href="login.php" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0;transition:color .2s">Sign In</a>
        </div>
        <div>
            <h4 style="color:#fff;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">Categories</h4>
            <a href="index.php?cat=Single+Home" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0">Single Homes</a>
            <a href="index.php?cat=Apartment" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0">Apartments</a>
            <a href="index.php?cat=Villa" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0">Villas</a>
            <a href="index.php?cat=Shop" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0">Shops</a>
        </div>
        <div>
            <h4 style="color:#fff;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">Contact</h4>
            <a href="#" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0"><i class="fas fa-location-dot" style="color:#2dd4bf;margin-right:6px"></i> Adama, Oromia, Ethiopia</a>
            <a href="tel:+251910407721" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0"><i class="fas fa-phone" style="color:#2dd4bf;margin-right:6px"></i> +251 910 407 721</a>
            <a href="tel:+251938272728" style="display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;padding:4px 0"><i class="fas fa-phone" style="color:#2dd4bf;margin-right:6px"></i> +251 938 272 728</a>
        </div>
    </div>
    <div style="max-width:1100px;margin:40px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);text-align:center;font-size:13px;color:rgba(255,255,255,.4)">
        &copy; <?php echo date("Y"); ?> AdamaRent. All rights reserved.
    </div>
</footer>
