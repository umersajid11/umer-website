<?php
// Messages (v7 mail-style) — Gmail/Yahoo-style inbox.
// LEFT: inbox list, one row per person (name, last-message preview,
// time, unread badge). RIGHT: reading pane with the full conversation
// for the selected person and a Reply box at the bottom.
// All backend logic lives in backend/messages.php (untouched by this
// layout change): reply via send_message, Archive hides the thread,
// Delete removes only that user's messages, opening marks read.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
// POST wins over GET: a reading-pane button must act on the user in
// that pane, even when another user's row is involved.
$selectedUser = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0);
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
if ($tab !== 'unread' && $tab !== 'archived') {
    // Backwards compatibility: old "Show Archived Chats" link.
    $tab = (isset($_GET['show_archived']) && $_GET['show_archived'] === '1') ? 'archived' : 'all';
}
$search = trim($_GET['q'] ?? '');
// Archive / unarchive / delete a whole chat thread. These run before
// the thread is opened below, so a deleted thread is not re-read.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUser && $db) {
    if (isset($_POST['archive_chat'])) {
        $message = archive_chat($db, $selectedUser) ? 'Chat archived — it is hidden from the main inbox. Open the Archived tab to see it again.' : 'Chat could not be archived — the archived_chats table is missing. Run the migration in database.sql first.';
        if (strpos($message, 'archived —') !== false) $selectedUser = 0;
    } elseif (isset($_POST['unarchive_chat'])) {
        $message = unarchive_chat($db, $selectedUser) ? 'Chat is back in the main inbox.' : 'Chat could not be brought back.';
    } elseif (isset($_POST['delete_chat'])) {
        if (delete_chat_messages($db, $selectedUser)) { $message = 'Chat deleted — all messages with this person are gone. This cannot be undone.'; $selectedUser = 0; }
        else $message = 'Chat could not be deleted — database may not be connected yet.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_message']) && $selectedUser) {
    if (send_message($db, $selectedUser, 'admin', $_POST['message'] ?? '')) $message = 'Message sent — it now shows in that user\'s portal.';
    else $message = 'Message could not be sent — database may not be connected yet.';
}
$threads = []; $users = []; $messages = []; $selectedUserInfo = null; $archivedIds = [];
if ($db) {
    $threads = get_message_threads($db);
    $archivedIds = get_archived_user_ids($db);
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
$isArchivedThread = function ($t) use ($archivedIds) { return in_array((int) $t['user_id'], $archivedIds, true); };
$unreadCount = 0; $archivedCount = 0;
foreach ($threads as $t) {
    if ($isArchivedThread($t)) $archivedCount++;
    elseif ((int) $t['unread'] > 0) $unreadCount++;
}
// Visible inbox rows for the current tab, then the search filter.
$visibleThreads = array_values(array_filter($threads, function ($t) use ($tab, $isArchivedThread) {
    $archived = $isArchivedThread($t);
    if ($tab === 'archived') return $archived;
    if ($tab === 'unread') return !$archived && (int) $t['unread'] > 0;
    return !$archived; // All = main inbox, archived hidden
}));
if ($search !== '') {
    $visibleThreads = array_values(array_filter($visibleThreads, function ($t) use ($search) {
        $hay = ($t['name'] ?? '') . ' ' . ($t['email'] ?? '') . ' ' . ($t['last_message'] ?? '');
        return stripos($hay, $search) !== false;
    }));
}
function preview_msg($text, $len = 90) { $t = trim((string) $text); if (function_exists('mb_strlen')) return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t; return strlen($t) > $len ? substr($t, 0, $len) . '…' : $t; }
function mail_url($params) { return 'messages.php?' . http_build_query($params); }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — Admin</title><link rel="stylesheet" href="../css/style.css?v=7"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>💬 Messages</h1>
  <p class="page-intro">Your inbox, like email. Pick a person on the left to read their messages, then reply on the right — the user will see your reply in their own panel.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — messages will show here once the database is connected.</p><?php endif; ?>

  <!-- Gmail-style toolbar: search, tabs, refresh -->
  <div class="mail-toolbar">
    <form method="get" action="messages.php" class="mail-search">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
      <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email or message…" aria-label="Search messages">
      <button type="submit">Search</button>
      <?php if ($search !== ''): ?><a href="<?php echo mail_url(['tab' => $tab]); ?>">Clear</a><?php endif; ?>
    </form>
    <a class="btn btn-small" href="messages.php">↻ Refresh</a>
  </div>
  <nav class="mail-tabs" aria-label="Inbox filters">
    <a href="<?php echo mail_url(['tab' => 'all']); ?>" class="<?php echo $tab === 'all' ? 'mail-tab-active' : ''; ?>">All</a>
    <a href="<?php echo mail_url(['tab' => 'unread']); ?>" class="<?php echo $tab === 'unread' ? 'mail-tab-active' : ''; ?>">Unread<?php if ($unreadCount > 0): ?> (<?php echo $unreadCount; ?>)<?php endif; ?></a>
    <a href="<?php echo mail_url(['tab' => 'archived']); ?>" class="<?php echo $tab === 'archived' ? 'mail-tab-active' : ''; ?>">Archived<?php if ($archivedCount > 0): ?> (<?php echo $archivedCount; ?>)<?php endif; ?></a>
  </nav>

  <div class="mail-layout">
    <!-- LEFT: inbox list -->
    <div class="mail-inbox">
      <p class="mail-inbox-head"><?php echo $tab === 'unread' ? 'Unread' : ($tab === 'archived' ? 'Archived' : 'Inbox'); ?><?php echo $search !== '' ? ' — results for "' . htmlspecialchars($search) . '"' : ''; ?></p>
      <?php if (empty($visibleThreads)): ?>
        <p class="chat-empty"><?php echo $search !== '' ? 'No messages match your search.' : ($tab === 'unread' ? '✅ No unread messages right now.' : ($tab === 'archived' ? 'No archived chats yet.' : 'No messages yet.')); ?></p>
      <?php else: ?>
        <?php foreach ($visibleThreads as $t):
          $isUnread = (int) $t['unread'] > 0;
          $isArchived = $isArchivedThread($t);
          $isSelected = $selectedUser && (int) $t['user_id'] === $selectedUser;
          $rowUrl = mail_url(['tab' => $tab, 'q' => $search, 'user_id' => (int) $t['user_id']]);
        ?>
          <a class="mail-row<?php echo $isSelected ? ' mail-row-selected' : ''; ?>" href="<?php echo $rowUrl; ?>">
            <span class="mail-row-top">
              <strong class="<?php echo $isUnread ? 'mail-unread' : ''; ?>"><?php echo htmlspecialchars($t['name'] ?: 'User #' . (int) $t['user_id']); ?></strong>
              <span class="mail-row-time"><?php echo htmlspecialchars($t['last_at']); ?></span>
            </span>
            <span class="mail-row-subject"><?php echo htmlspecialchars(($t['last_sender'] === 'admin' ? 'You: ' : '') . preview_msg($t['last_message'])); ?></span>
            <span class="mail-row-meta"><?php echo htmlspecialchars($t['email'] ?? ''); ?><?php if ($isArchived): ?> <span class="badge badge-archived">Archived</span><?php endif; ?><?php if ($isUnread): ?> <span class="unread-badge"><?php echo (int) $t['unread']; ?> new</span><?php endif; ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- RIGHT: reading pane -->
    <div class="mail-reading">
      <?php if (!$selectedUser): ?>
        <p class="chat-empty">👈 Select a conversation from the inbox to read it here.<br>Tip: use the Unread tab to see only messages you have not opened yet.</p>
      <?php else: $selectedIsArchived = in_array($selectedUser, $archivedIds, true); ?>
        <div class="mail-reading-toolbar">
          <a class="btn btn-small" href="<?php echo mail_url(['tab' => $tab, 'q' => $search]); ?>">← Back to inbox</a>
          <?php if ($selectedIsArchived): ?>
            <span class="badge badge-archived">Archived</span>
            <form method="post" style="display:inline"><input type="hidden" name="user_id" value="<?php echo $selectedUser; ?>"><button type="submit" name="unarchive_chat" value="1">Unarchive — bring it back</button></form>
          <?php else: ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Archive this chat? It will be hidden from the main inbox. You can bring it back from the Archived tab.');"><input type="hidden" name="user_id" value="<?php echo $selectedUser; ?>"><button type="submit" name="archive_chat" value="1">Archive</button></form>
          <?php endif; ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this whole chat? ALL messages with this person will be gone for good. This cannot be undone.');"><input type="hidden" name="user_id" value="<?php echo $selectedUser; ?>"><button type="submit" name="delete_chat" value="1">Delete</button></form>
        </div>
        <h2><?php echo $selectedUserInfo ? htmlspecialchars($selectedUserInfo['name']) : 'User #' . $selectedUser; ?></h2>
        <?php if ($selectedUserInfo): ?><p class="muted"><?php echo htmlspecialchars($selectedUserInfo['email']); ?></p><?php endif; ?>
        <?php if (empty($messages)): ?><p class="chat-empty">No messages yet — start the conversation below.</p><?php else: ?>
          <div class="chat-box" id="chatBox">
            <?php foreach ($messages as $m): ?>
              <div class="chat-msg <?php echo $m['sender_role'] === 'admin' ? 'from-admin' : 'from-user'; ?>">
                <div class="chat-meta"><strong><?php echo $m['sender_role'] === 'admin' ? 'You (Admin)' : ($selectedUserInfo ? htmlspecialchars($selectedUserInfo['name']) : 'User'); ?></strong>
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
      <?php endif; ?>
    </div>
  </div>
  <p><a href="dashboard.php">← Back to Admin Home</a></p>
</section>
</body>
</html>
