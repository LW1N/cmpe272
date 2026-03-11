<?php
declare(strict_types=1);

/**
 * Shared authentication helpers.
 *
 * Supports:
 * - Admin login (config-based, see admin/config.php)
 * - Standard user login (demo config, see includes/auth_config.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => (int) ($params['lifetime'] ?? 0),
        'path' => (string) ($params['path'] ?? '/'),
        'domain' => (string) ($params['domain'] ?? ''),
        'secure' => $isHttps ? true : (bool) ($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

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

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /login');
        exit;
    }
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
        return true;
    }

    // Standard users (demo)
    $standardUsers = STANDARD_USERS;
    if (isset($standardUsers[$userid]) && password_verify($password, $standardUsers[$userid])) {
        session_regenerate_id(true);
        $_SESSION['is_logged_in'] = true;
        $_SESSION['is_admin'] = false;
        $_SESSION['userid'] = $userid;
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

