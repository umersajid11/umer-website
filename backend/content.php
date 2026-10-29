<?php
// Blog + site-settings helpers (v5). Every function degrades gracefully:
// if the DB is not connected, or the blog_posts / site_settings table
// does not exist yet on an old database, callers get an empty list or
// the supplied default — pages never crash. Output is always escaped
// by the calling page (htmlspecialchars); no raw HTML is executed.

// ---------- Site settings ----------
function site_setting_defaults() {
    return [
        'hero_title' => 'Umer Sajid',
        'hero_intro' => 'I build modern websites, create engaging videos, and help people grow online — with clear, honest work delivered on time.',
        'motivation_quote' => '“Dream big, work honestly, and let every small step take you further.”',
        'about_intro' => 'Hello, I am Umer Sajid — a software engineer, IT professional and creative content creator from Muzaffarabad, Azad Kashmir. I build modern websites, manage databases and create family-friendly, positive video content. My goal is simple: honest work, delivered on time, that you are proud to share.',
        'contact_notice' => 'Send an inquiry below. Serious inquiries only, please — tell me what you need, and I will reply in your portal and here. Inquiries are saved to the database once it is connected; otherwise this form is a preview only.',
    ];
}

function get_site_setting($db, $key, $default = '') {
    $defaults = site_setting_defaults();
    if (!isset($defaults[$key])) $defaults[$key] = $default;
    if (!$db) return $defaults[$key];
    try {
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') return $row['setting_value'];
    } catch (Throwable $e) {}
    return $defaults[$key];
}

function save_site_setting($db, $key, $value) {
    if (!$db) return false;
    try {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, trim($value)]);
    } catch (Throwable $e) { return false; }
}

// ---------- Blog ----------
function blog_slugify($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'post';
}

function get_published_posts($db) {
    if (!$db) return [];
    try {
        return $db->query("SELECT id, slug, title, excerpt, cover_image, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC, id DESC")->fetchAll();
    } catch (Throwable $e) { return []; }
}

function get_all_posts($db) {
    if (!$db) return [];
    try {
        return $db->query("SELECT id, slug, title, excerpt, body, cover_image, status, created_at, updated_at FROM blog_posts ORDER BY created_at DESC, id DESC")->fetchAll();
    } catch (Throwable $e) { return []; }
}

function get_published_post($db, $slug) {
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT id, slug, title, excerpt, body, cover_image, created_at FROM blog_posts WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function get_post_by_id($db, $id) {
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function save_blog_post($db, $id, $title, $slug, $excerpt, $body, $coverImage, $status) {
    if (!$db || trim($title) === '' || trim($body) === '') return false;
    $slug = $slug !== '' ? blog_slugify($slug) : blog_slugify($title);
    $status = ($status === 'published') ? 'published' : 'draft';
    try {
        if ($id) {
            $stmt = $db->prepare("UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, body = ?, cover_image = ?, status = ? WHERE id = ?");
            return $stmt->execute([trim($title), $slug, trim($excerpt), trim($body), trim($coverImage), $status, (int) $id]);
        }
        $stmt = $db->prepare("INSERT INTO blog_posts (slug, title, excerpt, body, cover_image, status) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$slug, trim($title), trim($excerpt), trim($body), trim($coverImage), $status]);
    } catch (Throwable $e) { return false; }
}

function delete_blog_post($db, $id) {
    if (!$db) return false;
    try {
        $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
        return $stmt->execute([(int) $id]);
    } catch (Throwable $e) { return false; }
}
