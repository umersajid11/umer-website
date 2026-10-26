<?php
// One-time setup: creates the seed admin account using password_hash().
// SECURITY: run this ONCE after importing database.sql, log in, change the
// placeholder password (you will be forced to), then DELETE this file.
// If an admin already exists, this page does nothing except say so.
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/auth.php';
start_app_session();
$db = get_db_connection();
$message = '';
$done = false;
if (!$db) {
    $message = 'Database not connected yet — import database.sql and update backend/config.php first, then reload this page.';
} else {
    $count = 0;
    try { $count = (int) $db->query("SELECT COUNT(*) FROM admins")->fetchColumn(); } catch (Throwable $e) { $count = 0; }
    if ($count > 0) {
        $message = 'Setup already done: an admin account already exists. Nothing was created. DELETE setup.php from the server now.';
        $done = true;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Placeholder seed credentials — MUST be changed on first login.
        // The password is deliberately documented in README.md so the owner
        // can log in once; it is not a real personal password.
        $hash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (name, email, password_hash, must_change_password) VALUES (?, ?, ?, 1)");
        $stmt->execute(['Umer Sajid', 'admin@umer-website.local', $hash]);
        $message = 'Seed admin created. Log in at admin/login.php with the placeholder credentials in README.md — you will be forced to change the password before the dashboard opens. Then DELETE setup.php.';
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — Umer Sajid</title><link rel="stylesheet" href="css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="index.php">Home</a></nav></header>
<section>
  <h1>One-time Setup</h1>
  <p class="notice">This creates ONE seed admin account only if no admin exists yet. After setup, delete setup.php from the server.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if ($db && !$done): ?>
    <form method="post"><button class="btn" type="submit">Create seed admin account</button></form>
  <?php endif; ?>
  <p><a href="admin/login.php">Go to Admin Login</a></p>
</section>
</body>
</html>
