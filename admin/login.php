<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
$db = get_db_connection();
$error = '';
if (is_admin_logged_in()) {
    header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change-password.php' : 'dashboard.php'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db) {
        $error = 'Database not connected yet — import database.sql and update backend/config.php, then try again.';
    } elseif (attempt_admin_login($db, $_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change-password.php' : 'dashboard.php'));
        exit;
    } else {
        $error = 'Invalid email or password. If this is a fresh install, run setup.php once first to create the seed admin account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="../index.php">Home</a></nav></header>
<section>
  <h1>Admin Login</h1>
  <?php if ($error): ?><p class="notice"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet.</p><?php endif; ?>
  <form method="post">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p>First time? Run <a href="../setup.php">setup.php</a> once, then change the placeholder password on first login — and delete setup.php afterwards.</p>
</section>
</body>
</html>
