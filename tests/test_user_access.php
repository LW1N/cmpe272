<?php
declare(strict_types=1);

function run_user_access_tests(TestRunner $t): void
{
    $t->section('User Access & Display');

    require_once PROJECT_ROOT . '/includes/auth.php';

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // --- Username display in nav ---

    $t->run('logged-in admin sees their username in the nav', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => true, 'userid' => 'admin'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('class="nav-user"', $output, 'Nav should contain the nav-user element');
        $t->assertContains('admin', $output, 'Nav should display admin username');
        $_SESSION = [];
    });

    $t->run('logged-in standard user sees their username in the nav', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('class="nav-user"', $output, 'Nav should contain the nav-user element');
        $t->assertContains('>user<', $output, 'Nav should display standard username');
        $_SESSION = [];
    });

    $t->run('logged-out visitor does not see username in the nav', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertNotContains('class="nav-user"', $output, 'Nav should NOT contain nav-user element when logged out');
    });

    // --- Admin-only "Users" link ---

    $t->run('admin sees Users link in nav', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => true, 'userid' => 'admin'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('href="/admin/users.php"', $output, 'Nav should contain Users link for admin');
        $t->assertContains('Users', $output, 'Nav should display Users label for admin');
        $_SESSION = [];
    });

    $t->run('standard user does NOT see Users link in nav', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertNotContains('href="/admin/users.php"', $output, 'Nav should NOT contain Users link for standard user');
        $_SESSION = [];
    });

    $t->run('logged-out visitor does NOT see Users link in nav', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertNotContains('href="/admin/users.php"', $output, 'Nav should NOT contain Users link when logged out');
    });

    // --- Log in / Log out link toggling ---

    $t->run('logged-in user sees Log out, not Log in', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('href="/logout"', $output, 'Nav should contain Logout link when logged in');
        $t->assertNotContains('href="/login"', $output, 'Nav should NOT contain Login link when logged in');
        $_SESSION = [];
    });

    $t->run('logged-out visitor sees Log in, not Log out', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('href="/login"', $output, 'Nav should contain Login link when logged out');
        $t->assertNotContains('href="/logout"', $output, 'Nav should NOT contain Logout link when logged out');
    });

    // --- Page title encoding ---

    $t->run('products page title does not double-encode ampersand', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/products.php';
        $output = ob_get_clean();

        $t->assertContains('<title>Products &amp; Services', $output,
            'Title should contain properly encoded ampersand');
        $t->assertNotContains('<title>Products &amp;amp; Services', $output,
            'Title should NOT double-encode the ampersand');
    });

    // --- Username display with aria-label accessibility ---

    $t->run('username span has proper aria-label for accessibility', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('aria-label="Logged in as user"', $output,
            'Username element should have descriptive aria-label');
        $_SESSION = [];
    });

    // --- Username shows on multiple pages ---

    $t->run('username shows on about page when logged in', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/about.php';
        $output = ob_get_clean();

        $t->assertContains('class="nav-user"', $output, 'Nav-user should appear on about page');
        $_SESSION = [];
    });

    $t->run('username shows on news page when logged in', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => false, 'userid' => 'user'];
        ob_start();
        require PROJECT_ROOT . '/news.php';
        $output = ob_get_clean();

        $t->assertContains('class="nav-user"', $output, 'Nav-user should appear on news page');
        $_SESSION = [];
    });
}
