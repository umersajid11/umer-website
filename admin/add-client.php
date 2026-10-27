<?php
// Add Client (v6) — ONLY the Add Client form. One job: add one new
// client. Nothing else is on this page.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
if (isset($_GET['action']) && $_GET['action'] === 'logout') { app_logout('login.php'); }
require_admin_login();
$db = get_db_connection();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $name = trim($_POST['name'] ?? '');
    if (!$db) $message = 'Database not connected yet — client could not be saved.';
    elseif ($name === '') $message = 'Client name is required — please type the name first.';
    else {
        $stmt = $db->prepare("INSERT INTO clients (name, email, phone, service_needed, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['service_needed'] ?? ''), trim($_POST['notes'] ?? ''), trim($_POST['status'] ?? 'new')]);
        $message = '✅ Client added successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Client — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<?php require __DIR__ . '/_menu.php'; ?>
<section>
  <h1>➕ Add Client</h1>
  <p class="page-intro">This page does only one thing: add one new client. Fill the boxes and tap "Add Client".</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — the client cannot be saved until the database is connected.</p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="add_client" value="1">
    <label>Name (required) <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email"></label>
    <label>Phone <input type="text" name="phone"></label>
    <label>Service needed <input type="text" name="service_needed"></label>
    <label>Notes <textarea name="notes" rows="3"></textarea></label>
    <label>Stage
      <select name="status"><option value="new">new — just added</option><option value="contacted">contacted — talked to</option><option value="done">done — finished</option></select>
    </label>
    <button class="btn" type="submit">Add Client</button>
  </form>
  <p><a href="clients.php">← Back to Clients</a> · <a href="dashboard.php">Admin Home</a></p>
</section>
</body>
</html>
