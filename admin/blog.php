<?php
// Admin Blog manager (v5) — admin only, same session auth as the dashboard.
// Create / edit / delete posts and switch them between Draft and Published.
// Public visitors only ever see Published posts (see blog.php).
require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/content.php';
start_app_session();
require_admin_login();
$db = get_db_connection();
$message = '';
$edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post']) && $db) {
    $ok = save_blog_post($db, (int) ($_POST['id'] ?? 0), $_POST['title'] ?? '', $_POST['slug'] ?? '', $_POST['excerpt'] ?? '', $_POST['body'] ?? '', $_POST['cover_image'] ?? '', $_POST['status'] ?? 'draft');
    $message = $ok ? 'Blog post saved.' : 'Post could not be saved — check the title/body and that the blog_posts table exists (import database.sql).';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'], $_POST['id']) && $db) {
    $message = delete_blog_post($db, (int) $_POST['id']) ? 'Blog post deleted.' : 'Post could not be deleted.';
}
if (isset($_GET['edit']) && $db) $edit = get_post_by_id($db, (int) $_GET['edit']);
$posts = get_all_posts($db);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog Manager — Admin</title><link rel="stylesheet" href="../css/style.css?v=4"></head>
<body>
<header><strong>Admin Panel</strong><nav><a href="dashboard.php">Dashboard</a> <a href="../blog.php">View Blog</a> <a href="settings.php">Settings</a> <a href="dashboard.php?action=logout">Logout</a></nav></header>
<section>
  <h1>📝 Blog Manager</h1>
  <p class="muted">Write one AI / tech post a day here. <strong>Published</strong> posts appear on the public Blog page (good for Google / AdSense and SEO); <strong>Draft</strong> posts stay hidden until you publish them.</p>
  <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
  <?php if (!$db): ?><p class="notice">Database not connected yet — posts cannot be saved until database.sql is imported and backend/config.php is set.</p><?php endif; ?>

  <div class="admin-section">
  <h2><?php echo $edit ? 'Edit Post' : 'New Post'; ?></h2>
  <form method="post">
    <input type="hidden" name="save_post" value="1">
    <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
    <label>Title <input type="text" name="title" required value="<?php echo htmlspecialchars($edit['title'] ?? ''); ?>"></label>
    <label>Slug (web address, leave blank to auto-create from the title) <input type="text" name="slug" value="<?php echo htmlspecialchars($edit['slug'] ?? ''); ?>"></label>
    <label>Short summary (excerpt, shown on the Blog list) <textarea name="excerpt" rows="3"><?php echo htmlspecialchars($edit['excerpt'] ?? ''); ?></textarea></label>
    <label>Full post text <textarea name="body" rows="12" required><?php echo htmlspecialchars($edit['body'] ?? ''); ?></textarea></label>
    <label>Cover image URL (optional) <input type="text" name="cover_image" value="<?php echo htmlspecialchars($edit['cover_image'] ?? ''); ?>"></label>
    <label>Status
      <select name="status">
        <option value="draft" <?php echo ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft (hidden)</option>
        <option value="published" <?php echo ($edit['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published (public)</option>
      </select>
    </label>
    <button class="btn" type="submit">Save Post</button>
    <?php if ($edit): ?><a class="btn btn-outline" href="blog.php">Cancel</a><?php endif; ?>
  </form>
  </div>

  <div class="admin-section">
  <h2>All Posts (<?php echo count($posts); ?>)</h2>
  <?php if (empty($posts)): ?><p>No posts yet — write your first one above.</p><?php else: ?>
    <table>
      <tr><th>Title</th><th>Slug</th><th>Status</th><th>Created</th><th>Actions</th></tr>
      <?php foreach ($posts as $p): ?>
        <tr>
          <td><?php echo htmlspecialchars($p['title']); ?></td><td><?php echo htmlspecialchars($p['slug']); ?></td>
          <td><?php echo status_badge_admin($p['status']); ?></td><td><?php echo htmlspecialchars($p['created_at']); ?></td>
          <td>
            <a href="blog.php?edit=<?php echo (int) $p['id']; ?>">Edit</a>
            <?php if ($p['status'] === 'published'): ?> · <a href="../blog-post.php?slug=<?php echo urlencode($p['slug']); ?>">View</a><?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this post? This cannot be undone.');"><input type="hidden" name="delete_post" value="1"><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><button type="submit">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  </div>
</section>
</body>
</html>
<?php
function status_badge_admin($s) { return '<span class="badge badge-' . htmlspecialchars($s) . '">' . htmlspecialchars($s) . '</span>'; }
