<?php
declare(strict_types=1);

function send_product_api_headers(int $maxAge = 300): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: public, max-age=' . $maxAge);
    header('X-Content-Type-Options: nosniff');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
}

function handle_product_api_options_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') {
        return;
    }

    http_response_code(204);
    exit;
}

function get_product_api_base_url(): string
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

function build_product_api_absolute_url(string $path, string $baseUrl): string
{
    if (preg_match('/^https?:\/\//i', $path) === 1) {
        return $path;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

/**
 * @param array{name: string, price: float, image: string, short_description: string, description: string} $product
 * @return array{title: string, description: string, price: float, image_link: string, product_link: string}
 */
function format_product_for_partner_api(string $slug, array $product, string $baseUrl): array
{
    return [
        'title' => $product['name'],
        'description' => $product['description'],
        'price' => (float) $product['price'],
        'image_link' => build_product_api_absolute_url($product['image'], $baseUrl),
        'product_link' => $baseUrl . '/product?slug=' . rawurlencode($slug),
    ];
}

/**
 * @param array{slug: string, name: string, price: float, image: string, short_description: string, description: string, visit_count: int} $product
 * @return array{title: string, description: string, price: float, image_link: string, product_link: string, visit_count: int}
 */
function format_visited_product_for_partner_api(array $product, string $baseUrl): array
{
    return format_product_for_partner_api($product['slug'], $product, $baseUrl) + [
        'visit_count' => (int) $product['visit_count'],
    ];
}
