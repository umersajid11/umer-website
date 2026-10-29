<?php
// Inquiries (v10) — SIMPLE. Three steps, nothing else:
//   1. Pick an inquiry on the left and read it on the right.
//   2. Answer it: reply in their panel (registered users only),
//      or press "Replied by email" if you emailed them yourself.
//   3. Finish it: Done, Archive, or Delete.
// What is an inquiry? A first question from the public Contact form.
// It is NOT a panel chat (that is Messages) and NOT a client record.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
$selectedId = isset($_POST['inquiry_id']) ? (int) $_POST['inquiry_id'] : (isset($_GET['inquiry_id']) ? (int) $_GET['inquiry_id'] : 0);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';
if ($tab !== 'inbox' && $tab !== 'done' && $tab !== 'archived') $tab = 'inbox';
$search = trim($_GET['q'] ?? '');
$deleteConfirmId = isset($_GET['delete_confirm']) ? (int) $_GET['delete_confirm'] : 0;

// One-button stage changes.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_stage'], $_POST['inquiry_id']) && $db) {
    $stage = $_POST['set_stage'];
    if (in_array($stage, ['new', 'emailed', 'contacted', 'done', 'archived'], true)) {
        $stmt = $db->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$stage, (int) $_POST['inquiry_id']]);
        $message = $stage === 'archived' ? 'Inquiry archived — it is in the Archived tab.' : ($stage === 'done' ? 'Inquiry marked Done.' : ($stage === 'emailed' ? 'Inquiry marked "Replied by email".' : 'Inquiry back in the Inbox.'));
        if ($stage === 'archived' || $stage === 'done') $selectedId = 0;
    }
}
// Save an admin note (e.g. what you emailed them, and when).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inquiry_note'], $_POST['inquiry_id']) && $db) {
    try {
        $stmt = $db->prepare("UPDATE inquiries SET admin_note = ? WHERE id = ?");
        $stmt->execute([trim($_POST['admin_note'] ?? ''), (int) $_POST['inquiry_id']]);
        $message = 'Note saved.';
    } catch (Throwable $e) { $message = 'Note could not be saved — the admin_note column is missing.'; }
}
// Reply in the person's panel — only if they registered with this email.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'], $_POST['inquiry_id']) && $db) {
    $iid = (int) $_POST['inquiry_id'];
    $stmt = $db->prepare("SELECT email FROM inquiries WHERE id = ? LIMIT 1"); $stmt->execute([$iid]); $row = $stmt->fetch();
    $uid = $row ? find_user_id_by_email($db, $row['email']) : null;
    if ($uid) {
        if (send_message($db, $uid, 'admin', $_POST['message'] ?? '', $iid)) $message = 'Reply sent — it shows in their panel, under Chat with Admin.';
        else $message = 'Reply could not be saved.';
    } else {
        $message = 'Reply NOT sent: this email has no registered panel. Email them directly instead, then press "Replied by email".';
    }
}
// Delete — two steps, no pop-up dialog: link -> confirmation page -> Yes button.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry'], $_POST['inquiry_id']) && $db) {
    $stmt = $db->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->execute([(int) $_POST['inquiry_id']]);
    $message = 'Inquiry deleted. This cannot be undone.';
    $selectedId = 0; $deleteConfirmId = 0;
}
$inquiries = [];
if ($db) {
    try { $inquiries = $db->query("SELECT id, name, email, service, message, status, admin_note, created_at FROM inquiries ORDER BY created_at DESC LIMIT 100")->fetchAll(); } catch (Throwable $e) {
        try { $inquiries = $db->query("SELECT id, name, email, service, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 100")->fetchAll(); } catch (Throwable $e2) {}
        foreach ($inquiries as &$q) $q['admin_note'] = null;
        unset($q);
    }
}
function inq_status_group($s) {
    if ($s === 'done') return 'done';
    if ($s === 'archived') return 'archived';
    return 'inbox'; // new, emailed, contacted
}
$counts = ['inbox' => 0, 'done' => 0, 'archived' => 0];
foreach ($inquiries as $q) $counts[inq_status_group($q['status'])]++;
$visible = array_values(array_filter($inquiries, function ($q) use ($tab, $search) {
    if ($search === '' && inq_status_group($q['status']) !== $tab) return false;
    if ($search !== '') {
        $hay = ($q['name'] ?? '') . ' ' . ($q['email'] ?? '') . ' ' . ($q['service'] ?? '') . ' ' . ($q['message'] ?? '');
        if (stripos($hay, $search) === false) return false;
    }
    return true;
}));
$selected = null;
foreach ($inquiries as $q) { if ((int) $q['id'] === $selectedId) { $selected = $q; break; } }
$hasPortalUser = false;
if ($selected && $db) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1"); $stmt->execute([$selected['email']]); $hasPortalUser = (bool) $stmt->fetch();
}
function inquiry_badge($s) {
    $label = $s === 'emailed' ? 'Replied by email' : $s;
    return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($label) . '</span>';
}
function preview_inq($text, $len = 90) { $t = trim((string) $text); return function_exists('mb_strlen') && mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : (strlen($t) > $len ? substr($t, 0, $len) . '…' : $t); }
function inq_url($params) { return 'inquiries.php?' . http_build_query($params); }
function stage_button($id, $stage, $label) {
    return '<form method="post" style="display:inline"><input type="hidden" name="inquiry_id" value="' . (int) $id . '"><button type="submit" name="set_stage" value="' . htmlspecialchars($stage) . '">' . $label . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inquiries — Admin</title><link rel="stylesheet" href="../css/style.css?v=10"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>📩 Inquiries</h1>
  <p class="page-intro">Questions from the Contact form. Pick one on the left, answer it, then finish it with Done, Archive or Delete.<br>
  <strong>Panel reply</strong> goes only to a registered user's panel chat. <strong>Email reply</strong> is an email you send yourself — the site does not send it, so press "Replied by email" to record it.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — inquiries will show here once the database is connected.</p><?php endif; ?>

  <div class="mail-toolbar">
    <form method="get" action="inquiries.php" class="mail-search">
      <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, service or question…" aria-label="Search inquiries">
      <button type="submit">Search</button>
      <?php if ($search !== ''): ?><a href="inquiries.php">Clear</a><?php endif; ?>
    </form>
    <a class="btn btn-small" href="inquiries.php">↻ Refresh</a>
  </div>
  <nav class="mail-tabs" aria-label="Inquiry folders">
    <a href="<?php echo inq_url(['tab' => 'inbox']); ?>" class="<?php echo $tab === 'inbox' && $search === '' ? 'mail-tab-active' : ''; ?>">Inbox (<?php echo $counts['inbox']; ?>)</a>
    <a href="<?php echo inq_url(['tab' => 'done']); ?>" class="<?php echo $tab === 'done' && $search === '' ? 'mail-tab-active' : ''; ?>">Done (<?php echo $counts['done']; ?>)</a>
    <a href="<?php echo inq_url(['tab' => 'archived']); ?>" class="<?php echo $tab === 'archived' && $search === '' ? 'mail-tab-active' : ''; ?>">Archived (<?php echo $counts['archived']; ?>)</a>
  </nav>

  <div class="mail-layout">
    <div class="mail-inbox">
      <p class="mail-inbox-head"><?php echo $search !== '' ? 'Search results' : ($tab === 'inbox' ? 'Inbox — needs an answer' : ucfirst($tab)); ?></p>
      <?php if (empty($visible)): ?>
        <p class="chat-empty"><?php echo $tab === 'inbox' && $search === '' ? '✅ Inbox is clear — no inquiry needs an answer.' : 'Nothing here.'; ?></p>
      <?php else: ?>
        <?php foreach ($visible as $q):
          $isSelected = $selected && (int) $q['id'] === (int) $selected['id'];
          $rowUrl = inq_url(['tab' => $tab, 'q' => $search, 'inquiry_id' => (int) $q['id']]);
        ?>
          <a class="mail-row<?php echo $isSelected ? ' mail-row-selected' : ''; ?>" href="<?php echo $rowUrl; ?>">
            <span class="mail-row-top">
              <strong class="<?php echo $q['status'] === 'new' ? 'mail-unread' : ''; ?>"><?php echo htmlspecialchars($q['name']); ?></strong>
              <span class="mail-row-time"><?php echo htmlspecialchars($q['created_at']); ?></span>
            </span>
            <span class="mail-row-subject"><?php echo preview_inq($q['message']); ?></span>
            <span class="mail-row-meta"><?php echo htmlspecialchars($q['service'] ?? ''); ?> <?php echo inquiry_badge($q['status']); ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="mail-reading">
      <?php if ($deleteConfirmId && !$selected): $dq = null; foreach ($inquiries as $q) { if ((int) $q['id'] === $deleteConfirmId) $dq = $q; }
        if ($dq): ?>
        <h2>Delete this inquiry?</h2>
        <p><strong><?php echo htmlspecialchars($dq['name']); ?></strong> · <?php echo htmlspecialchars($dq['email']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($dq['message'])); ?></p>
        <p class="notice">This cannot be undone.</p>
        <form method="post" style="display:inline"><input type="hidden" name="inquiry_id" value="<?php echo (int) $dq['id']; ?>"><button class="btn" type="submit" name="delete_inquiry" value="1">Yes, delete this inquiry</button></form>
        <a class="btn btn-small" href="<?php echo inq_url(['tab' => $tab]); ?>">Cancel</a>
      <?php endif; ?>
      <?php elseif (!$selected): ?>
        <p class="chat-empty">👈 Select an inquiry on the left to read it here.</p>
      <?php else: ?>
        <div class="mail-reading-toolbar">
          <a class="btn btn-small" href="<?php echo inq_url(['tab' => $tab, 'q' => $search]); ?>">← Back to list</a>
          <?php echo inquiry_badge($selected['status']); ?>
        </div>
        <h2><?php echo htmlspecialchars($selected['name']); ?></h2>
        <p class="muted"><?php echo htmlspecialchars($selected['email']); ?> · <?php echo htmlspecialchars($selected['service'] ?? ''); ?> · <?php echo htmlspecialchars($selected['created_at']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($selected['message'])); ?></p>

        <h3>Step 1 — Answer it</h3>
        <?php if ($hasPortalUser): ?>
          <form method="post">
            <input type="hidden" name="reply_inquiry" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $selected['id']; ?>">
            <label>Reply in their panel <textarea name="message" rows="3" required placeholder="Write your reply — it shows in their panel, under Chat with Admin"></textarea></label>
            <button class="btn" type="submit">Send Panel Reply</button>
          </form>
          <p class="muted">Or, if you emailed them yourself instead, use "Replied by email" below.</p>
        <?php else: ?>
          <p class="notice">⚠️ No panel for this email — a panel reply cannot reach them. Email them directly from your own email, then press "Replied by email" in Step 2.</p>
        <?php endif; ?>

        <h3>Step 2 — Finish it</h3>
        <p>
          <?php if ($selected['status'] !== 'emailed'): echo stage_button($selected['id'], 'emailed', '📧 Replied by email'); endif; ?>
          <?php if ($selected['status'] !== 'done'): echo stage_button($selected['id'], 'done', '✅ Done'); endif; ?>
          <?php if ($selected['status'] !== 'archived'): echo stage_button($selected['id'], 'archived', '🗄️ Archive'); endif; ?>
          <?php if ($selected['status'] === 'archived'): echo stage_button($selected['id'], 'contacted', '↩️ Unarchive — back to Inbox'); endif; ?>
          <?php if ($selected['status'] === 'done'): echo stage_button($selected['id'], 'contacted', '↩️ Back to Inbox'); endif; ?>
          <a class="btn btn-small" href="<?php echo inq_url(['tab' => $tab, 'delete_confirm' => (int) $selected['id']]); ?>">🗑️ Delete</a>
        </p>
        <p class="muted">Done = finished. Archive = hide it from the Inbox without deleting it (see it in Archived, bring it back anytime). Delete = gone for good.</p>

        <h3>Note (optional)</h3>
        <?php if (!empty($selected['admin_note'])): ?><p><strong>Saved note:</strong> <?php echo nl2br(htmlspecialchars($selected['admin_note'])); ?></p><?php endif; ?>
        <form method="post">
          <input type="hidden" name="save_inquiry_note" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $selected['id']; ?>">
          <label>What did you do? <textarea name="admin_note" rows="2" placeholder="e.g. Emailed 29 Oct — price and timeline sent"><?php echo htmlspecialchars($selected['admin_note'] ?? ''); ?></textarea></label>
          <button type="submit">Save Note</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <p><a href="dashboard.php">← Back to Dashboard</a></p>
</section>
</body>
</html>
