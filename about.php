<?php
// Full About page — the detailed content moved off the shortened front page.
// Same theme/nav/footer as the rest of the site; no DB needed for this page.
require_once __DIR__ . '/backend/database.php';
require_once __DIR__ . '/backend/content.php';
$db = get_db_connection();
$aboutIntro = get_site_setting($db, 'about_intro');
$motivationQuote = get_site_setting($db, 'motivation_quote');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Me — Umer Sajid</title>
<meta name="description" content="About Umer Sajid — education, working experience, international certification and Pakistan Engineering Council membership.">
<link rel="stylesheet" href="css/style.css?v=8">
</head>
<body>
<header>
  <div class="header-brand"><strong>Umer Sajid</strong></div>
  <nav>
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
    <a href="services.php">Services</a>
    <a href="blog.php">Blog</a>
    <a href="index.php#contact">Contact</a>
    <a href="user/login.php" class="nav-profile" title="User Panel" aria-label="User Panel"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><circle cx="12" cy="7" r="4" fill="#ffffff"/><path d="M12 12c-4.4 0-8 3.1-8 7.5V21h16v-1.5C20 15.1 16.4 12 12 12z" fill="#ffffff"/></svg><span class="sr-only">User Panel</span></a>
  </nav>
</header>
<main>
  <section id="about">
    <h2>About Me</h2>
    <p class="about-intro"><?php echo htmlspecialchars($aboutIntro); ?></p>
    <blockquote class="motivation"><?php echo htmlspecialchars($motivationQuote); ?></blockquote>

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
      <a href="https://www.facebook.com/umermughal11">Facebook</a> ·
      <a href="https://www.instagram.com/ii_umermughal/">Instagram</a>
    </p>
    <p><a class="btn" href="index.php#contact">Work With Me</a></p>
  </section>
</main>
<footer>
  <p>© Umer Sajid · MS Software Engineering · One-year diploma from China</p>
  <p><a href="about.php">About</a> · <a href="services.php">Services</a> · <a href="blog.php">Blog</a> · <a href="user/login.php">User Panel</a></p>
  <p class="footer-admin"><a href="admin/login.php">Admin</a></p>
</footer>
<script src="js/script.js"></script>
<script src="js/assistant.js"></script>
</body>
</html>
