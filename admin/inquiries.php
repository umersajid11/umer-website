<?php
// Inquiries (v9) — CLEAN inbox, like Messages. One job per inquiry:
// read it, set its stage, and reply if the person has a user panel.
//
// The three things, clearly separated:
//   • Inquiry  = a first question from the public Contact form (no login needed).
//   • Message  = a chat with a registered user (admin/messages.php).
//   • Request  = the same inquiry, seen from the user's own panel.
// A "New" inquiry stays marked New — and stays in the dashboard alert —
// until you set its stage to Contacted or Done. That IS the notification:
// there is no email notification, because no mail service is configured.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
$selectedId = isset($_POST['inquiry_id']) ? (int) $_POST['inquiry_id'] : (isset($_GET['inquiry_id']) ? (int) $_GET['inquiry_id'] : 0);
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
if ($tab !== 'new' && $tab !== 'emailed' && $tab !== 'contacted' && $tab !== 'done') $tab = 'all';
$search = trim($_GET['q'] ?? '');
// Set the stage for the selected inquiry.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'], $_POST['inquiry_id']) && $db) {
  $stmt = $db->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
  $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['inquiry_id']]);
  $message = 'Stage updated.';
}
// Save an admin note (e.g. what you emailed them, and when).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inquiry_note'], $_POST['inquiry_id']) && $db) {
  try {
    $stmt = $db->prepare("UPDATE inquiries SET admin_note = ? WHERE id = ?");
    $stmt->execute([trim($_POST['admin_note'] ?? ''), (int) $_POST['inquiry_id']]);
    $message = 'Note saved.';
  } catch (Throwable $e) { $message = 'Note could not be saved — the admin_note column is missing. Run the migration first.'; }
}
// Reply in the person's portal — only possible if they registered
// with the same email. Otherwise say so plainly; never pretend it sent.
$hasPortalUser = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'], $_POST['inquiry_id']) && $db) {
  $iid = (int) $_POST['inquiry_id'];
  $stmt = $db->prepare("SELECT email FROM inquiries WHERE id = ? LIMIT 1");
  $stmt->execute([$iid]);
  $row = $stmt->fetch();
  $uid = $row ? find_user_id_by_email($db, $row['email']) : null;
  if ($uid) {
    if (send_message($db, $uid, 'admin', $_POST['message'] ?? '', $iid)) $message = 'Reply sent — it now shows in that person\'s panel, under Chat with Admin.';
    else $message = 'Reply could not be saved.';
  } else {
    $message = 'Reply NOT sent: this email has no registered user panel yet. The reply can only appear in a panel, so contact this person directly by email, or ask them to register with this same email and then reply here.';
  }
}
$inquiries = [];
if ($db) {
  try { $inquiries = $db->query("SELECT id, name, email, service, message, status, admin_note, created_at FROM inquiries ORDER BY created_at DESC LIMIT 100")->fetchAll(); } catch (Throwable $e) {
    // Fallback for a database where the admin_note migration has not run yet:
    // show inquiries without notes rather than an empty list.
    try { $inquiries = $db->query("SELECT id, name, email, service, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 100")->fetchAll(); } catch (Throwable $e2) {}
    foreach ($inquiries as &$q) $q['admin_note'] = null;
    unset($q);
  }
}
$counts = ['new' => 0, 'emailed' => 0, 'contacted' => 0, 'done' => 0];
foreach ($inquiries as $q) { if (isset($counts[$q['status']])) $counts[$q['status']]++; }
$visible = array_values(array_filter($inquiries, function ($q) use ($tab, $search) {
  if ($tab !== 'all' && $q['status'] !== $tab) return false;
  if ($search !== '') {
    $hay = ($q['name'] ?? '') . ' ' . ($q['email'] ?? '') . ' ' . ($q['service'] ?? '') . ' ' . ($q['message'] ?? '');
    if (stripos($hay, $search) === false) return false;
  }
  return true;
}));
$selected = null;
foreach ($inquiries as $q) { if ((int) $q['id'] === $selectedId) { $selected = $q; break; } }
if ($selected && $db) {
  $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$selected['email']]);
  $hasPortalUser = (bool) $stmt->fetch();
}
function inquiry_badge($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
function preview_inq($text, $len = 90) { $t = trim((string) $text); return function_exists('mb_strlen') && mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : (strlen($t) > $len ? substr($t, 0, $len) . '…' : $t); }
function inq_url($params) { return 'inquiries.php?' . http_build_query($params); }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inquiries — Admin</title><link rel="stylesheet" href="../css/style.css?v=9"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
<h1>📩 Inquiries</h1>
<p class="page-intro"><strong>What is an inquiry?</strong> A first question from the public Contact form — the person did <em>not</em> need an account to send it. That is different from <strong>Messages</strong> (a chat with someone who <em>did</em> register) and from <strong>Clients</strong> (people you work with).<br>
<strong>Notification:</strong> a "New" inquiry stays New — and stays in the red alert on your Dashboard — until you set its stage to Emailed, Contacted or Done. There is no email notification.<br>
<strong>Panel reply vs email reply:</strong> a panel reply goes only to a registered user's panel chat. An email reply you send yourself from your own email has no panel record — use the "emailed" stage + note below to record it, so you never reply twice or forget you already answered.</p>
<?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
<?php if (!$db): ?><p class="notice">Database not connected yet — inquiries will show here once the database is connected.</p><?php endif; ?>
<div class="mail-toolbar">
<form method="get" action="inquiries.php" class="mail-search">
<input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
<input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, service or question …" aria-label="Search inquiries">
<button type="submit">Search</button>
<?php if ($search !== ''): ?><a href="<?php echo inq_url(['tab' => $tab]); ?>">Clear</a><?php endif; ?>
</form>
<a class="btn-small" href="inquiries.php">↻ Refresh</a>
</div>
<nav class="mail-tabs" aria-label="Inquiry stages">
<a href="<?php echo inq_url(['tab' => 'all']); ?>" class="<?php echo $tab === 'all' ? 'mail-tab-active' : ''; ?>">All (<?php echo count($inquiries); ?>)</a>
<a href="<?php echo inq_url(['tab' => 'new']); ?>" class="<?php echo $tab === 'new' ? 'mail-tab-active' : ''; ?>">New <?php if ($counts['new'] > 0): ?> (<?php echo $counts['new']; ?>)<?php endif; ?></a>
<a href="<?php echo inq_url(['tab' => 'emailed']); ?>" class="<?php echo $tab === 'emailed' ? 'mail-tab-active' : ''; ?>">Emailed (<?php echo $counts['emailed']; ?>)</a>
<a href="<?php echo inq_url(['tab' => 'contacted']); ?>" class="<?php echo $tab === 'contacted' ? 'mail-tab-active' : ''; ?>">Contacted (<?php echo $counts['contacted']; ?>)</a>
<a href="<?php echo inq_url(['tab' => 'done']); ?>" class="<?php echo $tab === 'done' ? 'mail-tab-active' : ''; ?>">Done (<?php echo $counts['done']; ?>)</a>
</nav>
<div class="mail-layout">
<div class="mail-inbox">
<p class="mail-inbox-head"><?php echo $tab === 'all' ? 'All inquiries' : ucfirst($tab); ?><?php echo $search !== '' ? ' — results for "' . htmlspecialchars($search) . '"' : ''; ?></p>
<?php if (empty($visible)): ?>
<p class="chat-empty"><?php echo $tab === 'new' ? '✅ No new inquiries right now.' : 'No inquiries match.'; ?></p>
<?php else: ?>
<?php foreach ($visible as $q):
  $isSelected = $selected && (int) $q['id'] === (int) $selected['id'];
  $rowUrl = inq_url(['tab' => $tab, 'q' => $search, 'inquiry_id' => (int) $q['id']]);
?>
<a class="mail-row <?php echo $isSelected ? 'mail-row-selected' : ''; ?>" href="<?php echo $rowUrl; ?>">
<span class="mail-row-top">
<strong class="<?php echo $q['status'] === 'new' ? 'mail-unread' : ''; ?>"><?php echo htmlspecialchars($q['name']); ?></strong>
<span class="mail-row-time"><?php echo htmlspecialchars($q['created_at']); ?></span>
</span>
<span class="mail-row-subject"><?php echo preview_inq($q['message']); ?></span>
<span class="mail-row-meta"><?php echo htmlspecialchars($q['email']); ?> · <?php echo htmlspecialchars($q['service'] ?? ''); ?> <?php echo inquiry_badge($q['status']); ?></span>
</a>
<?php endforeach; ?>
<?php endif; ?>
</div>
<div class="mail-reading">
<?php if (!$selected): ?>
<p class="chat-empty">👈 Select an inquiry on the left to read it here.<br>Tip: use the New tab to see only inquiries you have not dealt with yet.</p>
<?php else: ?>
<div class="mail-reading-toolbar">
<a class="btn-small" href="<?php echo inq_url(['tab' => $tab, 'q' => $search]); ?>">← Back to list</a>
<?php echo inquiry_badge($selected['status']); ?>
</div>
<h2><?php echo htmlspecialchars($selected['name']); ?></h2>
<p class="muted"><?php echo htmlspecialchars($selected['email']); ?> · <?php echo htmlspecialchars($selected['service'] ?? ''); ?> · <?php echo htmlspecialchars($selected['created_at']); ?></p>
<p><?php echo nl2br(htmlspecialchars($selected['message'])); ?></p>
<h3>Set its stage</h3>
<p class="muted">New = just arrived (stays in your Dashboard alert). Emailed = you already answered by email yourself (panel reply not needed). Contacted = you talked to them another way. Done = finished.</p>
<form method="post">
<input type="hidden" name="update_inquiry_status" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $selected['id']; ?>">
<select name="status">
<option value="new" <?php echo $selected['status'] === 'new' ? 'selected' : ''; ?>>new — just arrived</option>
<option value="emailed" <?php echo $selected['status'] === 'emailed' ? 'selected' : ''; ?>>emailed — replied by email</option>
<option value="contacted" <?php echo $selected['status'] === 'contacted' ? 'selected' : ''; ?>>contacted — talked to</option>
<option value="done" <?php echo $selected['status'] === 'done' ? 'selected' : ''; ?>>done — finished</option>
</select>
<button type="submit">Set stage</button>
</form>
<h3>Note — what you did outside the panel</h3>
<?php if (!empty($selected['admin_note'])): ?><p><strong>Saved note:</strong> <?php echo nl2br(htmlspecialchars($selected['admin_note'])); ?></p><?php endif; ?>
<p class="muted">For email replies: write what you emailed and when, so it is on record here. The site does not send this email — you send it from your own email, this note only records it.</p>
<form method="post">
<input type="hidden" name="save_inquiry_note" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $selected['id']; ?>">
<label>Admin note <textarea name="admin_note" rows="2" placeholder="e.g. Emailed 29 Oct — price and timeline sent"><?php echo htmlspecialchars($selected['admin_note'] ?? ''); ?></textarea></label>
<button type="submit">Save Note</button>
</form>
<h3>Reply in their panel</h3>
<?php if ($hasPortalUser): ?>
<p class="muted">✅ This email has a registered panel, so your reply will appear there under "Chat with Admin".</p>
<?php else: ?>
<p class="notice">⚠️ This email has <strong>no registered panel yet</strong>, so a panel reply cannot reach them. Contact them directly by email, or ask them to register with this same email — then reply here and it will appear in their panel.</p>
<?php endif; ?>
<form method="post">
<input type="hidden" name="reply_inquiry" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $selected['id']; ?>">
<label>Write your reply <textarea name="message" rows="3" required placeholder="Write your reply …"></textarea></label>
<button class="btn" type="submit" <?php echo $hasPortalUser ? '' : 'disabled'; ?>>Send Reply</button>
</form>
<?php endif; ?>
</div>
</div>
<p><a href="dashboard.php">← Back to Dashboard</a></p>
</section>
</body>
</html>
