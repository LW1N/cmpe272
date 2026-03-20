<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/product_helpers.php';

$page_title = 'Most Visited Products';
$current_page = 'products';
$mostVisitedProducts = get_most_visited_products();

require __DIR__ . '/includes/header.php';
?>
<h1>Top 5 most visited products</h1>

<section class="section">
    <?php if ($mostVisitedProducts === []): ?>
        <div class="contacts-empty">
            <p>No product visits have been recorded yet.</p>
            <p><a href="/products">Browse Products &amp; Services</a></p>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($mostVisitedProducts as $product): ?>
                <article class="card product-card">
                    <img
                        src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="product-card-image"
                    >
                    <h2><?= htmlspecialchars($product['name']) ?></h2>
                    <p><?= htmlspecialchars($product['short_description']) ?></p>
                    <p class="product-visit-count">Visits: <?= htmlspecialchars((string) $product['visit_count']) ?></p>
                    <p><a href="/product?slug=<?= urlencode($product['slug']) ?>">View details</a></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<p><a href="/products" class="btn btn-secondary">Back to Products &amp; Services</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
