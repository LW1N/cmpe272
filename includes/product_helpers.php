<?php
declare(strict_types=1);

const RECENT_PRODUCTS_COOKIE = 'pass_play_recent_products';
const PRODUCT_COUNTS_COOKIE = 'pass_play_product_counts';
const PRODUCT_COOKIE_LIMIT = 5;
const PRODUCT_COOKIE_TTL = 2592000; // 30 days
const PRODUCT_VISIT_COUNTS_TABLE = 'product_visit_counts';

/**
 * Load the shared product catalog once per request.
 *
 * @return array<string, array{name: string, price: float, image: string, short_description: string, description: string}>
 */
function get_product_catalog(): array
{
    static $products = null;

    if ($products === null) {
        /** @var array<string, array{name: string, price: float, image: string, short_description: string, description: string}> $products */
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
function get_cookie_product_visit_counts(): array
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
    track_recent_product_visit($slug);
    track_global_product_visit($slug);
}

function track_recent_product_visit(string $slug): void
{
    $slug = sanitize_product_slug($slug);

    if ($slug === '' || get_product_by_slug($slug) === null) {
        return;
    }

    $recent = get_recent_product_slugs();
    $recent = array_values(array_filter($recent, static fn(string $item): bool => $item !== $slug));
    array_unshift($recent, $slug);
    $recent = array_slice($recent, 0, PRODUCT_COOKIE_LIMIT);

    set_tracking_cookie(RECENT_PRODUCTS_COOKIE, json_encode($recent, JSON_THROW_ON_ERROR));

    $_COOKIE[RECENT_PRODUCTS_COOKIE] = json_encode($recent, JSON_THROW_ON_ERROR);
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
 * @return array<int, array{slug: string, name: string, price: float, image: string, short_description: string, description: string}>
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
 * @return array<int, array{slug: string, name: string, price: float, image: string, short_description: string, description: string, visit_count: int}>
 */
function get_most_visited_products(int $limit = PRODUCT_COOKIE_LIMIT): array
{
    try {
        return get_global_most_visited_products(get_product_visit_pdo(), $limit);
    } catch (\Throwable $e) {
        error_log('global product visit lookup error: ' . $e->getMessage());

        return [];
    }
}

/**
 * @return array<int, array{slug: string, name: string, price: float, image: string, short_description: string, description: string, visit_count: int}>
 */
function get_cookie_most_visited_products(int $limit = PRODUCT_COOKIE_LIMIT): array
{
    return get_products_by_visit_counts(get_cookie_product_visit_counts(), $limit);
}

/**
 * @return array{dsn: string, user: string, pass: string}
 */
function get_product_visit_db_config(): array
{
    $dsn = trim((string) getenv('PRODUCT_VISITS_DSN'));
    $usesCustomDsn = $dsn !== '';

    if (!$usesCustomDsn) {
        $host = getenv('DB_HOST') ?: 'mysql';
        $name = getenv('DB_NAME') ?: 'demo';
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    }

    return [
        'dsn' => $dsn,
        'user' => getenv('PRODUCT_VISITS_DB_USER') ?: ($usesCustomDsn ? '' : (getenv('DB_USER') ?: 'demo')),
        'pass' => getenv('PRODUCT_VISITS_DB_PASS') ?: ($usesCustomDsn ? '' : (getenv('DB_PASS') ?: '')),
    ];
}

function get_product_visit_pdo(): PDO
{
    $config = get_product_visit_db_config();

    return new PDO(
        $config['dsn'],
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function initialize_product_visit_tracking(PDO $pdo): void
{
    create_product_visit_counts_table($pdo);
}

function create_product_visit_counts_table(PDO $pdo): void
{
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS " . PRODUCT_VISIT_COUNTS_TABLE . " (
                slug VARCHAR(120) PRIMARY KEY,
                visit_count INTEGER NOT NULL DEFAULT 0 CHECK (visit_count >= 0),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS " . PRODUCT_VISIT_COUNTS_TABLE . " (
            slug VARCHAR(120) PRIMARY KEY,
            visit_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_product_visit_counts_visit_count (visit_count)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function track_global_product_visit(string $slug): void
{
    try {
        increment_global_product_visit(get_product_visit_pdo(), $slug);
    } catch (\Throwable $e) {
        error_log('global product visit tracking error: ' . $e->getMessage());
    }
}

function increment_global_product_visit(PDO $pdo, string $slug): void
{
    $slug = sanitize_product_slug($slug);

    if ($slug === '' || get_product_by_slug($slug) === null) {
        return;
    }

    initialize_product_visit_tracking($pdo);

    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $stmt = $pdo->prepare("
            INSERT INTO " . PRODUCT_VISIT_COUNTS_TABLE . " (slug, visit_count)
            VALUES (:slug, 1)
            ON DUPLICATE KEY UPDATE
                visit_count = visit_count + 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute(['slug' => $slug]);

        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO " . PRODUCT_VISIT_COUNTS_TABLE . " (slug, visit_count)
        VALUES (:slug, 1)
        ON CONFLICT(slug) DO UPDATE SET
            visit_count = visit_count + 1,
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute(['slug' => $slug]);
}

/**
 * @return array<int, array{slug: string, name: string, price: float, image: string, short_description: string, description: string, visit_count: int}>
 */
function get_global_most_visited_products(PDO $pdo, int $limit = PRODUCT_COOKIE_LIMIT): array
{
    initialize_product_visit_tracking($pdo);

    $stmt = $pdo->query("
        SELECT slug, visit_count
        FROM " . PRODUCT_VISIT_COUNTS_TABLE . "
        WHERE visit_count > 0
    ");
    $counts = [];

    foreach ($stmt->fetchAll() as $row) {
        $counts[(string) $row['slug']] = (int) $row['visit_count'];
    }

    return get_products_by_visit_counts($counts, $limit);
}

/**
 * @param array<string, int> $counts
 * @return array<int, array{slug: string, name: string, price: float, image: string, short_description: string, description: string, visit_count: int}>
 */
function get_products_by_visit_counts(array $counts, int $limit = PRODUCT_COOKIE_LIMIT): array
{
    $products = get_product_catalog();
    $visitedProducts = [];
    $limit = max(0, $limit);

    if ($limit === 0) {
        return [];
    }

    foreach ($counts as $slug => $count) {
        $slug = sanitize_product_slug((string) $slug);
        $count = (int) $count;

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
