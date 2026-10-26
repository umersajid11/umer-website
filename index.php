<?php
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/services.php';
$db = get_db_connection();
$services = [];
if ($db) {
    try { $services = $db->query("SELECT title, slug, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll(); } catch (Throwable $e) { $services = []; }
}
if (empty($services)) {
    foreach (service_catalogue() as $slug => $s) {
        $services[] = ['title' => $s['title'], 'slug' => $slug, 'description' => $s['short']];
    }
}
$preselected = trim($_GET['service'] ?? '');
$profilePhoto = 'images/profile.jpg'; // Owner's Facebook profile DP (150x150, low-res — replace with a higher-res photo when sent; no code change needed).
$hasPhoto = file_exists(__DIR__ . '/' . $profilePhoto);
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
  <div class="header-brand">
    <strong>Umer Sajid</strong>
  </div>
  <nav>
    <a href="#about">About</a>
    <a href="#services">Services</a>
    <a href="#contact">Contact</a>
    <a href="user/login.php" class="nav-cta">User Panel</a>
  </nav>
</header>

<main>
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-slider" id="heroSlider" aria-label="AI and technology slider">
        <div class="hero-slide is-active">
          <img class="slide-image" src="images/media-generation-slider-ai-1-0-59ab8d95-8c65-4b68-a4f8-3f4f60c6f4f1.webp" alt="Artificial intelligence and technology">
          <div class="slide-text">
            <h2 class="slide-title">AI &amp; Technology</h2>
            <p class="slide-sub">Smart ideas · Modern tools</p>
            <p class="muted">Using today's technology to build work that is useful, modern and easy to use.</p>
          </div>
        </div>
        <div class="hero-slide">
          <img class="slide-image" src="images/media-generation-slider-tech-2-0-65d7dd84-8bb6-4226-bc60-d28d6ce8c6af.webp" alt="Modern technology">
          <div class="slide-text">
            <h2 class="slide-title">Modern Technology</h2>
            <p class="slide-sub">Web · Software · Digital</p>
            <p class="muted">Clean, fast and professional digital work — built for real people, not just screens.</p>
          </div>
        </div>
        <div class="hero-slide">
          <img class="slide-image" src="images/media-generation-slider-code-3-0-b1481f35-d15c-4c8e-8d10-84274e47183e.webp" alt="Software and code">
          <div class="slide-text">
            <h2 class="slide-title">Code &amp; Software</h2>
            <p class="slide-sub">Development · Problem solving</p>
            <p class="muted">From an idea to a working product — planned, built and tested properly.</p>
          </div>
        </div>
        <button class="slide-arrow slide-prev" type="button" aria-label="Previous slide">‹</button>
        <button class="slide-arrow slide-next" type="button" aria-label="Next slide">›</button>
        <div class="slide-dots" role="tablist" aria-label="Choose slide">
          <button class="slide-dot is-active" type="button" data-slide="0" aria-label="Slide 1"></button>
          <button class="slide-dot" type="button" data-slide="1" aria-label="Slide 2"></button>
          <button class="slide-dot" type="button" data-slide="2" aria-label="Slide 3"></button>
        </div>
      </div>
      <?php if ($hasPhoto): ?>
      <div class="profile-photo">
        <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Umer Sajid — professional photo">
      </div>
      <?php endif; ?>
      <span class="tag">Personal Portfolio</span>
      <h1>Umer Sajid</h1>
      <p class="hero-credentials">MS in Software Engineering · One-year diploma from China</p>
      <p>I create wholesome, family-friendly videos and help people build their presence online — with honest work, clear communication and attention to detail.</p>
      <blockquote class="motivation">“Dream big, work honestly, and let every small step take you further.”</blockquote>
      <p>
        <a class="btn" href="#services">My Services</a>
        <a class="btn" href="#contact">Work With Me</a>
      </p>
      <div class="hero-cards">
        <div class="hero-card">🎓 MS Software Engineering</div>
        <div class="hero-card">🎓 One-year Diploma — China</div>
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
    <p>Hello, I am Umer Sajid — MS in Software Engineering, with a one-year diploma from China. I work on creative video content, voiceovers, and websites — always family-friendly and positive. My goal is simple: good work, delivered on time, that you are proud to share.</p>
    <blockquote class="motivation">“Dream big, work honestly, and let every small step take you further.”</blockquote>
    <p>
      <a href="https://linkedin.com/umersajid">LinkedIn</a> ·
      <a href="https://www.facebook.com/profile.php?id=61595644480292">Facebook</a> ·
      <a href="https://www.instagram.com/ii_umermughal/">Instagram</a>
    </p>
  </section>

  <section id="services">
    <h2>Services</h2>
    <p>Click any service to see what is included, who it is for, and how to start it.</p>
    <div class="grid">
      <?php foreach ($services as $s):
        $slug = $s['slug'] ?? '';
        if (!$slug) { $found = find_service($s['title']); $slug = $found['slug'] ?? ''; }
      ?>
        <div class="card">
          <h3><?php echo htmlspecialchars($s['title']); ?></h3>
          <p><?php echo htmlspecialchars($s['description']); ?></p>
          <?php if ($slug): ?><p><a href="service.php?slug=<?php echo urlencode($slug); ?>">View details →</a></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="contact">
    <h2>Contact / Inquiry</h2>
    <p>Send an inquiry below. Serious inquiries only, please — tell me what you need, and I will reply in your portal and here. Inquiries are saved to the database once it is connected; otherwise this form is a preview only.</p>
    <form method="post" action="index.php#contact">
      <label>Name <input type="text" name="name" required></label>
      <label>Email <input type="email" name="email" required></label>
      <label>Service
        <select name="service">
          <option value="">— Choose a service —</option>
          <?php foreach (service_catalogue() as $slug => $s): ?>
            <option value="<?php echo htmlspecialchars($s['title']); ?>" <?php echo ($preselected === $slug || $preselected === $s['title']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Message <textarea name="message" rows="4" required></textarea></label>
      <button class="btn" type="submit">Send Inquiry</button>
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cName = trim($_POST['name'] ?? '');
        $cEmail = trim($_POST['email'] ?? '');
        $cService = trim($_POST['service'] ?? '');
        $cMessage = trim($_POST['message'] ?? '');
        if (!$db) {
            echo '<p class="notice">Database not connected yet — your inquiry could not be saved. Connect the database (see README.md) and try again.</p>';
        } elseif ($cName === '' || !filter_var($cEmail, FILTER_VALIDATE_EMAIL) || $cMessage === '') {
            echo '<p class="notice">Please enter your name, a valid email address, and a message.</p>';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO inquiries (name, email, service, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$cName, $cEmail, $cService, $cMessage]);
                echo '<p>Thank you — your inquiry was saved. If you register in the User Panel with this same email, admin replies will appear in your portal.</p>';
            } catch (Throwable $e) {
                echo '<p class="notice">Something went wrong saving your inquiry. Please try again.</p>';
            }
        }
    }
    ?>
  </section>
</main>

<footer>
  <p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p>
  <p class="footer-admin"><a href="admin/login.php">Admin</a></p>
</footer>
<script src="js/script.js"></script>
</body>
</html>
