<?php
// Public single blog post — Published posts only, looked up by slug.
// Title/excerpt are used as SEO meta tags; body is escaped, never raw HTML.
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/content.php';
$db = get_db_connection();
$slug = trim($_GET['slug'] ?? '');
$post = $slug !== '' ? get_published_post($db, $slug) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $post ? htmlspecialchars($post['title']) . ' — Umer Sajid' : 'Post not found — Umer Sajid'; ?></title>
<?php if ($post && !empty($post['excerpt'])): ?><meta name="description" content="<?php echo htmlspecialchars($post['excerpt']); ?>"><?php endif; ?>
<link rel="stylesheet" href="css/style.css?v=5"></head>
<body>
<header><div class="header-brand"><strong>Umer Sajid</strong></div>
<nav><a href="index.php">Home</a> <a href="about.php">About</a> <a href="services.php">Services</a> <a href="blog.php">Blog</a> <a href="index.php#contact">Contact</a> <a href="user/login.php" class="nav-profile" title="User Panel" aria-label="User Panel"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="currentColor"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8" fill="currentColor"/></svg><span class="sr-only">User Panel</span></a></nav></header>
<main>
<section>
  <?php if (!$post): ?>
    <h1>Post not found</h1>
    <p>This post does not exist, or it is still a draft. <a href="blog.php">← Back to Blog</a></p>
  <?php else: ?>
    <p><a href="blog.php">← Back to Blog</a></p>
    <h1><?php echo htmlspecialchars($post['title']); ?></h1>
    <p class="muted"><?php echo htmlspecialchars($post['created_at']); ?></p>
    <?php if (!empty($post['cover_image'])): ?><p><img src="<?php echo htmlspecialchars($post['cover_image']); ?>" alt="" style="max-width:100%;border-radius:16px;"></p><?php endif; ?>
    <?php if (!empty($post['excerpt'])): ?><p><strong><?php echo htmlspecialchars($post['excerpt']); ?></strong></p><?php endif; ?>
    <p><?php echo nl2br(htmlspecialchars($post['body'])); ?></p>
  <?php endif; ?>
</section>
</main>
<footer><p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p>
<p><a href="blog.php">Blog</a> · <a href="user/login.php">User Panel</a> · <a href="admin/login.php">Admin</a></p></footer>
<script src="js/assistant.js"></script>
</body>
</html>
