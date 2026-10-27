// Simple site chatbot — plain JavaScript, no framework, no external API.
// Injects a floating bubble (bottom-right, closed by default) that talks to
// backend/assistant.php by POST. Works from any public page at the site root.
(function () {
  if (document.getElementById('site-chatbot')) return;

  var wrap = document.createElement('div');
  wrap.id = 'site-chatbot';
  wrap.innerHTML =
    '<button type="button" class="chatbot-bubble" aria-label="Open chat assistant" aria-expanded="false">💬</button>' +
    '<div class="chatbot-panel" hidden>' +
      '<div class="chatbot-head"><strong>Site Assistant</strong><span>Ask about services, price or contact</span>' +
      '<button type="button" class="chatbot-close" aria-label="Close chat">✕</button></div>' +
      '<div class="chatbot-messages" aria-live="polite"></div>' +
      '<div class="chatbot-quick">' +
        '<button type="button" data-q="What services do you offer?">Services</button>' +
        '<button type="button" data-q="What is the price?">Price</button>' +
        '<button type="button" data-q="How do I contact you?">Contact</button>' +
        '<button type="button" data-q="How do I register in the User Panel?">Register</button>' +
      '</div>' +
      '<form class="chatbot-form">' +
        '<input type="text" name="question" placeholder="Type your question…" autocomplete="off" required>' +
        '<button type="submit">Send</button>' +
      '</form>' +
      '<details class="chatbot-leave"><summary>Leave a message (name + email)</summary>' +
      '<form class="chatbot-leave-form">' +
        '<input type="text" name="name" placeholder="Your name" required>' +
        '<input type="email" name="email" placeholder="Your email" required>' +
        '<textarea name="message" placeholder="Your message" rows="3" required></textarea>' +
        '<button type="submit">Save as inquiry</button>' +
      '</form></details>' +
    '</div>';
  document.body.appendChild(wrap);

  var bubble = wrap.querySelector('.chatbot-bubble');
  var panel = wrap.querySelector('.chatbot-panel');
  var closeBtn = wrap.querySelector('.chatbot-close');
  var messages = wrap.querySelector('.chatbot-messages');
  var greeted = false;

  function addMsg(html, who) {
    var div = document.createElement('div');
    div.className = 'chatbot-msg chatbot-' + who;
    div.innerHTML = html;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
  }
  function openPanel() {
    panel.hidden = false;
    bubble.setAttribute('aria-expanded', 'true');
    if (!greeted) {
      greeted = true;
      addMsg('Hello! I can help with services, price, contact and the User Panel. Tap a quick button or type a question.', 'bot');
    }
  }
  function closePanel() { panel.hidden = true; bubble.setAttribute('aria-expanded', 'false'); }
  bubble.addEventListener('click', function () { panel.hidden ? openPanel() : closePanel(); });
  closeBtn.addEventListener('click', closePanel);

  function ask(question) {
    addMsg(question.replace(/</g, '&lt;'), 'user');
    var body = new URLSearchParams({ action: 'ask', question: question });
    fetch('backend/assistant.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
      .then(function (r) { return r.json(); })
      .then(function (data) { addMsg(data.answer || 'Sorry, something went wrong. Please try the Contact form.', 'bot'); })
      .catch(function () { addMsg('Sorry, I could not reach the assistant right now. Please use the <a href="index.php#contact">Contact form</a>.', 'bot'); });
  }

  wrap.querySelectorAll('.chatbot-quick button').forEach(function (btn) {
    btn.addEventListener('click', function () { ask(btn.getAttribute('data-q')); });
  });
  wrap.querySelector('.chatbot-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var input = this.querySelector('input[name="question"]');
    var q = input.value.trim();
    if (!q) return;
    input.value = '';
    ask(q);
  });
  wrap.querySelector('.chatbot-leave-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = this;
    var body = new URLSearchParams({
      action: 'save_inquiry',
      name: form.name.value.trim(),
      email: form.email.value.trim(),
      message: form.message.value.trim()
    });
    fetch('backend/assistant.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
      .then(function (r) { return r.json(); })
      .then(function (data) { addMsg(data.ok ? data.answer : (data.error || 'Could not save. Please use the Contact form.'), 'bot'); if (data.ok) form.reset(); })
      .catch(function () { addMsg('Sorry, I could not save that right now. Please use the <a href="index.php#contact">Contact form</a>.', 'bot'); });
  });
})();
