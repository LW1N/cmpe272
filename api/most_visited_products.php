<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/product_helpers.php';
require_once __DIR__ . '/../includes/product_api_helpers.php';

send_product_api_headers(60);
handle_product_api_options_request();

$baseUrl = get_product_api_base_url();
$products = [];

foreach (get_most_visited_products(PRODUCT_COOKIE_LIMIT) as $product) {
    $products[] = format_visited_product_for_partner_api($product, $baseUrl);
}

echo json_encode(
    [
        'company_name' => 'Pass & Play',
        'tracking_scope' => 'global',
        'products' => $products,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
);
