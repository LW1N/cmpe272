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
        $t->assertFalse(attempt_login('   ', 'admin123'));
        $t->assertFalse(is_logged_in());
    });

    $t->run('admin login succeeds with correct credentials', function () use ($t) {
        $_SESSION = [];
        $t->assertTrue(attempt_login('admin', 'admin123'));
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
        attempt_login('admin', 'admin123');
        $t->assertTrue(is_logged_in());

        logout();

        $t->assertFalse(is_logged_in());
        $t->assertFalse(is_admin());
        $t->assertEqual('', current_userid());

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    });
}
