<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/product_helpers.php';

$slug = sanitize_product_slug($_GET['slug'] ?? null);
$product = $slug !== '' ? get_product_by_slug($slug) : null;

if ($product !== null) {
    track_product_visit($slug);
}

if ($product === null) {
    http_response_code(404);
    $page_title = 'Product Not Found';
} else {
    $page_title = $product['name'];
}

$current_page = 'products';
require __DIR__ . '/includes/header.php';
?>
<?php if ($product === null): ?>
    <h1>Product not found</h1>
    <section class="section">
        <div class="card">
            <p>We could not find that product or service. Please return to the catalog and choose one of the available options.</p>
            <p><a href="/products" class="btn btn-secondary">Back to Products &amp; Services</a></p>
        </div>
    </section>
<?php else: ?>
    <h1><?= htmlspecialchars($product['name']) ?></h1>

    <section class="section product-detail">
        <div class="product-detail-media">
            <img
                src="<?= htmlspecialchars($product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                class="product-detail-image"
            >
        </div>
        <div class="product-detail-content">
            <p class="product-detail-summary"><?= htmlspecialchars($product['short_description']) ?></p>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <div class="product-meta-links">
                <a href="/products" class="btn btn-secondary">Back to Products &amp; Services</a>
                <a href="/recent-products">Last 5 visited</a>
                <a href="/most-visited-products">Top 5 most visited</a>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
