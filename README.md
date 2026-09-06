# 🏠 AdamaRent — Property Rental Marketplace

A PHP/MySQL rental house marketplace for **Adama City, Ethiopia** (plain PHP, no framework). Landlords list properties and tenants browse/search them; there is a landlord dashboard and an admin console for managing users, houses, and approvals.

**Stack:** PHP · Apache (XAMPP/LAMPP) · MySQL (mysqli) · HTML/CSS/JS

---

## ✅ Requirements

- **XAMPP** (or LAMPP on Linux) with Apache + MySQL running
- PHP **7.4+** (project developed on **8.2**)
- Project folder placed inside `htdocs/`
  - Windows: `C:\xampp\htdocs\`
  - Linux: `/opt/lampp/htdocs/`

---

## 🚀 Installation (First-Time Setup)

### 1️⃣ Start XAMPP
- **XAMPP Control Panel** → Start **Apache** and **MySQL**.

### 2️⃣ Configure Database Credentials
Open `db.php` and match it to your MySQL:
```php
$host   = 'localhost';
$user   = 'root';
$pass   = '';          // default empty in XAMPP — change if yours has a password
$dbname = 'rental_db'; // database name (the app creates it for you)
```

### 3️⃣ Run the One-Click Setup
Open in your browser:
```
http://localhost/udmp_adama_rental_house/setup.php
```
This **automatically**:
- Creates the `rental_db` database
- Creates all required tables (`users`, `houses`, `requests`, `admin_invites`, `app_config`)
- Creates the `uploads/` folder for property images

> ⚠️ **Important:** `setup.php` does **NOT** create an admin account for you. You must register one using the setup key (next step).

### 4️⃣ Create the First Super Admin
1. Go to `register.php`.
2. Fill in your name, email, and password.
3. Tick **"I'm the site administrator"** to reveal the **Admin Setup Key** field.
4. Paste the key from step 3 and submit.
5. That account becomes the **Super Admin**.
6. The setup key is **destroyed** after this — it cannot be reused.

> Everyone else who registers normally (no key) becomes a **landlord**.

### 5️⃣ View Your Site
```
http://localhost/udmp_adama_rental_house/
```
The site loads the landing page (Home). Click **Browse** to see/search properties.

---

## 🔧 Troubleshooting

| Issue | Solution |
|-------|----------|
| **"Connection failed"** | Check `$pass` in `db.php` (XAMPP default is empty) and that MySQL is started. |
| **Database/tables missing** | Run `setup.php` once to create everything. |
| **Can't log in as admin** | You must **register the first Super Admin** via `setup.php` key (see Installation step 4). |
| **Setup key not showing** | The key only shows while **no admin exists** — once an admin is created it's destroyed. |
| **Landlord promoted but not admin** | They must enter the invite key at `admin_key.php` (they may have clicked "Skip for now"). |
| **Port 80/443 in use** | Stop Skype/Teams/IIS or change Apache ports in XAMPP. |
| **White screen / PHP errors** | Enable error reporting in `php.ini` or check Apache logs. |
| **Uploads not saving** | Ensure `uploads/` exists and is writable (Apache needs write access). |

---

**Need help?** Check the Apache/MySQL logs via the XAMPP Control Panel ("Logs" button), or open an issue on the repository.


