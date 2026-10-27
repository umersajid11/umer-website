-- Umer Sajid personal website — database schema
-- Import this file in phpMyAdmin (XAMPP locally, or cPanel / InfinityFree later).
-- No real user data is included, and no password hash is hard-coded here:
-- after importing, run setup.php ONCE in a browser to create the seed admin
-- account (placeholder password, forced to change on first login — see README.md),
-- then DELETE setup.php. Do not leave setup.php on a live site.
--
-- UPGRADE NOTE (existing live databases): the users table gained
-- phone, city, service_needed, project_detail and budget_range columns,
-- services gained a slug column, and a messages table was added.
-- For an existing database, run these before importing new code:
--   ALTER TABLE users ADD COLUMN phone VARCHAR(50) NULL AFTER email;
--   ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER phone;
--   ALTER TABLE users ADD COLUMN service_needed VARCHAR(150) NULL AFTER city;
--   ALTER TABLE users ADD COLUMN project_detail TEXT NULL AFTER service_needed;
--   ALTER TABLE users ADD COLUMN budget_range VARCHAR(100) NULL AFTER project_detail;
--   ALTER TABLE services ADD COLUMN slug VARCHAR(100) NULL AFTER title;
--   (Then import/create the messages table below, and update service slugs.)
--
-- V6.1 UPGRADE NOTE (existing live databases): run this before deploying
-- the chat Archive/Delete buttons in admin/messages.php. No existing
-- table is changed and no data is deleted by this statement:
--   CREATE TABLE IF NOT EXISTS archived_chats (
--       user_id INT UNSIGNED NOT NULL PRIMARY KEY,
--       archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
--   );
--
-- V5 UPGRADE NOTE (existing live databases): run these before deploying v5:
--   ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message;
--   -- 'archived' needs NO schema change: clients.status is VARCHAR(30),
--   -- so UPDATE clients SET status = 'archived' already works.
--   -- Then CREATE the blog_posts and site_settings tables below
--   -- (they use CREATE TABLE IF NOT EXISTS, so importing is safe).

CREATE DATABASE IF NOT EXISTS umer_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE umer_website;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(50) NULL,
    city VARCHAR(100) NULL,
    service_needed VARCHAR(150) NULL,
    project_detail TEXT NULL,
    budget_range VARCHAR(100) NULL,
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
    slug VARCHAR(100) NULL,
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

-- Admin <-> user messaging portal. sender_role is 'admin' or 'user';
-- user_id is the registered user the conversation belongs to.
-- Admin replies to an inquiry are also stored here when the inquiry
-- email matches a registered user, so they appear in that user's portal.
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    sender_role VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    inquiry_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_user (user_id)
);
-- is_read: 0 = a user-sent message the admin has not opened yet
-- (shown in Dashboard section 2, "New Messages"); 1 = seen / admin-sent.
-- Existing databases: run the ALTER in the V5 UPGRADE NOTE above.

-- v5 Blog (AdSense / SEO): public visitors only see status = 'published'.
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(200) NOT NULL UNIQUE,
    title VARCHAR(250) NOT NULL,
    excerpt TEXT NULL,
    body TEXT NOT NULL,
    cover_image VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_blog_status (status)
);

-- v6.1 Chat archiving: a user_id listed here has its thread hidden
-- from the main Messages list (shown again via "Show Archived Chats").
-- Archiving never deletes messages; deleting a chat is a separate
-- DELETE FROM messages WHERE user_id = ? action in admin/messages.php.
CREATE TABLE IF NOT EXISTS archived_chats (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- v5 Website settings: front-page wording editable from admin/settings.php.
-- Keys used: hero_title, hero_intro, motivation_quote, about_intro,
-- contact_notice. Editing these rows never touches config or code files.
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Starter service rows (shown on the front-end if the DB is connected).
INSERT INTO services (title, slug, description) VALUES
('Website Design', 'website-design', 'Modern, fast, mobile-friendly personal and business websites, built folder-wise.'),
('Website Fixing & Maintenance', 'website-fixing', 'Fix errors, broken pages and slow sites — and keep an existing site running smoothly.'),
('Video Editing', 'video-editing', 'Short-form and cinematic edits from your own clips — clean cuts, text and music.'),
('Voiceover & Scripts', 'voiceover-scripts', 'Natural voiceover in English and Urdu, plus scripts, captions and story ideas.'),
('Social Media Management', 'social-media-management', 'Posts, captions, hashtags and posting plans for Facebook, Instagram and YouTube.'),
('Logo & Thumbnail Design', 'logo-thumbnail', 'Clean logos and eye-catching thumbnails that make your page instantly recognisable.'),
('SEO & AdSense Setup', 'seo-adsense', 'Make your site search-friendly and ready for AdSense — setup only, approval is never guaranteed.'),
('Content Writing', 'content-writing', 'Clear, positive writing for websites, pages and videos — in simple, readable language.');
