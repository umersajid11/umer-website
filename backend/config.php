<?php
// Database configuration — PLACEHOLDERS ONLY.
// No real credentials are stored in this file.
// Set these values via environment variables on your server / in XAMPP,
// or edit them locally AFTER deployment. Never commit real passwords to GitHub.
return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'name'     => getenv('DB_NAME') ?: 'umer_website',
    'user'     => getenv('DB_USER') ?: 'YOUR_DB_USERNAME',
    'password' => getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD',
    'charset'  => 'utf8mb4',
];
