<?php
// Admin Dashboard v5 — redesigned to be simple and user-friendly.
// Sections in order: 1 Overview, 2 New Messages, 3 Messages by User,
// 4 Clients, 5 Add Client (completely separate), 6 Registered Users,
// 7 Recent Inquiries. Auth/session logic is unchanged (backend/auth.php).
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
$selectedUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0);
$showArchived = !empty($_GET['show_archived']);

// --- Add a client (kept as its own section, section 5) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = trim($_POST['name'] ?? '');
    if (!$db) $message = 'Database not connected yet — client could not be saved.';
    elseif ($name === '') $message = 'Client name is required.';
    else {
        $stmt = $db->prepare("INSERT INTO clients (name, email, phone, service_needed, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['service_needed'] ?? ''), trim($_POST['notes'] ?? ''), trim($_POST['status'] ?? 'new')]);
        $message = 'Client added successfully.';
    }
}
// --- Update a client status (new / contacted / done) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = ? WHERE id = ?");
    $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['client_id']]);
    $message = 'Client status updated.';
}
// --- Archive / restore a client (archived clients are hidden by default) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_client'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = 'archived' WHERE id = ?");
    $stmt->execute([(int) $_POST['client_id']]);
    $message = 'Client archived — it is hidden from the main list until you choose Show Archived.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_client'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = 'new' WHERE id = ?");
    $stmt->execute([(int) $_POST['client_id']]);
    $message = 'Client restored to the main list.';
}
// --- Delete a client (confirmation is asked in the browser first) ---
// Also deletes that client's messages (matched by the client's email
// against registered users), never any other client's data.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'], $_POST['client_id']) && $db) {
    $cid = (int) $_POST['client_id'];
    $stmt = $db->prepare("SELECT email FROM clients WHERE id = ? LIMIT 1"); $stmt->execute([$cid]); $crow = $stmt->fetch();
    if ($crow && !empty($crow['email'])) {
        $uid = find_user_id_by_email($db, $crow['email']);
        if ($uid) {
            $del = $db->prepare("DELETE FROM messages WHERE user_id = ?"); $del->execute([$uid]);
        }
    }
    $stmt = $db->prepare("DELETE FROM clients WHERE id = ?"); $stmt->execute([$cid]);
    $message = 'Client deleted, together with that client\'s messages.';
}
// --- Update an inquiry status ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'], $_POST['inquiry_id']) && $db) {
    $stmt = $db->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
    $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['inquiry_id']]);
    $message = 'Inquiry status updated.';
}
// --- Admin sends a message to a registered user ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_message']) && $selectedUser) {
    if (send_message($db, $selectedUser, 'admin', $_POST['message'] ?? '')) $message = 'Message sent — it now shows in that user\'s portal.';
    else $message = 'Message could not be sent — database may not be connected yet.';
}
// --- Admin replies to an inquiry ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'], $_POST['inquiry_id']) && $db) {
    $iid = (int) $_POST['inquiry_id'];
    $stmt = $db->prepare("SELECT email FROM inquiries WHERE id = ? LIMIT 1"); $stmt->execute([$iid]); $row = $stmt->fetch();
    $uid = $row ? find_user_id_by_email($db, $row['email']) : null;
    if ($uid) {
        if (send_message($db, $uid, 'admin', $_POST['message'] ?? '', $iid)) { $message = 'Reply sent — it now shows in that user\'s portal.'; $selectedUser = $uid; }
        else $message = 'Reply could not be saved.';
    } else {
        $message = 'That inquiry email has no registered user portal yet — ask them to register with the same email, then reply here.';
    }
}

$clients = []; $visibleClients = []; $archivedClients = []; $inquiries = []; $users = []; $messages = []; $threads = []; $selectedUserInfo = null;
$counts = ['clients' => 0, 'inquiries' => 0, 'users' => 0, 'messages' => 0, 'unread' => 0];
$clientByStatus = ['new' => 0, 'contacted' => 0, 'done' => 0];
$inqByStatus = ['new' => 0, 'contacted' => 0, 'done' => 0];
if ($db) {
    try { $clients = $db->query("SELECT id, name, email, phone, service_needed, notes, status, created_at FROM clients ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) {}
    try { $inquiries = $db->query("SELECT id, name, email, service, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 50")->fetchAll(); } catch (Throwable $e) {}
    try { $users = $db->query("SELECT id, name, email, phone, city, service_needed, project_detail, budget_range, created_at FROM users ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) {}
    $threads = get_message_threads($db);
    $counts = ['clients' => count($clients), 'inquiries' => count($inquiries), 'users' => count($users), 'messages' => count_messages($db), 'unread' => count_unread_messages($db)];
    foreach ($clients as $c) {
        if (isset($clientByStatus[$c['status']])) $clientByStatus[$c['status']]++;
        if ($c['status'] === 'archived') $archivedClients[] = $c; else $visibleClients[] = $c;
    }
    foreach ($inquiries as $q) if (isset($inqByStatus[$q['status']])) $inqByStatus[$q['status']]++;
    if ($selectedUser) {
        foreach ($users as $u) if ((int) $u['id'] === $selectedUser) $selectedUserInfo = $u;
        // Opening a thread marks that user's messages as read for the admin.
        mark_thread_read($db, $selectedUser);
        $messages = get_user_messages($db, $selectedUser);
    }
}
$newThreads = array_values(array_filter($threads, function ($t) { return (int) $t['unread'] > 0; }));
function bar_pct($n, $total) { return $total > 0 ? round($n * 100 / $total) : 0; }
function status_badge($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
function preview($text, $len = 120) { $t = trim($text); if (function_exists('mb_strlen')) return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t; return strlen($t) > $len ? substr($t, 0, $len) . '…' : $t; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Umer Sajid</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<header><strong>Admin Panel</strong><nav><a href="../index.php">Home</a> <a href="blog.php">Blog</a> <a href="settings.php">Settings</a> <a href="change-password.php">Change Password</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>Admin Dashboard</h1>
  <p>Logged in as <?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>.</p>
  <p class="muted">Simple sections, in order: Overview → New Messages → Messages by User → Clients → Add Client → Registered Users → Inquiries. Nothing is mixed together.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?>
    <p class="notice">Database not connected yet — no records can be loaded. Import database.sql and update backend/config.php, then reload this page.</p>
  <?php else: ?>

  <!-- 1 · OVERVIEW -->
  <div class="admin-section">
  <h2>1 · Overview</h2>
  <div class="grid stat-grid">
    <div class="card stat-card"><span class="stat-num"><?php echo $counts['clients']; ?></span><p>Clients</p></div>
    <div class="card stat-card"><span class="stat-num"><?php echo $counts['inquiries']; ?></span><p>Inquiries</p></div>
    <div class="card stat-card"><span class="stat-num"><?php echo $counts['users']; ?></span><p>Registered Users</p></div>
    <div class="card stat-card"><span class="stat-num"><?php echo $counts['messages']; ?></span><p>Portal Messages</p></div>
    <div class="card stat-card"><span class="stat-num"><?php echo $counts['unread']; ?></span><p>✉️ New (Unread) Messages</p></div>
  </div>
  <div class="grid">
    <div class="card">
      <h3>Clients by status</h3>
      <?php foreach ($clientByStatus as $st => $n): $pct = bar_pct($n, max(1, $counts['clients'])); ?>
        <p><?php echo htmlspecialchars($st); ?> — <?php echo $n; ?></p>
        <div class="bar"><div class="bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
      <?php endforeach; ?>
      <?php if (!empty($archivedClients)): ?><p>archived — <?php echo count($archivedClients); ?> (hidden below)</p><?php endif; ?>
    </div>
    <div class="card">
      <h3>Inquiries by status</h3>
      <?php foreach ($inqByStatus as $st => $n): $pct = bar_pct($n, max(1, $counts['inquiries'])); ?>
        <p><?php echo htmlspecialchars($st); ?> — <?php echo $n; ?></p>
        <div class="bar"><div class="bar-fill bar-blue" style="width:<?php echo $pct; ?>%"></div></div>
      <?php endforeach; ?>
    </div>
  </div>
  </div>

  <!-- 2 · NEW MESSAGES (right after Overview, so new mail is impossible to miss) -->
  <div class="admin-section">
  <h2>2 · ✉️ New Messages</h2>
  <p class="muted">New messages from users appear here first, with who sent them and when. Opening a chat marks it as read.</p>
  <?php if (empty($newThreads)): ?>
    <p class="chat-empty">No new (unread) messages right now. ✅</p>
  <?php else: ?>
    <?php foreach ($newThreads as $t): ?>
      <div class="card thread-card">
        <p><strong><?php echo htmlspecialchars($t['name'] ?: 'User #' . (int) $t['user_id']); ?></strong> · <?php echo htmlspecialchars($t['email'] ?? ''); ?>
        <span class="unread-badge"><?php echo (int) $t['unread']; ?> new</span></p>
        <p class="muted"><?php echo htmlspecialchars($t['last_at']); ?> — “<?php echo htmlspecialchars(preview($t['last_message'])); ?>”</p>
        <p><a class="btn btn-small" href="dashboard.php?user_id=<?php echo (int) $t['user_id']; ?>#messages-by-user">Open chat →</a></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <!-- 3 · MESSAGES BY USER (one thread per user, full history in one place) -->
  <div class="admin-section" id="messages-by-user">
  <h2>3 · 💬 Messages by User</h2>
  <p class="muted">Every user's full previous history lives in that user's own thread — pick a user to see the whole conversation and reply.</p>
  <?php if (empty($threads)): ?><p>No message threads yet.</p><?php else: ?>
    <table>
      <tr><th>User</th><th>Email</th><th>Messages</th><th>Latest Message</th><th>When</th><th>Open</th></tr>
      <?php foreach ($threads as $t): ?>
        <tr>
          <td><?php echo htmlspecialchars($t['name'] ?: 'User #' . (int) $t['user_id']); ?></td>
          <td><?php echo htmlspecialchars($t['email'] ?? ''); ?></td>
          <td><?php echo (int) $t['total']; ?><?php if ((int) $t['unread'] > 0): ?> <span class="unread-badge"><?php echo (int) $t['unread']; ?> new</span><?php endif; ?></td>
          <td><?php echo htmlspecialchars(preview($t['last_message'])); ?></td>
          <td><?php echo htmlspecialchars($t['last_at']); ?></td>
          <td><a href="dashboard.php?user_id=<?php echo (int) $t['user_id']; ?>#messages-by-user">Open chat →</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <form method="get" action="dashboard.php#messages-by-user">
    <label>Or choose a registered user directly
      <select name="user_id" required>
        <option value="">— Choose —</option>
        <?php foreach ($users as $u): ?><option value="<?php echo (int) $u['id']; ?>" <?php echo $selectedUser === (int) $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name'] . ' (' . $u['email'] . ')'); ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn" type="submit">Open Conversation</button>
  </form>
  <?php if ($selectedUser && $selectedUserInfo): ?>
    <h3>Conversation with <?php echo htmlspecialchars($selectedUserInfo['name']); ?> (<?php echo htmlspecialchars($selectedUserInfo['email']); ?>)</h3>
    <?php if (empty($messages)): ?><p class="chat-empty">No messages yet — start the conversation below. Your reply will appear here for both sides.</p><?php else: ?>
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
    <form method="post" action="dashboard.php?user_id=<?php echo $selectedUser; ?>#messages-by-user">
      <input type="hidden" name="user_id" value="<?php echo $selectedUser; ?>">
      <label class="sr-only" for="chatInput">Your reply</label><textarea id="chatInput" name="message" rows="3" required></textarea>
      <button class="btn" type="submit" name="send_admin_message" value="1">Send Reply</button>
    </form>
  <?php endif; ?>
  </div>

  <!-- 4 · CLIENTS (list only — adding is a separate section below) -->
  <div class="admin-section">
  <h2>4 · Clients (<?php echo count($visibleClients); ?>)</h2>
  <p class="muted">Archived clients are hidden here. <a href="dashboard.php?show_archived=1">Show Archived (<?php echo count($archivedClients); ?>)</a><?php if ($showArchived): ?> · <a href="dashboard.php">Hide Archived</a><?php endif; ?></p>
  <?php $clientList = $showArchived ? $clients : $visibleClients; ?>
  <?php if (empty($clientList)): ?><p>No client records in this view.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Phone</th><th>Service Needed</th><th>Status</th><th>Change Status</th><th>Actions</th></tr>
      <?php foreach ($clientList as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['name']); ?></td><td><?php echo htmlspecialchars($c['email']); ?></td>
          <td><?php echo htmlspecialchars($c['phone']); ?></td><td><?php echo htmlspecialchars($c['service_needed']); ?></td>
          <td><?php echo status_badge($c['status']); ?></td>
          <td><form method="post"><input type="hidden" name="update_status" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>">
            <select name="status"><option value="new" <?php echo $c['status'] === 'new' ? 'selected' : ''; ?>>new</option><option value="contacted" <?php echo $c['status'] === 'contacted' ? 'selected' : ''; ?>>contacted</option><option value="done" <?php echo $c['status'] === 'done' ? 'selected' : ''; ?>>done</option></select>
            <button type="submit">Update</button></form></td>
          <td>
            <?php if ($c['status'] === 'archived'): ?>
              <form method="post"><input type="hidden" name="restore_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Restore</button></form>
            <?php else: ?>
              <form method="post"><input type="hidden" name="archive_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Archive</button></form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this client? This also deletes that client\'s messages and cannot be undone.');"><input type="hidden" name="delete_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  </div>

  <!-- 5 · ADD CLIENT — completely separate section, not mixed into the list -->
  <div class="admin-section">
  <h2>5 · ➕ Add Client</h2>
  <p class="muted">Use this form only to add a new client. It does not change the list above.</p>
  <form method="post">
    <input type="hidden" name="add_client" value="1">
    <label>Name <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email"></label>
    <label>Phone <input type="text" name="phone"></label>
    <label>Service needed <input type="text" name="service_needed"></label>
    <label>Notes <textarea name="notes" rows="3"></textarea></label>
    <label>Status
      <select name="status"><option value="new">new</option><option value="contacted">contacted</option><option value="done">done</option></select>
    </label>
    <button class="btn" type="submit">Add Client</button>
  </form>
  </div>

  <!-- 6 · REGISTERED USERS -->
  <div class="admin-section">
  <h2>6 · Registered Users — with registration detail (<?php echo count($users); ?>)</h2>
  <?php if (empty($users)): ?><p>No registered users yet.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Service Needed</th><th>Budget</th><th>Project Detail</th><th>Messages</th></tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo htmlspecialchars($u['name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td><td><?php echo htmlspecialchars($u['city'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['service_needed'] ?? ''); ?></td><td><?php echo htmlspecialchars($u['budget_range'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['project_detail'] ?? ''); ?></td>
          <td><a href="dashboard.php?user_id=<?php echo (int) $u['id']; ?>#messages-by-user">Open chat →</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  </div>

  <!-- 7 · RECENT INQUIRIES -->
  <div class="admin-section">
  <h2>7 · Recent Inquiries (<?php echo count($inquiries); ?>)</h2>
  <?php if (empty($inquiries)): ?><p>No inquiries yet.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Service</th><th>Message</th><th>Status</th><th>Reply in Portal</th></tr>
      <?php foreach ($inquiries as $q): ?>
        <tr>
          <td><?php echo htmlspecialchars($q['name']); ?></td><td><?php echo htmlspecialchars($q['email']); ?></td>
          <td><?php echo htmlspecialchars($q['service'] ?? ''); ?></td><td><?php echo htmlspecialchars($q['message']); ?></td>
          <td><?php echo status_badge($q['status']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="reply_inquiry" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $q['id']; ?>">
              <textarea name="message" rows="2" required placeholder="Reply — shows in user's portal if they registered with this email"></textarea>
              <button type="submit">Reply</button>
            </form>
            <form method="post"><input type="hidden" name="update_inquiry_status" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $q['id']; ?>">
              <select name="status"><option value="new" <?php echo $q['status'] === 'new' ? 'selected' : ''; ?>>new</option><option value="contacted" <?php echo $q['status'] === 'contacted' ? 'selected' : ''; ?>>contacted</option><option value="done" <?php echo $q['status'] === 'done' ? 'selected' : ''; ?>>done</option></select>
              <button type="submit">Set status</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
</body>
</html>
