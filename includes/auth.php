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
define('LOGIN_MAX_ATTEMPTS', max(1, (int) (getenv('LOGIN_MAX_ATTEMPTS') ?: 5)));
define('LOGIN_ATTEMPT_WINDOW', max(60, (int) (getenv('LOGIN_ATTEMPT_WINDOW') ?: 300)));
define('LOGIN_LOCKOUT_SECONDS', max(60, (int) (getenv('LOGIN_LOCKOUT_SECONDS') ?: 900)));

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

if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline';");
}

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/auth_config.php';

/**
 * @return string[]
 */
function auth_configuration_errors(): array
{
    $errors = [];

    if (ADMIN_PASSWORD_HASH === '') {
        $errors[] = 'ADMIN_PASSWORD_HASH is not configured.';
    }

    return $errors;
}

function auth_is_configured(): bool
{
    return auth_configuration_errors() === [];
}

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

function get_client_ip(): string
{
    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwardedFor !== '') {
        $parts = array_map('trim', explode(',', $forwardedFor));
        foreach ($parts as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP) !== false) {
                return $part;
            }
        }
    }

    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return filter_var($remoteAddr, FILTER_VALIDATE_IP) !== false ? $remoteAddr : 'unknown';
}

function get_login_throttle_key(string $userid): string
{
    return strtolower(trim($userid)) . '|' . get_client_ip();
}

function get_login_throttle_file(): string
{
    return sys_get_temp_dir() . '/pass_play_login_throttle.json';
}

/**
 * @param array<mixed> $decoded
 * @return array<string, array{count: int, first_attempt_at: int, locked_until: int}>
 */
function normalize_login_throttle_store(array $decoded): array
{
    $clean = [];

    foreach ($decoded as $key => $entry) {
        if (!is_string($key) || !is_array($entry)) {
            continue;
        }

        $clean[$key] = [
            'count' => max(0, (int) ($entry['count'] ?? 0)),
            'first_attempt_at' => max(0, (int) ($entry['first_attempt_at'] ?? 0)),
            'locked_until' => max(0, (int) ($entry['locked_until'] ?? 0)),
        ];
    }

    return $clean;
}

/**
 * @return array<string, array{count: int, first_attempt_at: int, locked_until: int}>
 */
function read_login_throttle_store(): array
{
    $file = get_login_throttle_file();
    if (!is_file($file)) {
        return [];
    }

    $raw = file_get_contents($file);
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? normalize_login_throttle_store($decoded) : [];
}

/**
 * @param callable(array<string, array{count: int, first_attempt_at: int, locked_until: int}>): array<string, array{count: int, first_attempt_at: int, locked_until: int}> $callback
 */
function update_login_throttle_store(callable $callback): void
{
    $file = get_login_throttle_file();
    $handle = fopen($file, 'c+');

    if ($handle === false) {
        return;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return;
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        $store = is_array($decoded) ? normalize_login_throttle_store($decoded) : [];
        $store = $callback($store);

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($store, JSON_THROW_ON_ERROR));
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function clear_expired_login_throttle_entries(): void
{
    update_login_throttle_store(static function (array $store): array {
        $now = time();

        foreach ($store as $key => $entry) {
            $windowExpired = $entry['first_attempt_at'] > 0 && ($now - $entry['first_attempt_at']) > LOGIN_ATTEMPT_WINDOW;
            $lockExpired = $entry['locked_until'] > 0 && $entry['locked_until'] <= $now;

            if (($windowExpired && $entry['locked_until'] === 0) || ($windowExpired && $lockExpired) || ($entry['count'] === 0 && $lockExpired)) {
                unset($store[$key]);
            }
        }

        return $store;
    });
}

function is_login_rate_limited(string $userid): bool
{
    clear_expired_login_throttle_entries();
    $entry = read_login_throttle_store()[get_login_throttle_key($userid)] ?? null;

    return is_array($entry) && ($entry['locked_until'] ?? 0) > time();
}

function record_failed_login_attempt(string $userid): void
{
    $key = get_login_throttle_key($userid);

    update_login_throttle_store(static function (array $store) use ($key): array {
        $now = time();
        $entry = $store[$key] ?? ['count' => 0, 'first_attempt_at' => $now, 'locked_until' => 0];

        if (($now - $entry['first_attempt_at']) > LOGIN_ATTEMPT_WINDOW) {
            $entry = ['count' => 0, 'first_attempt_at' => $now, 'locked_until' => 0];
        }

        $entry['count']++;
        if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
            $entry['locked_until'] = $now + LOGIN_LOCKOUT_SECONDS;
        }

        $store[$key] = $entry;

        return $store;
    });
}

function clear_failed_login_attempts(string $userid): void
{
    $key = get_login_throttle_key($userid);

    update_login_throttle_store(static function (array $store) use ($key): array {
        unset($store[$key]);

        return $store;
    });
}

function reset_login_throttle_store(): void
{
    $file = get_login_throttle_file();

    if (is_file($file)) {
        unlink($file);
    }
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

    if (!auth_is_configured() || is_login_rate_limited($userid)) {
        return false;
    }

    // Admin
    if ($userid === ADMIN_USER && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['is_logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['userid'] = $userid;
        $_SESSION['last_activity'] = time();
        clear_failed_login_attempts($userid);
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
        clear_failed_login_attempts($userid);
        return true;
    }

    record_failed_login_attempt($userid);
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
