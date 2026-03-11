<?php
declare(strict_types=1);

function run_news_data_tests(TestRunner $t): void
{
    $t->section('News Data');

    $newsFile = PROJECT_ROOT . '/data/news.json';

    $t->run('news.json file exists and is readable', function () use ($t, $newsFile) {
        $t->assertTrue(is_readable($newsFile), "news.json should be readable");
    });

    $t->run('news.json contains valid JSON array', function () use ($t, $newsFile) {
        $raw = file_get_contents($newsFile);
        $t->assertTrue($raw !== false, 'Should read file contents');
        $decoded = json_decode($raw, true);
        $t->assertTrue($decoded !== null, 'Should decode as valid JSON');
        $t->assertTrue(is_array($decoded), 'Should decode as array');
    });

    $t->run('news has at least one item', function () use ($t, $newsFile) {
        $news = json_decode(file_get_contents($newsFile), true);
        $t->assertGreaterThan(0, count($news), 'Should have at least one news item');
    });

    $t->run('news items have required fields', function () use ($t, $newsFile) {
        $news = json_decode(file_get_contents($newsFile), true);
        $required = ['date', 'title', 'summary', 'link'];
        foreach ($news as $i => $item) {
            foreach ($required as $field) {
                $t->assertTrue(
                    array_key_exists($field, $item),
                    "News item #{$i} missing field '{$field}'"
                );
            }
        }
    });

    $t->run('news items have non-empty titles', function () use ($t, $newsFile) {
        $news = json_decode(file_get_contents($newsFile), true);
        foreach ($news as $i => $item) {
            $t->assertNotEmpty($item['title'], "News item #{$i} has empty title");
        }
    });

    $t->run('news dates are valid date strings', function () use ($t, $newsFile) {
        $news = json_decode(file_get_contents($newsFile), true);
        foreach ($news as $i => $item) {
            $ts = strtotime($item['date']);
            $t->assertTrue($ts !== false, "News item #{$i} date '{$item['date']}' is not a valid date");
        }
    });

    $t->run('news can be sorted by date descending', function () use ($t, $newsFile) {
        $news = json_decode(file_get_contents($newsFile), true);
        usort($news, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        for ($i = 1; $i < count($news); $i++) {
            $t->assertTrue(
                strcmp($news[$i - 1]['date'], $news[$i]['date']) >= 0,
                "News not sorted: '{$news[$i-1]['date']}' should be >= '{$news[$i]['date']}'"
            );
        }
    });
}
