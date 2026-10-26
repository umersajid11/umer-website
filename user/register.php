<?php
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/services.php';
start_app_session();
$db = get_db_connection();
$error = ''; $success = '';
if (is_user_logged_in()) { header('Location: dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $service = trim($_POST['service_needed'] ?? '');
    $detail = trim($_POST['project_detail'] ?? '');
    $budget = trim($_POST['budget_range'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$db) {
        $error = 'Database not connected yet.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter your full name and a valid email address.';
    } elseif ($service === '' || strlen($detail) < 20) {
        $error = 'Please choose the service you need and describe your project in at least 20 characters, so only serious work requests come through.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, city, service_needed, project_detail, budget_range, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $city, $service, $detail, $budget, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Account created. You can now log in — your project detail is saved for the admin to review.';
        } catch (Throwable $e) {
            // Backward compatibility: older users table without the new columns.
            try {
                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $success = 'Account created (project detail could not be stored — the database needs the new columns from database.sql). You can now log in.';
            } catch (Throwable $e2) {
                $error = 'Could not create the account — that email may already be registered.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Register — Umer Sajid</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<header><strong>Umer Sajid</strong><nav><a href="../index.php">Home</a><a href="login.php">Login</a></nav></header>
<section>
  <h1>Create Your Account</h1>
  <p class="muted">This is a work portal, not a casual message box. Please fill in the detail honestly — it helps the admin understand your project and reply with a real plan. Phone/WhatsApp is optional, but it speeds up serious work.</p>
  <?php if ($error): ?><p class="notice"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
  <?php if ($success): ?><p><?php echo htmlspecialchars($success); ?></p><p><a href="login.php">Go to Login</a></p><?php endif; ?>
  <form method="post">
    <label>Full name <input type="text" name="name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Phone / WhatsApp (optional, encouraged) <input type="text" name="phone" placeholder="e.g. 03xx-xxxxxxx"></label>
    <label>City <input type="text" name="city" placeholder="e.g. Muzaffarabad"></label>
    <label>What service do you need?
      <select name="service_needed" required>
        <option value="">— Choose a service —</option>
        <?php foreach (service_catalogue() as $s): ?><option value="<?php echo htmlspecialchars($s['title']); ?>"><?php echo htmlspecialchars($s['title']); ?></option><?php endforeach; ?>
        <option value="Other">Other / Not sure yet</option>
      </select>
    </label>
    <label>Project detail — what do you want, and by when? <textarea name="project_detail" rows="4" required placeholder="At least 20 characters: describe your project, your goal, and your timeline."></textarea></label>
    <label>Budget range
      <select name="budget_range">
        <option value="">— Prefer not to say yet —</option>
        <option>Under PKR 5,000</option><option>PKR 5,000 – 15,000</option><option>PKR 15,000 – 50,000</option><option>PKR 50,000+</option><option>To be discussed</option>
      </select>
    </label>
    <label>Password (min. 8 characters) <input type="password" name="password" required></label>
    <button class="btn" type="submit">Register</button>
  </form>
  <p class="muted">Already have an account? <a href="login.php">Log in</a>.</p>
</section>
</body>
</html>
