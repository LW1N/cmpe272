<?php
declare(strict_types=1);

/**
 * Admin credentials (config-based, no database).
 *
 * Admin userid must be: admin
 * Configure the password hash via the ADMIN_PASSWORD_HASH environment variable.
 *
 * Generate a hash with:
 *   php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
 *
 * In production, consider moving this file outside the document root or
 * denying access via web server config (e.g. Apache AllowOverride / nginx location).
 */

define('ADMIN_USER', 'admin');
define('ADMIN_PASSWORD_HASH', trim((string) getenv('ADMIN_PASSWORD_HASH')));
