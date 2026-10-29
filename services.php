<?php
// Dedicated Services page — ALL 8 catalogue services, moved off the
// shortened front page. DB rows overlay catalogue titles/descriptions
// where they match, exactly as on the front page; graceful fallback
// to the catalogue when the DB is not connected.
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/services.php';
$db = get_db_connection();
$services = [];
if ($db) {
    try { $services = $db->query("SELECT title, slug, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll(); } catch (Throwable $e) { $services = []; }
}
$merged = [];
foreach (service_catalogue() as $slug => $s) {
    $merged[$slug] = ['title' => $s['title'], 'slug' => $slug, 'description' => $s['short'], 'icon' => $s['icon']];
}
foreach ($services as $row) {
    $slug = $row['slug'] ?? '';
    if (!$slug) { $f = find_service($row['title']); $slug = $f['slug'] ?? ''; }
    if ($slug && isset($merged[$slug])) {
        $merged[$slug]['title'] = $row['title'] ?: $merged[$slug]['title'];
        $merged[$slug]['description'] = $row['description'] ?: $merged[$slug]['description'];
    } elseif (!empty($row['title'])) {
        $merged[$slug ?: $row['title']] = ['title' => $row['title'], 'slug' => $slug, 'description' => $row['description'] ?? '', 'icon' => '✦'];
    }
}
$services = array_values($merged);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Services — Umer Sajid</title>
<meta name="description" content="All services by Umer Sajid — website design and fixing, video editing, voiceover and scripts, social media, logos, SEO and content writing.">
<link rel="stylesheet" href="css/style.css?v=8">
</head>
<body>
<header>
  <div class="header-brand"><strong>Umer Sajid</strong></div>
  <nav>
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
    <a href="services.php">Services</a>
    <a href="blog.php">Blog</a>
    <a href="index.php#contact">Contact</a>
    <a href="user/login.php" class="nav-profile" title="User Panel" aria-label="User Panel"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><circle cx="12" cy="7" r="4" fill="#ffffff"/><path d="M12 12c-4.4 0-8 3.1-8 7.5V21h16v-1.5C20 15.1 16.4 12 12 12z" fill="#ffffff"/></svg><span class="sr-only">User Panel</span></a>
  </nav>
</header>
<main>
  <section id="services">
    <h2>Services</h2>
    <p>Click any service to see what is included, who it is for, and how to start it.</p>
    <?php if (!$db): ?><p class="notice">Database not connected yet — showing the built-in service list. Connect the database (see README.md) to load services from MySQL.</p><?php endif; ?>
    <div class="grid">
      <?php foreach ($services as $s):
        $slug = $s['slug'] ?? '';
        if (!$slug) { $found = find_service($s['title']); $slug = $found['slug'] ?? ''; }
      ?>
        <div class="card service-card">
          <p class="service-card-icon"><?php echo htmlspecialchars($s['icon'] ?? '✦'); ?></p>
          <h3><?php echo htmlspecialchars($s['title']); ?></h3>
          <p><?php echo htmlspecialchars($s['description']); ?></p>
          <?php if ($slug): ?><p><a href="service.php?slug=<?php echo urlencode($slug); ?>">View details →</a></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center"><a class="btn" href="index.php#contact">Work With Me</a></p>
  </section>
</main>
<footer>
  <p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p>
  <p><a href="about.php">About</a> · <a href="services.php">Services</a> · <a href="blog.php">Blog</a> · <a href="user/login.php">User Panel</a></p>
  <p class="footer-admin"><a href="admin/login.php">Admin</a></p>
</footer>
<script src="js/script.js"></script>
<script src="js/assistant.js"></script>
</body>
</html>
