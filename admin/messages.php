<?php
// Messages (v6) — ONLY messages. One job: read new messages and reply.
// Top: new (unread) messages, grouped by user. Below: every user's
// thread. Tap a user to see their full history and reply.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
$selectedUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_message']) && $selectedUser) {
    if (send_message($db, $selectedUser, 'admin', $_POST['message'] ?? '')) $message = 'Message sent — it now shows in that user\'s portal.';
    else $message = 'Message could not be sent — database may not be connected yet.';
}
$threads = []; $users = []; $messages = []; $selectedUserInfo = null;
if ($db) {
    $threads = get_message_threads($db);
    try { $users = $db->query("SELECT id, name, email FROM users ORDER BY name ASC")->fetchAll(); } catch (Throwable $e) {}
    if ($selectedUser) {
        foreach ($users as $u) if ((int) $u['id'] === $selectedUser) $selectedUserInfo = $u;
        if (!$selectedUserInfo) {
            try { $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1"); $stmt->execute([$selectedUser]); $selectedUserInfo = $stmt->fetch() ?: null; } catch (Throwable $e) {}
        }
        mark_thread_read($db, $selectedUser);
        $messages = get_user_messages($db, $selectedUser);
    }
}
$newThreads = array_values(array_filter($threads, function ($t) { return (int) $t['unread'] > 0; }));
function preview_msg($text, $len = 120) { $t = trim((string) $text); if (function_exists('mb_strlen')) return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t; return strlen($t) > $len ? substr($t, 0, $len) . '…' : $t; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>💬 Messages</h1>
  <p class="page-intro">This page is only for messages. Read a new message here, then reply — the user will see your reply in their own panel.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — messages will show here once the database is connected.</p><?php endif; ?>

  <div class="admin-section">
  <h2>🔴 New Messages</h2>
  <p class="muted">These are messages you have not read yet. Tap one to open it.</p>
  <?php if (empty($newThreads)): ?><p class="chat-empty">✅ No new messages right now.</p><?php else: ?>
    <?php foreach ($newThreads as $t): ?>
      <div class="card thread-card">
        <p><strong><?php echo htmlspecialchars($t['name'] ?: 'User #' . (int) $t['user_id']); ?></strong> · <?php echo htmlspecialchars($t['email'] ?? ''); ?>
        <span class="unread-badge"><?php echo (int) $t['unread']; ?> new</span></p>
        <p class="muted"><?php echo htmlspecialchars($t['last_at']); ?> — “<?php echo htmlspecialchars(preview_msg($t['last_message'])); ?>”</p>
        <p><a class="btn btn-small" href="messages.php?user_id=<?php echo (int) $t['user_id']; ?>">Open chat →</a></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <div class="admin-section">
  <h2>Messages by User</h2>
  <p class="muted">Every person has one row. Tap "Open chat" to see that person's full history in one place.</p>
  <?php if (empty($threads)): ?><p>No messages yet.</p><?php else: ?>
    <table>
      <tr><th>Person</th><th>Email</th><th>Messages</th><th>Latest Message</th><th>When</th><th>Open</th></tr>
      <?php foreach ($threads as $t): ?>
        <tr>
          <td><?php echo htmlspecialchars($t['name'] ?: 'User #' . (int) $t['user_id']); ?></td>
          <td><?php echo htmlspecialchars($t['email'] ?? ''); ?></td>
          <td><?php echo (int) $t['total']; ?><?php if ((int) $t['unread'] > 0): ?> <span class="unread-badge"><?php echo (int) $t['unread']; ?> new</span><?php endif; ?></td>
          <td><?php echo htmlspecialchars(preview_msg($t['last_message'])); ?></td>
          <td><?php echo htmlspecialchars($t['last_at']); ?></td>
          <td><a href="messages.php?user_id=<?php echo (int) $t['user_id']; ?>">Open chat →</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  </div>

  <?php if ($selectedUser): ?>
  <div class="admin-section">
  <h2>Chat with <?php echo $selectedUserInfo ? htmlspecialchars($selectedUserInfo['name']) : 'User #' . $selectedUser; ?></h2>
  <?php if ($selectedUserInfo): ?><p class="muted"><?php echo htmlspecialchars($selectedUserInfo['email']); ?></p><?php endif; ?>
  <?php if (empty($messages)): ?><p class="chat-empty">No messages yet — start the conversation below.</p><?php else: ?>
    <div class="chat-box" id="chatBox">
      <?php foreach ($messages as $m): ?>
        <div class="chat-msg <?php echo $m['sender_role'] === 'admin' ? 'from-admin' : 'from-user'; ?>">
          <div class="chat-meta"><strong><?php echo $m['sender_role'] === 'admin' ? 'You (Admin)' : 'User'; ?></strong>
          <span><?php echo htmlspecialchars($m['created_at']); ?></span></div>
          <p><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="messages.php?user_id=<?php echo $selectedUser; ?>">
    <input type="hidden" name="user_id" value="<?php echo $selectedUser; ?>">
    <label>Write your reply <textarea name="message" rows="3" required></textarea></label>
    <button class="btn" type="submit" name="send_admin_message" value="1">Send Reply</button>
  </form>
  </div>
  <?php endif; ?>
  <p><a href="dashboard.php">← Back to Admin Home</a></p>
</section>
</body>
</html>
