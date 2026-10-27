<?php
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/services.php';
$db = get_db_connection();
$key = trim($_GET['slug'] ?? $_GET['id'] ?? '');
$service = $key ? find_service($key) : null;
if (!$service && $db) {
    try {
        $stmt = $db->prepare("SELECT title, slug, description FROM services WHERE slug = ? OR id = ? LIMIT 1");
        $stmt->execute([$key, is_numeric($key) ? (int) $key : 0]);
        $row = $stmt->fetch();
        if ($row) $service = ['slug' => $row['slug'] ?? '', 'title' => $row['title'], 'short' => $row['description'], 'detail' => $row['description'], 'included' => [], 'for' => 'Anyone who needs this service, done properly and on time.', 'icon' => '✨'];
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $service ? htmlspecialchars($service['title']) . ' — Umer Sajid' : 'Service not found — Umer Sajid'; ?></title>
<link rel="stylesheet" href="css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="index.php">Home</a><a href="index.php#services">Services</a><a href="user/login.php" class="nav-profile" title="User Panel" aria-label="User Panel"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="currentColor"/><path d="M4 20c0-4.4 3.6-8 8-8s8 3.6 8 8" fill="currentColor"/></svg><span class="sr-only">User Panel</span></a></nav></header>
<main>
<?php if (!$service): ?>
  <section><h1>Service not found</h1><p>That service does not exist. Please choose one from the <a href="index.php#services">services list</a>.</p></section>
<?php else: ?>
  <section class="service-detail">
    <p class="service-icon"><?php echo htmlspecialchars($service['icon'] ?? '✨'); ?></p>
    <span class="tag"><?php echo htmlspecialchars($service['title']); ?></span>
    <h1><?php echo htmlspecialchars($service['title']); ?></h1>
    <p class="service-lead"><?php echo htmlspecialchars($service['detail']); ?></p>
    <blockquote class="motivation">“Dream big, work honestly, and let every small step take you further.”</blockquote>
    <?php if (!empty($service['included'])): ?>
      <h2>What's included</h2>
      <ul class="checklist">
        <?php foreach ($service['included'] as $item): ?><li><?php echo htmlspecialchars($item); ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <h2>Who it's for</h2>
    <p><?php echo htmlspecialchars($service['for']); ?></p>
    <p>
      <a class="btn" href="index.php?service=<?php echo urlencode($service['slug']); ?>#contact">Start this service →</a>
      <a class="btn btn-outline" href="user/register.php">Register &amp; start in your panel</a>
    </p>
    <p class="muted">Not sure yet? Send a message with your idea — I will reply in your portal with an honest plan and next steps.</p>
  </section>
<?php endif; ?>
</main>
<footer><p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p><p><a href="user/login.php">User Panel</a></p><p class="footer-admin"><a href="admin/login.php">Admin</a></p></footer>
<script src="js/assistant.js"></script>
</body>
</html>
