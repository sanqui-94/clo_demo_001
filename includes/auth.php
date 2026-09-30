<?php
// Admin session handling: login, logout, inactivity timeout, and CSRF protection.
// Every admin page includes this file and calls require_login() (except login.php).

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

start_session();

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('clinic_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/**
 * Checks the credentials and, if they match, logs the admin in.
 */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // New session ID on login prevents session fixation.
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['last_activity'] = time();

    return true;
}

function logout(): void
{
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    session_destroy();
}

function is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Sends visitors who aren't logged in (or whose session timed out) to the login page.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }

    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        logout();
        start_session();
        flash('error', t('login.session_expired'));
        redirect('login.php');
    }

    $_SESSION['last_activity'] = time();
}

function current_admin_username(): string
{
    return $_SESSION['admin_username'] ?? '';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Hidden input to put inside every admin <form method="post">.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Stops the request if a POST didn't come from one of our own forms.
 */
function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        exit(e(t('admin.invalid_form')));
    }
}
