<?php
declare(strict_types=1);

// /admin/ → redirect to users (or login if not authenticated)
require_once __DIR__ . '/auth.php';
if (is_admin()) {
    header('Location: /admin/users.php');
} else {
    header('Location: /login');
}
exit;
