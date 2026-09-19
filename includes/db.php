<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// DB credentials come from config_secrets.php (gitignored so each machine/host
// uses its own values). Falls back to local XAMPP defaults when not defined.
if (file_exists(__DIR__ . '/../config/config_secrets.php')) {
    require_once __DIR__ . '/../config/config_secrets.php';
}
$host   = defined('DB_HOST') ? DB_HOST : "localhost";
$user   = defined('DB_USER') ? DB_USER : "root";
$pass   = defined('DB_PASS') ? DB_PASS : "";
$dbname = defined('DB_NAME') ? DB_NAME : "rental_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS house_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    amenity_id INT NOT NULL,
    INDEX idx_house (house_id),
    UNIQUE KEY unique_house_amenity (house_id, amenity_id)
)");

$amenity_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM amenities"))[0];
if ($amenity_count == 0) {
    $seeds = [
        ['Water Supply', 'fas fa-droplet', 1],
        ['Electricity', 'fas fa-bolt', 2],
        ['WiFi', 'fas fa-wifi', 3],
        ['Parking', 'fas fa-square-parking', 4],
        ['Furnished', 'fas fa-couch', 5],
        ['Air Conditioning', 'fas fa-snowflake', 6],
        ['Security Guard', 'fas fa-shield-halved', 7],
        ['CCTV', 'fas fa-video', 8],
        ['Generator', 'fas fa-car-battery', 9],
        ['Elevator', 'fas fa-arrow-up', 10],
        ['Garden', 'fas fa-seedling', 11],
        ['Swimming Pool', 'fas fa-person-swimming', 12],
        ['Laundry', 'fas fa-shirt', 13],
        ['Kitchen', 'fas fa-utensils', 14],
        ['Balcony', 'fas fa-house-chimney', 15],
    ];
    foreach ($seeds as $s) {
        $name = mysqli_real_escape_string($conn, $s[0]);
        $icon = mysqli_real_escape_string($conn, $s[1]);
        mysqli_query($conn, "INSERT INTO amenities (name, icon, sort_order) VALUES ('$name', '$icon', {$s[2]})");
    }
}

