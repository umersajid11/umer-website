<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
$db = get_db_connection();
$error = '';
if (is_user_logged_in()) { header('Location: dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$db) {
        $error = 'Database not connected yet — import database.sql and update backend/config.php, then try again.';
    } elseif (attempt_user_login($db, $_POST['email'] ?? '', $_POST['password'] ?? '')) {
        header('Location: dashboard.php'); exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Login — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="../index.php">Home</a></nav></header>
<section>
  <h1>User Login</h1>
  <?php if ($error): ?><p class="notice"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet.</p><?php endif; ?>
  <form method="post">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p>No account yet? <a href="register.php">Register here</a>.</p>
</section>
</body>
</html>
