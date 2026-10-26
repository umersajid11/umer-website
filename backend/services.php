<?php
// Shared service catalogue — used by the front page, service detail
// pages and both panels, so the list is identical everywhere.
// The database services table is the source when connected; this
// catalogue is the beautiful fallback and the source of detail text.

function service_catalogue() {
    return [
        'website-design' => [
            'title' => 'Website Design',
            'icon' => '🌐',
            'short' => 'Modern, fast, mobile-friendly personal and business websites, built folder-wise.',
            'detail' => 'A complete website designed around you — clean layout, easy navigation and a look your visitors will love, on phone and on desktop.',
            'included' => ['Home, About, Services and Contact pages', 'Mobile-friendly, modern design', 'Contact form connected to your inbox/panel', 'Basic on-page SEO setup'],
            'for' => 'Businesses, creators, freelancers and anyone who wants a professional online presence.',
        ],
        'website-fixing' => [
            'title' => 'Website Fixing & Maintenance',
            'icon' => '🛠️',
            'short' => 'Fix errors, broken pages and slow sites — and keep an existing site running smoothly.',
            'detail' => 'If your site is broken, slow or showing errors, I find the problem, fix it properly and make sure it keeps working afterwards.',
            'included' => ['Error and broken-page fixes', 'Speed and layout improvements', 'Content and link updates', 'Regular check-ups on request'],
            'for' => 'Owners of an existing website that needs repair, care or small updates.',
        ],
        'video-editing' => [
            'title' => 'Video Editing',
            'icon' => '🎬',
            'short' => 'Short-form and cinematic edits from your own clips — clean cuts, text and music.',
            'detail' => 'Your raw clips turned into a finished, shareable video: smooth cuts, readable text, fitting music and a strong opening.',
            'included' => ['Shorts, Reels and cinematic edits', 'Text, transitions and sound', 'Thumbnail-ready final cut', 'Family-friendly, positive style'],
            'for' => 'Creators, pages and small businesses posting on Facebook, Instagram, YouTube or TikTok.',
        ],
        'voiceover-scripts' => [
            'title' => 'Voiceover & Scripts',
            'icon' => '🎙️',
            'short' => 'Natural voiceover in English and Urdu, plus scripts, captions and story ideas.',
            'detail' => 'A warm, clear voiceover matched to your video, and a script that says your message simply — no jargon, no rushing.',
            'included' => ['Urdu and English voiceover', 'Video scripts and captions', 'Pace matched to your video', 'Revisions on the main message'],
            'for' => 'Creators who want their videos to sound professional and feel emotional.',
        ],
        'social-media-management' => [
            'title' => 'Social Media Management',
            'icon' => '📱',
            'short' => 'Posts, captions, hashtags and posting plans for Facebook, Instagram and YouTube.',
            'detail' => 'Consistent, wholesome content for your page — planned, captioned and ready to post, so you stay visible without daily stress.',
            'included' => ['Post ideas and captions', 'Hashtags and posting plan', 'Page and profile guidance', 'Growth-friendly, family-safe content'],
            'for' => 'Pages, creators and small brands that want steady, positive growth.',
        ],
        'logo-thumbnail' => [
            'title' => 'Logo & Thumbnail Design',
            'icon' => '🎨',
            'short' => 'Clean logos and eye-catching thumbnails that make your page instantly recognisable.',
            'detail' => 'Simple, memorable design — a logo that fits your name and thumbnails that make people want to click, without looking loud or cheap.',
            'included' => ['Logo design in common sizes', 'Video thumbnails and covers', 'Colour and font consistency', 'Files ready for social media'],
            'for' => 'New pages, channels and businesses building their brand from scratch.',
        ],
        'seo-adsense' => [
            'title' => 'SEO & AdSense Setup',
            'icon' => '🔍',
            'short' => 'Make your site search-friendly and ready for AdSense — setup only, approval is never guaranteed.',
            'detail' => 'Your site prepared the right way: clear titles, descriptions and structure for search engines, and AdSense code placed after your approval.',
            'included' => ['On-page SEO titles and descriptions', 'Site structure and speed basics', 'AdSense-ready layout and code placement', 'Honest guidance on what AdSense needs'],
            'for' => 'Site owners who want search traffic and a proper, policy-safe AdSense setup.',
        ],
        'content-writing' => [
            'title' => 'Content Writing',
            'icon' => '✍️',
            'short' => 'Clear, positive writing for websites, pages and videos — in simple, readable language.',
            'detail' => 'Words that sound like you, only clearer: about pages, service descriptions, captions and video text that people actually read.',
            'included' => ['Website and about-page text', 'Captions and post copy', 'Video on-screen text ideas', 'Simple, human tone — no filler'],
            'for' => 'Anyone who knows what they offer but struggles to write it well.',
        ],
    ];
}

function find_service($key) {
    $catalogue = service_catalogue();
    if (isset($catalogue[$key])) return array_merge(['slug' => $key], $catalogue[$key]);
    foreach ($catalogue as $slug => $s) {
        if (strcasecmp($s['title'], $key) === 0 || (string) $key === $slug) {
            return array_merge(['slug' => $slug], $s);
        }
    }
    return null;
}
