<?php
// Simple rule-based chatbot — NO external API, NO API key, NO invented prices.
// Keyword matching only, case-insensitive. Answers are pre-written and link
// to real pages. If the database is not connected, nothing crashes: the
// inquiry-save step is simply skipped and the visitor is told to use the
// Contact form instead.

require_once __DIR__ . '/services.php';
require_once __DIR__ . '/database.php';

// ---------- answer engine (pure function, easy to test) ----------

function chatbot_detect_service($text) {
    $map = [
        'website-design' => ['website design', 'website', 'web design', 'web site'],
        'website-fixing' => ['website fixing', 'fixing', 'fix my website', 'broken website', 'maintenance'],
        'video-editing' => ['video editing', 'video edit', 'edit video', 'reel', 'short'],
        'voiceover-scripts' => ['voiceover', 'voice over', 'script', 'narration'],
        'social-media-management' => ['social media', 'facebook', 'instagram', 'youtube management'],
        'logo-thumbnail' => ['logo', 'thumbnail'],
        'seo-adsense' => ['seo', 'adsense', 'ad sense', 'search'],
        'content-writing' => ['content writing', 'writing', 'caption', 'article'],
    ];
    foreach ($map as $slug => $words) {
        foreach ($words as $w) {
            if (strpos($text, $w) !== false) return $slug;
        }
    }
    return null;
}

function chatbot_services_list($base) {
    $out = 'I offer these services — tap any one for details: ';
    $parts = [];
    foreach (service_catalogue() as $slug => $s) {
        $parts[] = '<a href="' . $base . 'service.php?slug=' . urlencode($slug) . '">' . htmlspecialchars($s['title']) . '</a>';
    }
    $out .= implode(' · ', $parts);
    $out .= '. See them all on the <a href="' . $base . 'services.php">Services page</a>.';
    return $out;
}

function chatbot_answer($question, $base = '') {
    $text = strtolower(trim((string) $question));
    if ($text === '') {
        return 'Please type a question — for example "What services do you offer?", "What is the price?" or "How do I contact you?"';
    }
    $service = chatbot_detect_service($text);
    $serviceTitle = '';
    if ($service) {
        $found = find_service($service);
        $serviceTitle = $found['title'] ?? $service;
    }

    // Price: NEVER invent a price. Price always depends on project scope.
    if (strpos($text, 'price') !== false || strpos($text, 'cost') !== false
        || strpos($text, 'charge') !== false || strpos($text, 'rate') !== false
        || strpos($text, 'fee') !== false || strpos($text, 'kitne') !== false) {
        $what = $serviceTitle ? ' for ' . htmlspecialchars($serviceTitle) : '';
        return 'There is no fixed price listed' . $what . ' — the price depends on your project scope (what you need, how big it is, and how soon you need it). '
            . 'Please send your project detail through the <a href="' . $base . 'index.php#contact">Contact form</a> '
            . 'or from the <a href="' . $base . 'user/login.php">User Panel</a>, and you will get a proper quote before any work starts. No work starts without your approval of the price.';
    }

    if ($service) {
        $found = find_service($service);
        return htmlspecialchars($found['title']) . ': ' . htmlspecialchars($found['short'])
            . ' <a href="' . $base . 'service.php?slug=' . urlencode($service) . '">View full details →</a>'
            . ' Or ask about the price, or send your detail via the <a href="' . $base . 'index.php#contact">Contact form</a>.';
    }

    if (strpos($text, 'service') !== false || strpos($text, 'what do you do') !== false
        || strpos($text, 'kya karte') !== false || strpos($text, 'offer') !== false) {
        return chatbot_services_list($base);
    }

    if (strpos($text, 'contact') !== false || strpos($text, 'message') !== false
        || strpos($text, 'email') !== false || strpos($text, 'reach') !== false
        || strpos($text, 'rabta') !== false) {
        return 'You can reach Umer through the <a href="' . $base . 'index.php#contact">Contact form</a> on the home page — just your name, email, service and message. '
            . 'If you register in the <a href="' . $base . 'user/login.php">User Panel</a> with the same email, admin replies will appear in your portal.';
    }

    if (strpos($text, 'register') !== false || strpos($text, 'login') !== false
        || strpos($text, 'user panel') !== false || strpos($text, 'account') !== false
        || strpos($text, 'track') !== false || strpos($text, 'status') !== false
        || strpos($text, 'order') !== false || strpos($text, 'sign up') !== false) {
        return 'The <a href="' . $base . 'user/login.php">User Panel</a> is for registered clients: after you '
            . '<a href="' . $base . 'user/register.php">register</a> and log in, you can send requests, see their status, '
            . 'and chat with the admin in one place with full history. The public Contact form needs no login — it is just for a first inquiry.';
    }

    if (strpos($text, 'about') !== false || strpos($text, 'who are you') !== false
        || strpos($text, 'umer') !== false || strpos($text, 'experience') !== false
        || strpos($text, 'qualification') !== false) {
        return 'Umer Sajid — MS in Software Engineering, with a one-year diploma from China. He builds websites, edits videos, '
            . 'and does voiceover and scripts, social media, logos, SEO/AdSense setup and content writing. '
            . 'Read the full story on the <a href="' . $base . 'about.php">About page</a>.';
    }

    if (strpos($text, 'blog') !== false || strpos($text, 'post') !== false || strpos($text, 'article') !== false) {
        return 'The <a href="' . $base . 'blog.php">Blog</a> has daily AI and tech posts. Have a look — and if a post raises a question, send it through the <a href="' . $base . 'index.php#contact">Contact form</a>.';
    }

    if (strpos($text, 'hour') !== false || strpos($text, 'open') !== false || strpos($text, 'time') !== false
        || strpos($text, 'location') !== false || strpos($text, 'where') !== false || strpos($text, 'address') !== false) {
        // Deliberately no hours or address are invented here.
        return 'No fixed working hours or office address are published on this site. The best way is to send a message through the '
            . '<a href="' . $base . 'index.php#contact">Contact form</a> — the admin will reply there, or in your User Panel if you registered with the same email.';
    }

    if (strpos($text, 'hello') !== false || strpos($text, 'hi') !== false || strpos($text, 'salam') !== false
        || strpos($text, 'hey') !== false || strpos($text, 'help') !== false) {
        return 'Hello! I am a simple assistant for this site. Ask me about <a href="' . $base . 'services.php">Services</a>, '
            . 'price, <a href="' . $base . 'index.php#contact">Contact</a>, or the <a href="' . $base . 'user/login.php">User Panel</a> — '
            . 'or tap one of the quick buttons below.';
    }

    // Fallback: never guess, never invent — point to a human.
    return 'Sorry, I did not understand that — I am a simple assistant and only know about this site\'s services, price process, contact and User Panel. '
        . 'Please send your question through the <a href="' . $base . 'index.php#contact">Contact form</a>, '
        . 'or register in the <a href="' . $base . 'user/login.php">User Panel</a> to chat with the admin directly. '
        . 'You can also leave your name and email in the "Leave a message" form below and it will be saved as an inquiry.';
}

// ---------- AJAX endpoint (POST only) ----------

if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = trim($_POST['action'] ?? 'ask');
    if ($action === 'save_inquiry') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            echo json_encode(['ok' => false, 'error' => 'Please enter your name, a valid email address, and a message.']);
            exit;
        }
        $db = get_db_connection();
        if (!$db) {
            echo json_encode(['ok' => false, 'error' => 'Database not connected yet — please use the Contact form on the home page instead.']);
            exit;
        }
        try {
            $stmt = $db->prepare("INSERT INTO inquiries (name, email, service, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, '', $message]);
            echo json_encode(['ok' => true, 'answer' => 'Thank you — your message was saved as an inquiry. The admin will reply, and if you register in the User Panel with this same email, the reply will appear in your portal.']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Something went wrong saving your message. Please try the Contact form.']);
        }
        exit;
    }
    // Default action: ask
    $q = trim($_POST['question'] ?? '');
    echo json_encode(['ok' => true, 'answer' => chatbot_answer($q, '')]);
    exit;
}
