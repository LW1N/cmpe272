<?php
declare(strict_types=1);

/**
 * Standard (non-admin) users for demo purposes.
 *
 * In production, replace this with a database-backed user store.
 * Generate hashes via:
 *   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
 */

// Default: userid "user" with password "user123"
define('STANDARD_USERS', [
    'user' => '$2y$12$8c5BG1SvlxiyGtYo8v7qTeAHndGvjGceRvguidhJBtOktvAmKMFL.',
]);

