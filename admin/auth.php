<?php
declare(strict_types=1);

/**
 * Admin authentication helpers.
 * Uses PHP sessions; call require_admin() at the top of any protected admin page.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden the admin session cookie without requiring app-wide config.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // Preserve default cookie params but force secure when HTTPS is in use.
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

require_once __DIR__ . '/config.php';

/**
 * Redirect to login if not authenticated as admin.
 * Call at the top of protected admin pages (e.g. users.php).
 */
function require_admin(): void
{
    if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: /admin/login.php');
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
    if ($userid !== ADMIN_USER) {
        return false;
    }
    if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_userid'] = $userid;
    return true;
}
