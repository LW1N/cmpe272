<?php
declare(strict_types=1);

const RECENT_PRODUCTS_COOKIE = 'pass_play_recent_products';
const PRODUCT_COUNTS_COOKIE = 'pass_play_product_counts';
const PRODUCT_COOKIE_LIMIT = 5;
const PRODUCT_COOKIE_TTL = 2592000; // 30 days

/**
 * Load the shared product catalog once per request.
 *
 * @return array<string, array{name: string, image: string, short_description: string, description: string}>
 */
function get_product_catalog(): array
{
    static $products = null;

    if ($products === null) {
        /** @var array<string, array{name: string, image: string, short_description: string, description: string}> $products */
        $products = require __DIR__ . '/../data/products.php';
    }

    return $products;
}

function sanitize_product_slug(mixed $value): string
{
    if (!is_string($value)) {
        return '';
    }

    $value = strtolower(trim($value));

    if ($value === '' || !preg_match('/^[a-z0-9-]+$/', $value)) {
        return '';
    }

    return $value;
}

function get_product_by_slug(string $slug): ?array
{
    $products = get_product_catalog();

    return $products[$slug] ?? null;
}

/**
 * @return string[]
 */
function get_recent_product_slugs(): array
{
    $recent = json_decode($_COOKIE[RECENT_PRODUCTS_COOKIE] ?? '[]', true);

    if (!is_array($recent)) {
        return [];
    }

    $clean = [];

    foreach ($recent as $slug) {
        $sanitized = sanitize_product_slug($slug);

        if ($sanitized !== '' && !in_array($sanitized, $clean, true)) {
            $clean[] = $sanitized;
        }
    }

    return array_slice($clean, 0, PRODUCT_COOKIE_LIMIT);
}

/**
 * @return array<string, int>
 */
function get_product_visit_counts(): array
{
    $counts = json_decode($_COOKIE[PRODUCT_COUNTS_COOKIE] ?? '{}', true);

    if (!is_array($counts)) {
        return [];
    }

    $clean = [];

    foreach ($counts as $slug => $count) {
        $sanitized = sanitize_product_slug((string) $slug);

        if ($sanitized === '' || !is_numeric($count)) {
            continue;
        }

        $clean[$sanitized] = max(0, (int) $count);
    }

    return $clean;
}

function track_product_visit(string $slug): void
{
    $recent = get_recent_product_slugs();
    $recent = array_values(array_filter($recent, static fn(string $item): bool => $item !== $slug));
    array_unshift($recent, $slug);
    $recent = array_slice($recent, 0, PRODUCT_COOKIE_LIMIT);

    $counts = get_product_visit_counts();
    $counts[$slug] = ($counts[$slug] ?? 0) + 1;

    set_tracking_cookie(RECENT_PRODUCTS_COOKIE, json_encode($recent, JSON_THROW_ON_ERROR));
    set_tracking_cookie(PRODUCT_COUNTS_COOKIE, json_encode($counts, JSON_THROW_ON_ERROR));

    $_COOKIE[RECENT_PRODUCTS_COOKIE] = json_encode($recent, JSON_THROW_ON_ERROR);
    $_COOKIE[PRODUCT_COUNTS_COOKIE] = json_encode($counts, JSON_THROW_ON_ERROR);
}

function set_tracking_cookie(string $name, string $value): void
{
    setcookie($name, $value, [
        'expires' => time() + PRODUCT_COOKIE_TTL,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * @return array<int, array{slug: string, name: string, image: string, short_description: string, description: string}>
 */
function get_recent_products(int $limit = PRODUCT_COOKIE_LIMIT): array
{
    $products = get_product_catalog();
    $recentProducts = [];

    foreach (get_recent_product_slugs() as $slug) {
        if (!isset($products[$slug])) {
            continue;
        }

        $recentProducts[] = ['slug' => $slug] + $products[$slug];

        if (count($recentProducts) >= $limit) {
            break;
        }
    }

    return $recentProducts;
}

/**
 * @return array<int, array{slug: string, name: string, image: string, short_description: string, description: string, visit_count: int}>
 */
function get_most_visited_products(int $limit = PRODUCT_COOKIE_LIMIT): array
{
    $products = get_product_catalog();
    $counts = get_product_visit_counts();
    $visitedProducts = [];

    foreach ($counts as $slug => $count) {
        if ($count < 1 || !isset($products[$slug])) {
            continue;
        }

        $visitedProducts[] = ['slug' => $slug, 'visit_count' => $count] + $products[$slug];
    }

    usort(
        $visitedProducts,
        static function (array $left, array $right): int {
            if ($left['visit_count'] === $right['visit_count']) {
                return strcmp($left['name'], $right['name']);
            }

            return $right['visit_count'] <=> $left['visit_count'];
        }
    );

    return array_slice($visitedProducts, 0, $limit);
}
