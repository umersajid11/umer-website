<?php
// Shared session authentication for admin and user panels.
// No credentials are stored here. Passwords are only ever checked with
// password_verify() against password_hash() values in the database.

function start_app_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }
}

function is_admin_logged_in() {
    start_app_session();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email']);
}

function is_user_logged_in() {
    start_app_session();
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_email']);
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
    // Admins created by setup.php must change the placeholder password
    // before they can use the dashboard at all.
    if (!empty($_SESSION['admin_must_change_password'])) {
        $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($page !== 'change-password.php') {
            header('Location: change-password.php');
            exit;
        }
    }
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function attempt_admin_login($db, $email, $password) {
    if (!$db) return false;
    $stmt = $db->prepare("SELECT id, name, email, password_hash, must_change_password FROM admins WHERE email = ? LIMIT 1");
    $stmt->execute([trim($email)]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        start_app_session();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_must_change_password'] = !empty($admin['must_change_password']);
        return true;
    }
    return false;
}

function attempt_user_login($db, $email, $password) {
    if (!$db) return false;
    $stmt = $db->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        start_app_session();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    return false;
}

function app_logout($redirect) {
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . $redirect);
    exit;
}
