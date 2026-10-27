<?php
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/services.php';
$db = get_db_connection();
$services = [];
if ($db) {
    try { $services = $db->query("SELECT title, slug, description FROM services WHERE is_active = 1 ORDER BY id")->fetchAll(); } catch (Throwable $e) { $services = []; }
}
// v3: always show ALL 8 catalogue services. DB rows overlay catalogue
// titles/descriptions where they match by slug, so nothing is ever
// hidden just because the services table only has 5 rows.
$merged = [];
foreach (service_catalogue() as $slug => $s) {
    $merged[$slug] = ['title' => $s['title'], 'slug' => $slug, 'description' => $s['short'], 'icon' => $s['icon']];
}
foreach ($services as $row) {
    $slug = $row['slug'] ?? '';
    if (!$slug) { $f = find_service($row['title']); $slug = $f['slug'] ?? ''; }
    if ($slug && isset($merged[$slug])) {
        $merged[$slug]['title'] = $row['title'] ?: $merged[$slug]['title'];
        $merged[$slug]['description'] = $row['description'] ?: $merged[$slug]['description'];
    } elseif (!empty($row['title'])) {
        $merged[$slug ?: $row['title']] = ['title' => $row['title'], 'slug' => $slug, 'description' => $row['description'] ?? '', 'icon' => '✦'];
    }
}
$services = array_values($merged);
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
<link rel="stylesheet" href="css/style.css?v=4">
</head>
<body>
<header>
  <div class="header-brand">
    <strong>Umer Sajid</strong>
  </div>
  <nav>
    <a href="#about">About</a>
    <a href="#skills">Skills</a>
    <a href="#services">Services</a>
    <a href="#process">Process</a>
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
        <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Umer Sajid — professional portrait in a dark suit">
        <p class="profile-caption">Umer Sajid · Professional Portfolio</p>
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
    <p class="about-intro">Hello, I am Umer Sajid — a software engineer, IT professional and creative content creator from Muzaffarabad, Azad Kashmir. I build modern websites, manage databases and create family-friendly, positive video content. My goal is simple: honest work, delivered on time, that you are proud to share.</p>
    <blockquote class="motivation">“Dream big, work honestly, and let every small step take you further.”</blockquote>

    <h3>Education</h3>
    <div class="about-grid">
      <div class="card about-card">
        <p class="about-date">M.S. · University of Azad Jammu &amp; Kashmir, Muzaffarabad</p>
        <h4>MS in Software Engineering</h4>
        <p>Advanced study in software engineering — building reliable, modern software and digital solutions.</p>
      </div>
      <div class="card about-card">
        <p class="about-date">B.Sc. · Mirpur University of Science and Technology (MUST)</p>
        <h4>B.Sc. Software Engineering</h4>
        <p>Foundation in software design, programming, databases and problem solving.</p>
      </div>
      <div class="card about-card">
        <p class="about-date">International Diploma · China</p>
        <h4>One-Year Diploma</h4>
        <p>One year of international study and training in China, adding a global perspective to my work.</p>
      </div>
    </div>

    <h3>Working Experience</h3>
    <div class="about-grid">
      <div class="card about-card">
        <p class="about-date">07/09/2021 – Current · Muzaffarabad, Azad Kashmir</p>
        <h4>IT Engineer</h4>
        <p>Azad Jammu and Kashmir Electricity Department, Muzaffarabad — IT systems, support and digital operations.</p>
      </div>
      <div class="card about-card">
        <p class="about-date">08/11/2019 – 28/10/2020 · Muzaffarabad, Azad Kashmir, Pakistan</p>
        <h4>Database Administrator</h4>
        <p>Azad Jammu and Kashmir Electricity Department, Muzaffarabad — database management, integrity and reliable data systems.</p>
      </div>
    </div>

    <h3>International Certification</h3>
    <div class="about-grid">
      <div class="card about-card">
        <p class="about-date">Udemy · International Certification</p>
        <h4>Generative AI</h4>
        <p>Machine Supervised Learning: Regression in Python 3 and Math — practical AI and machine-learning skills for modern projects.</p>
      </div>
    </div>

    <h3>Networks &amp; Memberships</h3>
    <div class="about-grid">
      <div class="card about-card">
        <p class="about-date">30/10/2019 – Current · Pakistan</p>
        <h4>Professional Affiliation</h4>
        <p>Registered Engineer with the Pakistan Engineering Council (PEC) since 2019.<br>Registration Number: <strong>COMP/18605</strong></p>
      </div>
    </div>

    <p class="about-links">
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
        <div class="card service-card">
          <p class="service-card-icon"><?php echo htmlspecialchars($s['icon'] ?? '✦'); ?></p>
          <h3><?php echo htmlspecialchars($s['title']); ?></h3>
          <p><?php echo htmlspecialchars($s['description']); ?></p>
          <?php if ($slug): ?><p><a href="service.php?slug=<?php echo urlencode($slug); ?>">View details →</a></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>


  <section id="skills">
    <h2>Skills</h2>
    <p class="section-lead">Tools and skills I use every day to deliver clean, professional work.</p>
    <div class="skill-chips">
      <span>Website Design</span><span>PHP &amp; MySQL</span><span>Video Editing</span>
      <span>Voiceover (Urdu &amp; English)</span><span>Social Media</span><span>Logo &amp; Thumbnails</span>
      <span>SEO Basics</span><span>Content Writing</span><span>AI Tools</span>
    </div>
  </section>

  <section id="process">
    <h2>How I Work</h2>
    <p class="section-lead">A simple, honest process — no confusion, no hidden steps.</p>
    <div class="grid steps-grid">
      <div class="card"><h3>1 · Tell Me</h3><p>Send your idea through the contact form or your user panel, with as much detail as you can.</p></div>
      <div class="card"><h3>2 · Plan</h3><p>I confirm the scope, price range and timeline before any work starts.</p></div>
      <div class="card"><h3>3 · Build</h3><p>You get updates in your portal, and you can reply there at any time.</p></div>
      <div class="card"><h3>4 · Deliver</h3><p>Final work is delivered, checked with you, and revised on the main message if needed.</p></div>
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
