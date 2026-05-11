<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product_helpers.php';

if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: public, max-age=300');
    header('X-Content-Type-Options: nosniff');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$baseUrl = get_company_products_base_url();
$products = [];

foreach (get_product_catalog() as $slug => $product) {
    $products[] = [
        'title' => $product['name'],
        'description' => $product['description'],
        'price' => (float) $product['price'],
        'image_link' => build_company_products_absolute_url($product['image'], $baseUrl),
        'product_link' => $baseUrl . '/product?slug=' . rawurlencode($slug),
    ];
}

echo json_encode(
    [
        'company_name' => 'Pass & Play',
        'products' => $products,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
);

function get_company_products_base_url(): string
{
    $configuredBaseUrl = trim((string) getenv('PUBLIC_BASE_URL'));

    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'pap.uncannydev.com');
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $host) ?: 'pap.uncannydev.com';
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

    if ($forwardedProto === 'http' || $forwardedProto === 'https') {
        $scheme = $forwardedProto;
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            ? 'https'
            : 'http';
    }

    return $scheme . '://' . $host;
}

function build_company_products_absolute_url(string $path, string $baseUrl): string
{
    if (preg_match('/^https?:\/\//i', $path) === 1) {
        return $path;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}
