<?php
// Admin Home (v6) — SIMPLE HOME ONLY. One job: show the big picture
// and point you to the right page. No lists, no forms here.
// To do a job, tap one of the big cards below.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$counts = ['clients' => 0, 'inquiries' => 0, 'new_inquiries' => 0, 'users' => 0, 'unread' => 0];
if ($db) {
    try { $counts['clients'] = (int) $db->query("SELECT COUNT(*) FROM clients WHERE status <> 'archived'")->fetchColumn(); } catch (Throwable $e) {}
    try { $counts['inquiries'] = (int) $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn(); } catch (Throwable $e) {} try { $counts['new_inquiries'] = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn(); } catch (Throwable $e) {}
    try { $counts['users'] = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch (Throwable $e) {}
    $counts['unread'] = count_unread_messages($db);
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Umer Sajid</title><link rel="stylesheet" href="../css/style.css?v=8"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>Dashboard</h1>
  <p class="page-intro">Hello, Admin 👋 — this is your dashboard. It only shows the numbers. To do anything, tap one of the big boxes below — each box is one job.</p>
  <?php if (!$db): ?><p class="notice">Database not connected yet — numbers will show here once the database is connected.</p><?php endif; ?>

  <?php if ($counts['new_inquiries'] > 0): ?> <div class="alert-new"> <p><strong>🔴 You have <?php echo $counts['new_inquiries']; ?> new inquir<?php echo $counts['new_inquiries'] > 1 ? 'ies' : 'y'; ?>!</strong></p> <p>Someone asked a question from the Contact form. It stays here until you set its stage to Contacted or Done.</p> <p><a class="btn" href="inquiries.php?tab=new">Open New Inquiries →</a></p> </div> <?php endif; ?> <?php if ($counts['unread'] > 0): ?>
    <div class="alert-new">
      <p><strong>🔴 You have <?php echo $counts['unread']; ?> new message<?php echo $counts['unread'] > 1 ? 's' : ''; ?>!</strong></p>
      <p>Someone wrote to you. Tap below to read and reply.</p>
      <p><a class="btn" href="messages.php">Read New Messages →</a></p>
    </div>
  <?php endif; ?> <?php if ($counts['new_inquiries'] === 0 && $counts['unread'] === 0): ?>
    <p class="chat-empty">✅ No new inquiries and no new messages right now.</p>
  <?php endif; ?>

  <h2>What do you want to do?</h2>
  <div class="admin-menu-cards">
    <a class="menu-card" href="messages.php"><span class="menu-card-icon">💬</span><strong>Messages</strong><span>Read new messages and reply to users</span><span>Open →</span></a>
    <a class="menu-card" href="clients.php"><span class="menu-card-icon">👥</span><strong>Clients</strong><span>See your clients, archive or delete them</span><span>Open →</span></a>
    <a class="menu-card" href="add-client.php"><span class="menu-card-icon">➕</span><strong>Add Client</strong><span>Add one new client</span><span>Open →</span></a>
    <a class="menu-card" href="users.php"><span class="menu-card-icon">🧑</span><strong>Users</strong><span>See people who registered on your site</span><span>Open →</span></a>
    <a class="menu-card" href="inquiries.php"><span class="menu-card-icon">📩</span><strong>Inquiries<?php if ($counts['new_inquiries'] > 0): ?> (<?php echo $counts['new_inquiries']; ?> new)<?php endif; ?></strong><span>First questions from the Contact form — no account needed to send one</span><span>Open →</span></a>
    <a class="menu-card" href="blog.php"><span class="menu-card-icon">📝</span><strong>Blog</strong><span>Write and publish a new blog post</span><span>Open →</span></a>
    <a class="menu-card" href="settings.php"><span class="menu-card-icon">⚙️</span><strong>Settings</strong><span>Change your website text without coding</span><span>Open →</span></a>
  </div>
  <p class="muted">Numbers at a glance — Clients: <?php echo $counts['clients']; ?> · Inquiries: <?php echo $counts['inquiries']; ?> (<?php echo $counts['new_inquiries']; ?> new) · Registered users: <?php echo $counts['users']; ?> · New messages: <?php echo $counts['unread']; ?>. Logged in as <?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>.</p>
  <p><a href="../index.php">View your website →</a></p>
</section>
</body>
</html>
