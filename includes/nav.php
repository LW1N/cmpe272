<?php
// $current_page: 'home' | 'about' | 'products' | 'news' | 'user' | 'contacts' | null (no highlight)
$current_page = $current_page ?? null;
require_once __DIR__ . '/auth.php';

$nav_items = [
    'home'     => ['label' => 'Home',     'url' => '/'],
    'about'    => ['label' => 'About',    'url' => '/about'],
    'products' => ['label' => 'Products', 'url' => '/products'],
    'news'     => ['label' => 'News',     'url' => '/news'],
    'user'     => ['label' => 'User',     'url' => '/user'],
    'directory' => ['label' => 'Directory', 'url' => '/users'],
    'contacts' => ['label' => 'Contacts', 'url' => '/contacts'],
];

// Admin-only section
if (is_admin()) {
    $nav_items['users'] = ['label' => 'Users', 'url' => '/admin/users.php'];
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
    <?php if (is_logged_in()): ?>
        <a href="/logout" class="nav-user" aria-label="Logged in as <?= htmlspecialchars(current_userid()) ?>. Click to log out."><?= htmlspecialchars(current_userid()) ?></a>
    <?php else: ?>
        <a href="/login" class="nav-login">Log in</a>
    <?php endif; ?>
</nav>
