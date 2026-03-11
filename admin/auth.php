<?php
declare(strict_types=1);

/**
 * Admin authentication helpers.
 * Uses PHP sessions; call require_admin() at the top of any protected admin page.
 */

if (session_status() === PHP_SESSION_NONE) {
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
