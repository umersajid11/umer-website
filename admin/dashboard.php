<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
// Add a client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = trim($_POST['name'] ?? '');
    if (!$db) {
        $message = 'Database not connected yet — client could not be saved.';
    } elseif ($name === '') {
        $message = 'Client name is required.';
    } else {
        $stmt = $db->prepare("INSERT INTO clients (name, email, phone, service_needed, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['service_needed'] ?? ''), trim($_POST['notes'] ?? ''), trim($_POST['status'] ?? 'new')]);
        $message = 'Client added successfully.';
    }
}
// Update a client status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['client_id']) && $db) {
    $stmt = $db->prepare("UPDATE clients SET status = ? WHERE id = ?");
    $stmt->execute([trim($_POST['status'] ?? 'new'), (int) $_POST['client_id']]);
    $message = 'Client status updated.';
}
$clients = []; $inquiries = [];
if ($db) {
    try { $clients = $db->query("SELECT id, name, email, phone, service_needed, notes, status, created_at FROM clients ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) { $clients = []; }
    try { $inquiries = $db->query("SELECT id, name, email, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 50")->fetchAll(); } catch (Throwable $e) { $inquiries = []; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Admin Panel</strong><nav><a href="../index.php">Home</a> <a href="change-password.php">Change Password</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>Client Records</h1>
  <p>Logged in as <?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?>
    <p class="notice">Database not connected yet — no client records can be loaded. Import database.sql and update backend/config.php, then reload this page.</p>
  <?php else: ?>
    <h2>Add Client</h2>
    <form method="post">
      <input type="hidden" name="add_client" value="1">
      <label>Name <input type="text" name="name" required></label>
      <label>Email <input type="email" name="email"></label>
      <label>Phone <input type="text" name="phone"></label>
      <label>Service needed <input type="text" name="service_needed"></label>
      <label>Notes <textarea name="notes" rows="3"></textarea></label>
      <label>Status
        <select name="status">
          <option value="new">new</option><option value="contacted">contacted</option><option value="done">done</option>
        </select>
      </label>
      <button class="btn" type="submit">Add Client</button>
    </form>
    <h2>Clients (<?php echo count($clients); ?>)</h2>
    <?php if (empty($clients)): ?>
      <p>No client records yet. Use the form above to add one.</p>
    <?php else: ?>
      <table>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Service Needed</th><th>Status</th><th>Created</th><th>Change Status</th></tr>
        <?php foreach ($clients as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['id']); ?></td>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><?php echo htmlspecialchars($c['email']); ?></td>
            <td><?php echo htmlspecialchars($c['phone']); ?></td>
            <td><?php echo htmlspecialchars($c['service_needed']); ?></td>
            <td><?php echo htmlspecialchars($c['status']); ?></td>
            <td><?php echo htmlspecialchars($c['created_at']); ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="client_id" value="<?php echo (int) $c['id']; ?>">
                <select name="status">
                  <option value="new" <?php echo $c['status'] === 'new' ? 'selected' : ''; ?>>new</option>
                  <option value="contacted" <?php echo $c['status'] === 'contacted' ? 'selected' : ''; ?>>contacted</option>
                  <option value="done" <?php echo $c['status'] === 'done' ? 'selected' : ''; ?>>done</option>
                </select>
                <button type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <h2>Recent Inquiries (<?php echo count($inquiries); ?>)</h2>
    <?php if (empty($inquiries)): ?><p>No inquiries yet.</p><?php else: ?>
      <table>
        <tr><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Created</th></tr>
        <?php foreach ($inquiries as $q): ?>
          <tr><td><?php echo htmlspecialchars($q['name']); ?></td><td><?php echo htmlspecialchars($q['email']); ?></td><td><?php echo htmlspecialchars($q['message']); ?></td><td><?php echo htmlspecialchars($q['status']); ?></td><td><?php echo htmlspecialchars($q['created_at']); ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  <?php endif; ?>
</section>
</body>
</html>
