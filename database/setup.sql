-- AdamaRent Database Setup (structural only — NO auto admin account)
-- Run this once after cloning, OR just visit setup.php in your browser.

CREATE DATABASE IF NOT EXISTS rental_db;
USE rental_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    phone2 VARCHAR(20) DEFAULT '',
    password VARCHAR(255) NOT NULL,
    is_admin INT DEFAULT 0,
    status INT DEFAULT 0,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verify_token VARCHAR(64) NULL,
    verify_expires DATETIME NULL
);

CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kebele VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    house_number VARCHAR(50),
    category VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    phone VARCHAR(50) NOT NULL,
    map_link TEXT,
    image VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    video_file VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Pending',
    is_approved INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delete_key VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_id INT NOT NULL,
    status INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS house_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_house (house_id)
);

CREATE TABLE IF NOT EXISTS admin_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT
);

CREATE TABLE IF NOT EXISTS rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'info',
    title VARCHAR(120) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read)
);

CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS house_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    amenity_id INT NOT NULL,
    INDEX idx_house (house_id),
    UNIQUE KEY unique_house_amenity (house_id, amenity_id)
);

CREATE TABLE IF NOT EXISTS app_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT
);

-- Seed amenities
INSERT INTO amenities (name, icon, sort_order) VALUES
('Water Supply', 'fas fa-droplet', 1),
('Electricity', 'fas fa-bolt', 2),
('WiFi', 'fas fa-wifi', 3),
('Parking', 'fas fa-square-parking', 4),
('Furnished', 'fas fa-couch', 5),
('Air Conditioning', 'fas fa-snowflake', 6),
('Security Guard', 'fas fa-shield-halved', 7),
('CCTV', 'fas fa-video', 8),
('Generator', 'fas fa-car-battery', 9),
('Elevator', 'fas fa-arrow-up', 10),
('Garden', 'fas fa-seedling', 11),
('Swimming Pool', 'fas fa-person-swimming', 12),
('Laundry', 'fas fa-shirt', 13),
('Kitchen', 'fas fa-utensils', 14),
('Balcony', 'fas fa-house-chimney', 15);

-- NOTE: No admin account is created here for security.
-- After running setup.php, visit the generated admin setup key page,
-- then register your first account with that key to become admin.
