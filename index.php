<?php
require_once __DIR__ . '/backend/database.php';
$db = get_db_connection();
$services = [];
if ($db) {
    try {
        $services = $db->query("SELECT title, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll();
    } catch (Throwable $e) { $services = []; }
}
if (empty($services)) {
    $services = [
        ['title' => 'Website Building and Fixing', 'description' => 'Personal and business websites, built folder-wise and fixed on existing sites.'],
        ['title' => 'Video Editing', 'description' => 'Short-form and cinematic video editing from your own clips.'],
        ['title' => 'Voiceover', 'description' => 'Natural voiceover in English and Urdu for videos and shorts.'],
        ['title' => 'Scripts', 'description' => 'Video scripts, captions and story ideas for social media.'],
        ['title' => 'Social Media Posts and Management', 'description' => 'Posts, captions, hashtags and posting plans for Facebook, Instagram and YouTube.'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Umer Sajid — Personal Portfolio</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
  <strong>Umer Sajid</strong>
  <nav>
    <a href="#about">About</a>
    <a href="#services">Services</a>
    <a href="#contact">Contact</a>
    <a href="admin/login.php">Admin</a>
    <a href="user/login.php">User Panel</a>
  </nav>
</header>

<main>
  <section class="hero">
    <div class="hero-inner">
      <span class="tag">Personal Portfolio</span>
      <h1>Umer Sajid</h1>
      <p>MS in Software Engineering student at the University of AJK, Muzaffarabad.<br>
      I create wholesome, family-friendly videos and help people build their presence online.</p>
      <p>
        <a class="btn" href="#services">My Services</a>
        <a class="btn" href="#contact">Work With Me</a>
      </p>
      <div class="hero-cards">
        <div class="hero-card">🎓 MS Software Engineering</div>
        <div class="hero-card">🎬 Video &amp; Voiceover</div>
        <div class="hero-card">🌐 Websites</div>
      </div>
    </div>
  </section>

  <?php if (!$db): ?>
    <section><p class="notice">Database not connected yet — the site is showing its built-in content. Connect the database (see README.md) to load services and client records from MySQL.</p></section>
  <?php endif; ?>

  <section id="about">
    <h2>About Me</h2>
    <p>Hello, I am Umer Sajid, a student of MS in Software Engineering at the University of Azad Jammu and Kashmir (AJK), Muzaffarabad. I work on creative video content, voiceovers, and websites — always family-friendly and positive.</p>
    <p>
      <a href="https://linkedin.com/umersajid">LinkedIn</a> ·
      <!-- Facebook and Instagram links below are PLACEHOLDERS — exact profile URLs are unverified. See README.md. -->
      <a href="https://facebook.com/placeholder-umersajid">Facebook</a> ·
      <a href="https://instagram.com/placeholder-umersajid">Instagram</a>
    </p>
  </section>

  <section id="services">
    <h2>Services</h2>
    <div class="grid">
      <?php foreach ($services as $s): ?>
        <div class="card">
          <h3><?php echo htmlspecialchars($s['title']); ?></h3>
          <p><?php echo htmlspecialchars($s['description']); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="contact">
    <h2>Contact / Inquiry</h2>
    <p>Send an inquiry below. Inquiries are saved to the database once it is connected; otherwise this form is a preview only.</p>
    <form method="post" action="index.php#contact">
      <label>Name <input type="text" name="name" required></label>
      <label>Email <input type="email" name="email" required></label>
      <label>Message <textarea name="message" rows="4" required></textarea></label>
      <button class="btn" type="submit">Send Inquiry</button>
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cName = trim($_POST['name'] ?? '');
        $cEmail = trim($_POST['email'] ?? '');
        $cMessage = trim($_POST['message'] ?? '');
        if (!$db) {
            echo '<p class="notice">Database not connected yet — your inquiry could not be saved. Connect the database (see README.md) and try again.</p>';
        } elseif ($cName === '' || !filter_var($cEmail, FILTER_VALIDATE_EMAIL) || $cMessage === '') {
            echo '<p class="notice">Please enter your name, a valid email address, and a message.</p>';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)");
                $stmt->execute([$cName, $cEmail, $cMessage]);
                echo '<p>Thank you — your inquiry was saved. It will appear in the admin panel and, if you register with the same email, in your user panel.</p>';
            } catch (Throwable $e) {
                echo '<p class="notice">Something went wrong saving your inquiry. Please try again.</p>';
            }
        }
    }
    ?>
  </section>
</main>

<footer>© Umer Sajid · MS Software Engineering, University of AJK, Muzaffarabad</footer>
<script src="js/script.js"></script>
</body>
</html>
