<?php
// Public Blog list — only Published posts, newest first. All output is
// escaped; post bodies are never executed as HTML.
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/content.php';
$db = get_db_connection();
$posts = get_published_posts($db);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog — Umer Sajid | AI &amp; Technology</title>
<meta name="description" content="AI and technology blog by Umer Sajid — new posts on AI, software and modern technology.">
<link rel="stylesheet" href="css/style.css?v=4"></head>
<body>
<header><div class="header-brand"><strong>Umer Sajid</strong></div>
<nav><a href="index.php#about">About</a> <a href="index.php#services">Services</a> <a href="blog.php">Blog</a> <a href="index.php#contact">Contact</a> <a href="user/login.php" class="nav-cta">User Panel</a></nav></header>
<main>
<section>
  <h1>Blog</h1>
  <p class="section-lead">AI, software and modern technology — new posts, newest first.</p>
  <?php if (!$db): ?><p class="notice">Database not connected yet — blog posts will appear here once the database is connected.</p>
  <?php elseif (empty($posts)): ?><p>No published posts yet — check back soon.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($posts as $p): ?>
        <div class="card">
          <p class="muted"><?php echo htmlspecialchars($p['created_at']); ?></p>
          <h3><a href="blog-post.php?slug=<?php echo urlencode($p['slug']); ?>"><?php echo htmlspecialchars($p['title']); ?></a></h3>
          <?php if (!empty($p['excerpt'])): ?><p><?php echo htmlspecialchars($p['excerpt']); ?></p><?php endif; ?>
          <p><a href="blog-post.php?slug=<?php echo urlencode($p['slug']); ?>">Read post →</a></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
</main>
<footer><p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p>
<p><a href="blog.php">Blog</a> · <a href="admin/login.php">Admin</a></p></footer>
</body>
</html>
