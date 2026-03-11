<?php
declare(strict_types=1);

function run_page_rendering_tests(TestRunner $t): void
{
    $t->section('Page Rendering');

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];

    $phpErrorPatterns = ['Fatal error:', 'Warning:', 'Parse error:', 'Deprecated:', 'Notice:'];

    $pages = [
        'index.php'    => ['title' => 'Home',     'h1' => 'Pass & Play'],
        'about.php'    => ['title' => 'About',    'h1' => 'About Pass & Play'],
        'products.php' => ['title' => 'Products', 'h1' => 'Products &amp; Services'],
        'news.php'     => ['title' => 'News',     'h1' => 'News'],
        'contacts.php' => ['title' => 'Contacts', 'h1' => 'Contacts'],
    ];

    foreach ($pages as $file => $expect) {
        $t->run("{$file} renders without errors", function () use ($t, $file, $expect, $phpErrorPatterns) {
            $path = PROJECT_ROOT . '/' . $file;
            $t->assertTrue(file_exists($path), "{$file} should exist");

            try {
                ob_start();
                require $path;
                $output = ob_get_clean();
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                throw new TestFailure("{$file} threw: " . $e->getMessage());
            }

            $t->assertNotEmpty($output, "{$file} output should not be empty");
            $t->assertContains('<!DOCTYPE html>', $output, "{$file} should contain DOCTYPE");
            $t->assertContains('</html>', $output, "{$file} should contain closing html tag");
            $t->assertContains($expect['h1'], $output, "{$file} should contain expected heading");

            foreach ($phpErrorPatterns as $pattern) {
                $t->assertNotContains($pattern, $output, "{$file} should not contain '{$pattern}'");
            }
        });
    }

    $t->run('nav contains expected links when logged out', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('href="/"', $output, 'Nav should contain Home link');
        $t->assertContains('href="/about"', $output, 'Nav should contain About link');
        $t->assertContains('href="/products"', $output, 'Nav should contain Products link');
        $t->assertContains('href="/news"', $output, 'Nav should contain News link');
        $t->assertContains('href="/contacts"', $output, 'Nav should contain Contacts link');
        $t->assertContains('href="/login"', $output, 'Nav should contain Login link when logged out');
        $t->assertNotContains('href="/admin/users.php"', $output, 'Nav should NOT contain Users link when logged out');
    });

    $t->run('nav shows Users link and username for admin', function () use ($t) {
        $_SESSION = ['is_logged_in' => true, 'is_admin' => true, 'userid' => 'admin'];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $t->assertContains('href="/admin/users.php"', $output, 'Nav should contain Users link for admin');
        $t->assertContains('class="nav-user"', $output, 'Nav should contain username for admin');
        $t->assertNotContains('class="nav-login"', $output, 'Nav should NOT contain Login link when logged in');

        $_SESSION = [];
    });

    $t->run('footer contains copyright and links', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/index.php';
        $output = ob_get_clean();

        $year = date('Y');
        $t->assertContains("&copy; {$year} Pass & Play", $output, 'Footer should contain copyright with current year');
        $t->assertContains('href="/contacts"', $output, 'Footer should contain contact link');
        $t->assertContains('href="/demo.php"', $output, 'Footer should contain demo link');
    });

    $t->run('login page renders with CSRF token input', function () use ($t, $phpErrorPatterns) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/login.php';
        $output = ob_get_clean();

        $t->assertContains('<!DOCTYPE html>', $output, 'login.php should contain DOCTYPE');
        $t->assertContains('name="csrf_token"', $output, 'login.php should include a CSRF token input');
        $t->assertContains('type="hidden"', $output, 'CSRF token should be a hidden input');

        foreach ($phpErrorPatterns as $pattern) {
            $t->assertNotContains($pattern, $output, "login.php should not contain '{$pattern}'");
        }
    });

    $t->run('contacts page displays contact table', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/contacts.php';
        $output = ob_get_clean();

        $t->assertContains('<table', $output, 'Should contain a table');
        $t->assertContains('support@passandplay.com', $output, 'Should display support email');
        $t->assertContains('sales@passandplay.com', $output, 'Should display sales email');
    });

    $t->run('news page displays news items', function () use ($t) {
        $_SESSION = [];
        ob_start();
        require PROJECT_ROOT . '/news.php';
        $output = ob_get_clean();

        $t->assertContains('news-list', $output, 'Should contain news list');
        $t->assertContains('Pass &amp; Play Pro tier now available', $output, 'Should display news title');
    });
}
