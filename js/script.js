// Umer Sajid website — lightweight UX only, no external requests.
// Preserves the original 3D hero tilt; adds only safe, non-blocking
// improvements. If any part fails, the pages stay fully usable.
document.addEventListener('DOMContentLoaded', function () {
  document.body.classList.add('js');

  // 1) 3D tilt for the hero — CSS/JS only, no heavy libraries.
  // Disabled automatically for touch devices and reduced-motion users.
  var hero = document.querySelector('.hero-inner');
  var wrap = document.querySelector('.hero');
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (hero && wrap && !reducedMotion && window.matchMedia('(pointer: fine)').matches) {
    wrap.addEventListener('mousemove', function (e) {
      var r = wrap.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - 0.5;
      var y = (e.clientY - r.top) / r.height - 0.5;
      hero.style.transform = 'rotateY(' + (x * 10) + 'deg) rotateX(' + (-y * 8) + 'deg)';
    });
    wrap.addEventListener('mouseleave', function () {
      hero.style.transform = 'rotateY(0deg) rotateX(0deg)';
    });
  }

  // 2) Gentle reveal on scroll. Content is visible by default in CSS;
  // the .reveal hidden state only applies once JS is running, so a
  // no-JS visit never hides anything.
  var revealables = document.querySelectorAll('section, .card, .hero-card');
  revealables.forEach(function (el) { el.classList.add('reveal'); });
  if ('IntersectionObserver' in window && !reducedMotion) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealables.forEach(function (el) { observer.observe(el); });
  } else {
    revealables.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // 3) Highlight the current section in the top navigation on the
  // front page, so visitors always know where they are.
  var navLinks = document.querySelectorAll('header nav a[href^="#"]');
  var sections = document.querySelectorAll('section[id]');
  if (navLinks.length && sections.length && 'IntersectionObserver' in window) {
    var navObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        navLinks.forEach(function (link) {
          link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
        });
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    sections.forEach(function (s) { navObserver.observe(s); });
  }
});

// 4) Hero slider — auto-advance, dots, arrows, pause on hover/focus.
// No external code; if JS fails, slide 1 (owner photo) stays visible.
(function () {
  var slider = document.getElementById('heroSlider');
  if (!slider) return;
  var slides = slider.querySelectorAll('.hero-slide');
  var dots = slider.querySelectorAll('.slide-dot');
  var prev = slider.querySelector('.slide-prev');
  var next = slider.querySelector('.slide-next');
  if (slides.length < 2) return;
  var current = 0, timer = null;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(i) {
    current = (i + slides.length) % slides.length;
    slides.forEach(function (s, j) { s.classList.toggle('is-active', j === current); });
    dots.forEach(function (d, j) { d.classList.toggle('is-active', j === current); });
  }
  function start() { if (!reduced && !timer) timer = setInterval(function () { show(current + 1); }, 5000); }
  function stop() { if (timer) { clearInterval(timer); timer = null; } }
  if (prev) prev.addEventListener('click', function () { stop(); show(current - 1); start(); });
  if (next) next.addEventListener('click', function () { stop(); show(current + 1); start(); });
  dots.forEach(function (d) { d.addEventListener('click', function () { stop(); show(parseInt(d.getAttribute('data-slide'), 10) || 0); start(); }); });
  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', start);
  slider.addEventListener('focusin', stop);
  slider.addEventListener('focusout', start);
  start();
})();

// v3: chat boxes always open scrolled to the latest message.
document.addEventListener('DOMContentLoaded', function () {
  var box = document.getElementById('chatBox');
  if (box) box.scrollTop = box.scrollHeight;
});
