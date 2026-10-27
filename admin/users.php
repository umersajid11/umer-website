<?php
// Users (v6) — ONLY registered users. One job: see who registered
// on your website, with the details they gave when they signed up.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$users = [];
if ($db) {
    try { $users = $db->query("SELECT id, name, email, phone, city, service_needed, project_detail, budget_range, created_at FROM users ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>🧑 Registered Users (<?php echo count($users); ?>)</h1>
  <p class="page-intro">This page is only for people who registered on your website. It does not change anything — to talk to a person, tap "Open chat" and you go to the Messages page.</p>
  <?php if (!$db): ?><p class="notice">Database not connected yet — users will show here once the database is connected.</p><?php endif; ?>
  <?php if (empty($users)): ?><p>No registered users yet.</p><?php else: ?>
    <table>
      <tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Service Needed</th><th>Budget</th><th>Project Detail</th><th>Talk</th></tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo htmlspecialchars($u['name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td><td><?php echo htmlspecialchars($u['city'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['service_needed'] ?? ''); ?></td><td><?php echo htmlspecialchars($u['budget_range'] ?? ''); ?></td>
          <td><?php echo htmlspecialchars($u['project_detail'] ?? ''); ?></td>
          <td><a href="messages.php?user_id=<?php echo (int) $u['id']; ?>">Open chat →</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <p><a href="dashboard.php">← Back to Admin Home</a></p>
</section>
</body>
</html>
