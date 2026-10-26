<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
start_app_session();
$db = get_db_connection();
$error = ''; $success = '';
if (is_user_logged_in()) { header('Location: dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$db) {
        $error = 'Database not connected yet.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter your name and a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Account created. You can now log in.';
        } catch (Throwable $e) {
            $error = 'Could not create the account — that email may already be registered.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Register — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="../index.php">Home</a></nav></header>
<section>
  <h1>Create Your Account</h1>
  <?php if ($error): ?><p class="notice"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <?php if ($success): ?><p><?php echo htmlspecialchars($success); ?></p><p><a href="login.php">Go to Login</a></p><?php endif; ?>
  <form method="post">
    <label>Name <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn" type="submit">Register</button>
  </form>
</section>
</body>
</html>
