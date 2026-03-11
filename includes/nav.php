<?php
// $current_page: 'home' | 'about' | 'products' | 'news' | 'contacts' | null (no highlight)
$current_page = $current_page ?? null;
require_once __DIR__ . '/auth.php';

$nav_items = [
    'home'     => ['label' => 'Home',     'url' => '/'],
    'about'    => ['label' => 'About',    'url' => '/about'],
    'products' => ['label' => 'Products', 'url' => '/products'],
    'news'     => ['label' => 'News',     'url' => '/news'],
    'contacts' => ['label' => 'Contacts', 'url' => '/contacts'],
];

// Admin-only section
if (is_admin()) {
    $nav_items['users'] = ['label' => 'Users', 'url' => '/admin/users.php'];
}

// Auth link
if (is_logged_in()) {
    $nav_items['logout'] = ['label' => 'Log out', 'url' => '/logout'];
} else {
    $nav_items['login'] = ['label' => 'Log in', 'url' => '/login'];
}
?>
<nav class="site-nav" aria-label="Main navigation">
    <?php foreach ($nav_items as $key => $item): ?>
        <a href="<?= htmlspecialchars($item['url']) ?>"
           <?= ($current_page === $key) ? ' class="active" aria-current="page"' : '' ?>>
            <?= htmlspecialchars($item['label']) ?>
        </a>
    <?php endforeach; ?>
    <a href="/demo.php" class="nav-secondary">Demo</a>
</nav>
