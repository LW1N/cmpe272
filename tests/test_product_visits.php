<?php
declare(strict_types=1);

function run_product_visit_tests(TestRunner $t): void
{
    $t->section('Product Visits');

    require_once PROJECT_ROOT . '/includes/product_helpers.php';

    $t->run('track_product_visit keeps recent visits in cookie and global counts in database', function () use ($t) {
        $dbFile = tempnam(sys_get_temp_dir(), 'pass_play_product_visits_');
        $oldCookies = $_COOKIE;
        $_COOKIE = [];
        putenv('PRODUCT_VISITS_DSN=sqlite:' . $dbFile);

        try {
            track_product_visit('events-hub');
            track_product_visit('voice-lounge');

            $recentProducts = get_recent_products();
            $pdo = new PDO('sqlite:' . $dbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $globalProducts = get_global_most_visited_products($pdo);

            $t->assertCount(2, $recentProducts, 'Recent products should be stored per visitor cookie');
            $t->assertEqual('Voice Lounge', $recentProducts[0]['name'], 'Most recent product should appear first in cookie history');
            $t->assertCount(2, $globalProducts, 'Global products should be stored in the visit count table');
            $t->assertEqual('Events Hub', $globalProducts[0]['name'], 'Global products should use product name as tie-breaker');
            $t->assertEqual(1, $globalProducts[0]['visit_count'], 'Global product count should increment on product views');
        } finally {
            putenv('PRODUCT_VISITS_DSN');
            $_COOKIE = $oldCookies;

            if (is_string($dbFile) && file_exists($dbFile)) {
                unlink($dbFile);
            }
        }
    });

    $t->run('global most visited products return top five by shared visit count', function () use ($t) {
        $dbFile = tempnam(sys_get_temp_dir(), 'pass_play_product_visits_');
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        try {
            foreach ([
                'voice-lounge' => 2,
                'events-hub' => 8,
                'moderator-console' => 8,
                'community-launch-kit' => 4,
                'threaded-chat-suite' => 1,
                'mobile-companion' => 3,
                'missing-product' => 99,
            ] as $slug => $count) {
                for ($i = 0; $i < $count; $i++) {
                    increment_global_product_visit($pdo, $slug);
                }
            }

            $products = get_global_most_visited_products($pdo, 5);

            $t->assertCount(5, $products, 'Global top products should be limited to five known catalog products');
            $t->assertEqual('Events Hub', $products[0]['name'], 'Global top products should sort by visit count descending');
            $t->assertEqual(8, $products[0]['visit_count'], 'Global top products should include visit counts');
            $t->assertEqual('Moderator Console', $products[1]['name'], 'Global top products should sort ties by product name');
            $t->assertEqual('Community Launch Kit', $products[2]['name'], 'Global top products should include the next-highest count');
        } finally {
            if (is_string($dbFile) && file_exists($dbFile)) {
                unlink($dbFile);
            }
        }
    });
}
