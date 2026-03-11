<?php
declare(strict_types=1);

// /admin/ → redirect to users (or login if not authenticated)
require_once __DIR__ . '/auth.php';
if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/users.php');
} else {
    header('Location: /admin/login.php');
}
exit;
