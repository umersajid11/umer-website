<?php
// Clients (v6) — ONLY the clients list. One job: see your clients,
// change their stage, hide one (Archive) or remove one (Delete).
// To ADD a new client, go to the separate "Add Client" page.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/messages.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
$showArchived = !empty($_GET['show_archived']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = ? WHERE id = ?");
    $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['client_id']]);
    $message = 'Client stage updated.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_client'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = 'archived' WHERE id = ?");
    $stmt->execute([(int) $_POST['client_id']]);
    $message = 'Client archived. It is hidden from the main list. Tap "Show Archived" to see it again.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_client'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = 'new' WHERE id = ?");
    $stmt->execute([(int) $_POST['client_id']]);
    $message = 'Client put back on the main list.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'], $_POST['client_id']) && $db) {
    $cid = (int) $_POST['client_id'];
    $stmt = $db->prepare("SELECT email FROM clients WHERE id = ? LIMIT 1"); $stmt->execute([$cid]); $crow = $stmt->fetch();
    if ($crow && !empty($crow['email'])) {
        $uid = find_user_id_by_email($db, $crow['email']);
        if ($uid) { $del = $db->prepare("DELETE FROM messages WHERE user_id = ?"); $del->execute([$uid]); }
    }
    $stmt = $db->prepare("DELETE FROM clients WHERE id = ?"); $stmt->execute([$cid]);
    $message = 'Client deleted, together with that client\'s messages. This cannot be undone.';
}
$clients = []; $visibleClients = []; $archivedClients = [];
if ($db) {
    try { $clients = $db->query("SELECT id, name, email, phone, service_needed, notes, status, created_at FROM clients ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) {}
    foreach ($clients as $c) { if ($c['status'] === 'archived') $archivedClients[] = $c; else $visibleClients[] = $c; }
}
$clientList = $showArchived ? $clients : $visibleClients;
function client_badge($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clients — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>👥 Clients</h1>
  <p class="page-intro">This page is only for your clients. Change a client's stage, hide a client, or remove a client. To add a new client, use the separate Add Client page.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — clients will show here once the database is connected.</p><?php endif; ?>
  <p>
    <a class="btn btn-small" href="add-client.php">➕ Add a New Client</a>
    <?php if ($showArchived): ?><a class="btn btn-small btn-outline" href="clients.php">Hide Archived</a><?php else: ?><a class="btn btn-small btn-outline" href="clients.php?show_archived=1">Show Archived (<?php echo count($archivedClients); ?>)</a><?php endif; ?>
  </p>
  <p class="muted">Showing: <?php echo $showArchived ? 'all clients, including archived ones' : 'active clients only (' . count($visibleClients) . ')'; ?>. "Archive" means: hide this client, do not delete it. "Delete" means: remove it for good, with its messages.</p>
  <?php if (empty($clientList)): ?><p>No client records in this view.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Phone</th><th>Service Needed</th><th>Stage</th><th>Change Stage</th><th>Hide / Remove</th></tr>
      <?php foreach ($clientList as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['name']); ?></td><td><?php echo htmlspecialchars($c['email']); ?></td>
          <td><?php echo htmlspecialchars($c['phone']); ?></td><td><?php echo htmlspecialchars($c['service_needed']); ?></td>
          <td><?php echo client_badge($c['status']); ?></td>
          <td><form method="post"><input type="hidden" name="update_status" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>">
            <select name="status"><option value="new" <?php echo $c['status'] === 'new' ? 'selected' : ''; ?>>new — just added</option><option value="contacted" <?php echo $c['status'] === 'contacted' ? 'selected' : ''; ?>>contacted — talked to</option><option value="done" <?php echo $c['status'] === 'done' ? 'selected' : ''; ?>>done — finished</option></select>
            <button type="submit">Update</button></form></td>
          <td>
            <?php if ($c['status'] === 'archived'): ?>
              <form method="post"><input type="hidden" name="restore_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Put Back</button></form>
            <?php else: ?>
              <form method="post"><input type="hidden" name="archive_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Archive (Hide)</button></form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Are you sure? Delete this client for good? This also deletes that client\'s messages and cannot be undone.');"><input type="hidden" name="delete_client" value="1"><input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>"><button type="submit">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <p><a href="dashboard.php">← Back to Admin Home</a></p>
</section>
</body>
</html>
