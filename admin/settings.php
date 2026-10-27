<?php
// Website Settings (v5) — admin only, same session auth as the dashboard.
// Lets a non-coder change the site's front-page wording from a form.
// This page NEVER touches backend/config.php, credentials, or code files:
// it only saves rows in the site_settings table, which index.php reads
// when the table exists and otherwise falls back to the built-in text.
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/content.php';
start_app_session();
require_admin_login();
$db = get_db_connection();
$message = '';
$keys = array_keys(site_setting_defaults());
$values = [];
foreach ($keys as $k) $values[$k] = get_site_setting($db, $k);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!$db) {
        $message = 'Database not connected yet — settings cannot be saved until database.sql is imported.';
    } else {
        $ok = true;
        foreach ($keys as $k) {
            if (!save_site_setting($db, $k, $_POST[$k] ?? '')) $ok = false;
        }
        $message = $ok ? 'Settings saved — the front page now uses this text.' : 'Settings could not be saved — check that the site_settings table exists (import database.sql).';
        foreach ($keys as $k) $values[$k] = get_site_setting($db, $k);
    }
}
$labels = [
    'hero_title' => 'Hero title (big name at the top)',
    'hero_intro' => 'Hero intro text (under the name)',
    'motivation_quote' => 'Motivational quote',
    'about_intro' => 'About intro paragraph',
    'contact_notice' => 'Contact section notice text',
];
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website Settings — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<header><strong>Admin Panel</strong><nav><a href="dashboard.php">Dashboard</a> <a href="blog.php">Blog</a> <a href="../index.php">Home</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>⚙️ Website Settings</h1>
  <p class="muted">Change the front-page wording here — no coding needed. Saving here only changes text shown on the site; it never changes passwords, database settings, or any code file.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — the form below shows the built-in text, but saving needs the database.</p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="save_settings" value="1">
    <?php foreach ($keys as $k): ?>
      <label><?php echo htmlspecialchars($labels[$k]); ?>
        <?php if ($k === 'hero_title'): ?>
          <input type="text" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($values[$k]); ?>">
        <?php else: ?>
          <textarea name="<?php echo $k; ?>" rows="3"><?php echo htmlspecialchars($values[$k]); ?></textarea>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
    <button class="btn" type="submit">Save Settings</button>
  </form>
  <p class="muted">Tip: after saving, open the <a href="../index.php">front page</a> and refresh to see the new text.</p>
</section>
</body>
</html>
