<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/services.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_user_login();
$db = get_db_connection();
$msg = '';
$uid = (int) $_SESSION['user_id'];
// Send a message to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (send_message($db, $uid, 'user', $_POST['message'] ?? '')) $msg = 'Message sent to admin.';
    else $msg = 'Message could not be sent — database may not be connected yet.';
}
// Send a new inquiry from inside the panel (becomes an order-like request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_inquiry']) && $db) {
    try {
        $stmt = $db->prepare("INSERT INTO inquiries (name, email, service, message, status) VALUES (?, ?, ?, ?, 'new')");
        $stmt->execute([$_SESSION['user_name'], $_SESSION['user_email'], trim($_POST['service'] ?? ''), trim($_POST['message'] ?? '')]);
        $msg = 'Your request was saved. It now shows below as an order with status "new".';
    } catch (Throwable $e) { $msg = 'Request could not be saved.'; }
}
$profile = null; $services = []; $myInquiries = []; $messages = [];
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1"); $stmt->execute([$uid]); $profile = $stmt->fetch();
    } catch (Throwable $e) {}
    try { $services = $db->query("SELECT title, slug, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll(); } catch (Throwable $e) {}
    try {
        $stmt = $db->prepare("SELECT id, service, message, status, created_at FROM inquiries WHERE email = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_email']]); $myInquiries = $stmt->fetchAll();
    } catch (Throwable $e) {}
    $messages = get_user_messages($db, $uid);
}
if (empty($services)) {
    foreach (service_catalogue() as $slug => $s) $services[] = ['title' => $s['title'], 'slug' => $slug, 'description' => $s['short']];
}
function status_badge($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Panel — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Your Panel</strong><nav><a href="../index.php">Home</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?> 👋</h1>
  <?php if ($msg): ?><p><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — your profile, requests and messages will appear here once the database is connected.</p><?php endif; ?>

  <h2>Your Profile</h2>
  <div class="card profile-card">
    <p><strong><?php echo htmlspecialchars($profile['name'] ?? $_SESSION['user_name'] ?? ''); ?></strong> · <?php echo htmlspecialchars($profile['email'] ?? $_SESSION['user_email'] ?? ''); ?></p>
    <?php if ($profile): ?>
      <p class="muted">
        <?php if (!empty($profile['city'])) echo '📍 ' . htmlspecialchars($profile['city']) . ' · '; ?>
        <?php if (!empty($profile['phone'])) echo '📞 ' . htmlspecialchars($profile['phone']) . ' · '; ?>
        <?php if (!empty($profile['service_needed'])) echo '🎯 ' . htmlspecialchars($profile['service_needed']); ?>
      </p>
      <?php if (!empty($profile['project_detail'])): ?><p><?php echo htmlspecialchars($profile['project_detail']); ?></p><?php endif; ?>
      <?php if (!empty($profile['budget_range'])): ?><p class="muted">Budget: <?php echo htmlspecialchars($profile['budget_range']); ?></p><?php endif; ?>
    <?php else: ?><p class="muted">Profile detail will show here after registration with the detailed form.</p><?php endif; ?>
  </div>

  <h2>Browse Services — like a shop</h2>
  <p class="muted">Pick a service to see its detail page, then start it from there or from the form below.</p>
  <div class="grid">
    <?php foreach ($services as $s):
      $slug = $s['slug'] ?? ''; if (!$slug) { $f = find_service($s['title']); $slug = $f['slug'] ?? ''; }
    ?>
      <div class="card product-card">
        <h3><?php echo htmlspecialchars($s['title']); ?></h3>
        <p><?php echo htmlspecialchars($s['description']); ?></p>
        <p>
          <?php if ($slug): ?><a class="btn btn-small" href="../service.php?slug=<?php echo urlencode($slug); ?>">View Details</a> <?php endif; ?>
          <a href="../index.php?service=<?php echo urlencode($slug ?: $s['title']); ?>#contact">Start →</a>
        </p>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>Your Requests / Orders (<?php echo count($myInquiries); ?>)</h2>
  <?php if (empty($myInquiries)): ?>
    <p>No requests yet. Use the form below to place your first request.</p>
  <?php else: ?>
    <table>
      <tr><th>Service</th><th>Message</th><th>Status</th><th>Created</th></tr>
      <?php foreach ($myInquiries as $q): ?>
        <tr><td><?php echo htmlspecialchars($q['service'] ?? '—'); ?></td><td><?php echo htmlspecialchars($q['message']); ?></td><td><?php echo status_badge($q['status']); ?></td><td><?php echo htmlspecialchars($q['created_at']); ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h2>New Request</h2>
  <form method="post">
    <input type="hidden" name="new_inquiry" value="1">
    <label>Service
      <select name="service">
        <?php foreach (service_catalogue() as $s): ?><option value="<?php echo htmlspecialchars($s['title']); ?>"><?php echo htmlspecialchars($s['title']); ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Your message <textarea name="message" rows="3" required></textarea></label>
    <button class="btn" type="submit">Send Request</button>
  </form>

  <h2>Messages with Admin (<?php echo count($messages); ?>)</h2>
  <p class="muted">Admin replies to your requests appear here. Reply below and the admin will see it in their panel.</p>
  <?php if (empty($messages)): ?><p>No messages yet.</p><?php else: ?>
    <div class="chat">
      <?php foreach ($messages as $m): ?>
        <div class="chat-msg <?php echo $m['sender_role'] === 'admin' ? 'from-admin' : 'from-user'; ?>">
          <strong><?php echo $m['sender_role'] === 'admin' ? 'Admin' : 'You'; ?></strong>
          <span class="muted"><?php echo htmlspecialchars($m['created_at']); ?></span>
          <p><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="send_message" value="1">
    <label>Write to admin <textarea name="message" rows="3" required></textarea></label>
    <button class="btn" type="submit">Send Message</button>
  </form>
</section>
</body>
</html>
