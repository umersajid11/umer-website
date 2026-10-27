<?php
// Inquiries (v6) — ONLY questions from the contact form. One job:
// read a question, reply to it in the person's portal, and set its stage.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'], $_POST['inquiry_id']) && $db) {
    $stmt = $db->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
    $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['inquiry_id']]);
    $message = 'Inquiry stage updated.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_inquiry'], $_POST['inquiry_id']) && $db) {
    $iid = (int) $_POST['inquiry_id'];
    $stmt = $db->prepare("SELECT email FROM inquiries WHERE id = ? LIMIT 1"); $stmt->execute([$iid]); $row = $stmt->fetch();
    $uid = $row ? find_user_id_by_email($db, $row['email']) : null;
    if ($uid) {
        if (send_message($db, $uid, 'admin', $_POST['message'] ?? '', $iid)) $message = 'Reply sent — it now shows in that person\'s portal.';
        else $message = 'Reply could not be saved.';
    } else {
        $message = 'That email has no registered user portal yet — ask them to register with the same email, then reply here.';
    }
}
$inquiries = [];
if ($db) {
    try { $inquiries = $db->query("SELECT id, name, email, service, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 50")->fetchAll(); } catch (Throwable $e) {}
}
function inquiry_badge($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inquiries — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>📩 Inquiries (<?php echo count($inquiries); ?>)</h1>
  <p class="page-intro">This page is only for questions sent from the contact form on your website. Read a question, write a reply, and set its stage.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — inquiries will show here once the database is connected.</p><?php endif; ?>
  <?php if (empty($inquiries)): ?><p>No inquiries yet.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Service</th><th>Question</th><th>Stage</th><th>Reply in Portal</th></tr>
      <?php foreach ($inquiries as $q): ?>
        <tr>
          <td><?php echo htmlspecialchars($q['name']); ?></td><td><?php echo htmlspecialchars($q['email']); ?></td>
          <td><?php echo htmlspecialchars($q['service'] ?? ''); ?></td><td><?php echo htmlspecialchars($q['message']); ?></td>
          <td><?php echo inquiry_badge($q['status']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="reply_inquiry" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $q['id']; ?>">
              <textarea name="message" rows="2" required placeholder="Write your reply — it shows in the person's portal if they registered with this email"></textarea>
              <button type="submit">Reply</button>
            </form>
            <form method="post"><input type="hidden" name="update_inquiry_status" value="1"><input type="hidden" name="inquiry_id" value="<?php echo (int) $q['id']; ?>">
              <select name="status"><option value="new" <?php echo $q['status'] === 'new' ? 'selected' : ''; ?>>new — just arrived</option><option value="contacted" <?php echo $q['status'] === 'contacted' ? 'selected' : ''; ?>>contacted — talked to</option><option value="done" <?php echo $q['status'] === 'done' ? 'selected' : ''; ?>>done — finished</option></select>
              <button type="submit">Set stage</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <p><a href="dashboard.php">← Back to Admin Home</a></p>
</section>
</body>
</html>
