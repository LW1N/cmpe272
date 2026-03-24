<?php
declare(strict_types=1);

/**
 * Router for PHP's built-in development server.
 * Replicates the clean-URL rewrite rules from .htaccess.
 *
 * Usage:  php -S localhost:8000 router.php
 */

$routes = [
    '/about'                 => 'about.php',
    '/products'              => 'products.php',
    '/services'              => 'products.php',
    '/product'               => 'product.php',
    '/recent-products'       => 'recent-products.php',
    '/most-visited-products' => 'most-visited-products.php',
    '/news'                  => 'news.php',
    '/users'                 => 'users.php',
    '/contacts'              => 'contacts.php',
    '/login'                 => 'login.php',
    '/logout'                => 'logout.php',
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

if ($path === '') {
    $path = '/';
}

if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
    return true;
}

// Let the built-in server handle static files and .php files normally.
return false;
