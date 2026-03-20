<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/product_helpers.php';

$page_title = 'Products & Services';
$current_page = 'products';
$products = get_product_catalog();
require __DIR__ . '/includes/header.php';
?>
<h1>Products &amp; Services</h1>

<section class="section">
    <h2>Explore our offerings</h2>
    <p>Choose from 10 products and services built to help communities, creators, and teams run smoother on Pass &amp; Play.</p>
    <div class="product-meta-links">
        <a href="/recent-products">View last 5 visited products</a>
        <a href="/most-visited-products">View top 5 most visited products</a>
    </div>
</section>

<section class="section">
    <h2>Product catalog</h2>
    <div class="card-grid">
        <?php foreach ($products as $slug => $product): ?>
            <article class="card product-card">
                <img
                    src="<?= htmlspecialchars($product['image']) ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    class="product-card-image"
                >
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['short_description']) ?></p>
                <p><a href="/product?slug=<?= urlencode($slug) ?>">View details</a></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
