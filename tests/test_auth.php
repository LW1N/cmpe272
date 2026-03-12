<?php
declare(strict_types=1);

function run_auth_tests(TestRunner $t): void
{
    $t->section('Authentication');

    require_once PROJECT_ROOT . '/includes/auth.php';

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $t->run('is_logged_in() returns false for empty session', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(is_logged_in());
    });

    $t->run('is_admin() returns false for empty session', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(is_admin());
    });

    $t->run('current_userid() returns empty string for empty session', function () use ($t) {
        $_SESSION = [];
        $t->assertEqual('', current_userid());
    });

    $t->run('attempt_login() fails with empty userid', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(attempt_login('', 'password'));
        $t->assertFalse(is_logged_in());
    });

    $t->run('attempt_login() fails with empty password', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(attempt_login('admin', ''));
        $t->assertFalse(is_logged_in());
    });

    $t->run('attempt_login() fails with wrong password', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(attempt_login('admin', 'wrongpassword'));
        $t->assertFalse(is_logged_in());
    });

    $t->run('attempt_login() fails with unknown user', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(attempt_login('nonexistent', 'password123'));
        $t->assertFalse(is_logged_in());
    });

    $t->run('whitespace-only userid is rejected', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(attempt_login('   ', 'Adminpassword'));
        $t->assertFalse(is_logged_in());
    });

    $t->run('admin login succeeds with correct credentials', function () use ($t) {
        $_SESSION = [];
        $t->assertTrue(attempt_login('admin', 'Adminpassword'));
        $t->assertTrue(is_logged_in());
        $t->assertTrue(is_admin());
        $t->assertEqual('admin', current_userid());
    });

    $t->run('standard user login succeeds with correct credentials', function () use ($t) {
        $_SESSION = [];
        $t->assertTrue(attempt_login('user', 'user123'));
        $t->assertTrue(is_logged_in());
        $t->assertFalse(is_admin());
        $t->assertEqual('user', current_userid());
    });

    $t->run('logout() clears session state', function () use ($t) {
        $_SESSION = [];
        attempt_login('admin', 'Adminpassword');
        $t->assertTrue(is_logged_in());

        logout();

        $t->assertFalse(is_logged_in());
        $t->assertFalse(is_admin());
        $t->assertEqual('', current_userid());

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    });

    // --- CSRF token ---

    $t->run('generate_csrf_token() returns a non-empty string', function () use ($t) {
        $_SESSION = [];
        $token = generate_csrf_token();
        $t->assertNotEmpty($token);
        $t->assertTrue(strlen($token) === 64, 'CSRF token should be 64 hex chars');
    });

    $t->run('generate_csrf_token() returns the same token on repeat calls', function () use ($t) {
        $_SESSION = [];
        $token1 = generate_csrf_token();
        $token2 = generate_csrf_token();
        $t->assertEqual($token1, $token2);
    });

    $t->run('verify_csrf_token() returns true for valid token', function () use ($t) {
        $_SESSION = [];
        $token = generate_csrf_token();
        $t->assertTrue(verify_csrf_token($token));
    });

    $t->run('verify_csrf_token() returns false for incorrect token', function () use ($t) {
        $_SESSION = [];
        generate_csrf_token();
        $t->assertFalse(verify_csrf_token('wrong_token'));
    });

    $t->run('verify_csrf_token() returns false when no token in session', function () use ($t) {
        $_SESSION = [];
        $t->assertFalse(verify_csrf_token('any_token'));
    });

    // --- Session timeout ---

    $t->run('attempt_login() records last_activity in session', function () use ($t) {
        $_SESSION = [];
        $before = time();
        attempt_login('admin', 'Adminpassword');
        $t->assertTrue(isset($_SESSION['last_activity']), 'last_activity should be set after login');
        $t->assertTrue((int)$_SESSION['last_activity'] >= $before, 'last_activity should be >= login time');
        $_SESSION = [];
    });

    $t->run('is_logged_in() is unaffected by last_activity timestamp', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user', 'last_activity' => time()];
        $t->assertTrue(is_logged_in());
        $_SESSION = [];
    });
}
