<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_user_login();
$db = get_db_connection();
$services = []; $myInquiries = [];
if ($db) {
    try { $services = $db->query("SELECT title, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll(); } catch (Throwable $e) { $services = []; }
    try {
        $stmt = $db->prepare("SELECT id, message, status, created_at FROM inquiries WHERE email = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_email']]);
        $myInquiries = $stmt->fetchAll();
    } catch (Throwable $e) { $myInquiries = []; }
}
if (empty($services)) {
    $services = [
        ['title' => 'Website Building and Fixing', 'description' => 'Personal and business websites, built folder-wise and fixed on existing sites.'],
        ['title' => 'Video Editing', 'description' => 'Short-form and cinematic video editing from your own clips.'],
        ['title' => 'Voiceover', 'description' => 'Natural voiceover in English and Urdu for videos and shorts.'],
        ['title' => 'Scripts', 'description' => 'Video scripts, captions and story ideas for social media.'],
        ['title' => 'Social Media Posts and Management', 'description' => 'Posts, captions, hashtags and posting plans for Facebook, Instagram and YouTube.'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Panel — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>User Panel</strong><nav><a href="../index.php">Home</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></h1>
  <p>Logged in as <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>.</p>
  <?php if (!$db): ?><p class="notice">Database not connected yet — your inquiry history will appear here once the database is connected.</p><?php endif; ?>
  <h2>My Services</h2>
  <div class="grid">
    <?php foreach ($services as $s): ?>
      <div class="card"><h3><?php echo htmlspecialchars($s['title']); ?></h3><p><?php echo htmlspecialchars($s['description']); ?></p></div>
    <?php endforeach; ?>
  </div>
  <h2>My Inquiries (<?php echo count($myInquiries); ?>)</h2>
  <?php if (empty($myInquiries)): ?>
    <p>You have no inquiries yet. <a href="../index.php#contact">Send a new inquiry</a> from the home page using this same email address, and it will appear here.</p>
  <?php else: ?>
    <table>
      <tr><th>Message</th><th>Status</th><th>Created</th></tr>
      <?php foreach ($myInquiries as $q): ?>
        <tr><td><?php echo htmlspecialchars($q['message']); ?></td><td><?php echo htmlspecialchars($q['status']); ?></td><td><?php echo htmlspecialchars($q['created_at']); ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <div class="grid">
    <div class="card"><h3>Contact Umer</h3><p><a href="../index.php#contact">Send a new inquiry</a> from the home page.</p></div>
  </div>
</section>
</body>
</html>
