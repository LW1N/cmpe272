<?php
declare(strict_types=1);

/**
 * Shared authentication helpers.
 *
 * Supports:
 * - Admin login (config-based, see admin/config.php)
 * - Standard user login (demo config, see includes/auth_config.php)
 */

/** Idle session lifetime in seconds (30 minutes). */
define('SESSION_TIMEOUT', 1800);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/auth_config.php';

function is_logged_in(): bool
{
    return !empty($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

function is_admin(): bool
{
    return !empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function current_userid(): string
{
    return (string) ($_SESSION['userid'] ?? '');
}

/** Generate (or return existing) a per-session CSRF token. */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token submitted with a form.
 * Uses hash_equals() to prevent timing attacks.
 */
function verify_csrf_token(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check whether the current session has been idle beyond SESSION_TIMEOUT.
 * If so, destroys the session and redirects to the login page.
 * Otherwise, refreshes the last-activity timestamp.
 */
function check_session_timeout(): void
{
    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logout();
        header('Location: /login?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
    check_session_timeout();
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /login');
        exit;
    }
    check_session_timeout();
}

/**
 * Attempt login with userid and password.
 * Returns true on success (session is updated and id regenerated), false otherwise.
 */
function attempt_login(string $userid, string $password): bool
{
    $userid = trim($userid);
    if ($userid === '' || $password === '') {
        return false;
    }

    // Admin
    if ($userid === ADMIN_USER && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['is_logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['userid'] = $userid;
        $_SESSION['last_activity'] = time();
        return true;
    }

    // Standard users (demo)
    $standardUsers = STANDARD_USERS;
    if (isset($standardUsers[$userid]) && password_verify($password, $standardUsers[$userid])) {
        session_regenerate_id(true);
        $_SESSION['is_logged_in'] = true;
        $_SESSION['is_admin'] = false;
        $_SESSION['userid'] = $userid;
        $_SESSION['last_activity'] = time();
        return true;
    }

    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }
    session_destroy();
}

