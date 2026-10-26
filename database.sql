-- Umer Sajid personal website — database schema
-- Import this file in phpMyAdmin (XAMPP locally, or cPanel / InfinityFree later).
-- No real user data is included, and no password hash is hard-coded here:
-- after importing, run setup.php ONCE in a browser to create the seed admin
-- account (placeholder password, forced to change on first login — see README.md),
-- then DELETE setup.php. Do not leave setup.php on a live site.

CREATE DATABASE IF NOT EXISTS umer_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE umer_website;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(50),
    service_needed VARCHAR(150),
    notes TEXT,
    status VARCHAR(30) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    service VARCHAR(150),
    message TEXT NOT NULL,
    status VARCHAR(30) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Starter service rows (shown on the front-end if the DB is connected).
INSERT INTO services (title, description) VALUES
('Website Building and Fixing', 'Personal and business websites, built folder-wise and fixed on existing sites.'),
('Video Editing', 'Short-form and cinematic video editing from your own clips.'),
('Voiceover', 'Natural voiceover in English and Urdu for videos and shorts.'),
('Scripts', 'Video scripts, captions and story ideas for social media.'),
('Social Media Posts and Management', 'Posts, captions, hashtags and posting plans for Facebook, Instagram and YouTube.');
