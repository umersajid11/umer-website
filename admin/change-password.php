<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
if (!is_admin_logged_in()) { header('Location: login.php'); exit; }
$db = get_db_connection();
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$db) {
        $error = 'Database not connected yet.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            $upd = $db->prepare("UPDATE admins SET password_hash = ?, must_change_password = 0 WHERE id = ?");
            $upd->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            $_SESSION['admin_must_change_password'] = false;
            $success = 'Password changed. You can now use the dashboard.';
        }
    }
}
$mustChange = !empty($_SESSION['admin_must_change_password']);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password — Admin</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Admin Panel</strong><nav><a href="../index.php">Home</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>Change Password</h1>
  <?php if ($mustChange): ?><p class="notice">You are using the seed placeholder password. You must set a new password before the dashboard opens.</p><?php endif; ?>
  <?php if ($error): ?><p class="notice"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <?php if ($success): ?><p><?php echo htmlspecialchars($success); ?></p><p><a href="dashboard.php">Go to Dashboard</a></p><?php endif; ?>
  <form method="post">
    <label>Current password <input type="password" name="current_password" required></label>
    <label>New password <input type="password" name="new_password" required></label>
    <label>Confirm new password <input type="password" name="confirm_password" required></label>
    <button class="btn" type="submit">Change password</button>
  </form>
</section>
</body>
</html>
