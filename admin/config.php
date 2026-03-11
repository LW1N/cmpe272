<?php
declare(strict_types=1);

/**
 * Admin credentials (config-based, no database).
 *
 * Admin userid must be: admin
 * To change the admin password:
 *   1. Run: php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
 *   2. Replace ADMIN_PASSWORD_HASH below with the output.
 *
 * In production, consider moving this file outside the document root or
 * denying access via web server config (e.g. Apache AllowOverride / nginx location).
 */

define('ADMIN_USER', 'admin');
// Hash for password "admin123" — replace with your own hash (see above).
define('ADMIN_PASSWORD_HASH', '$2y$12$cDNCfFDsb8la/QKUr6qbt.aY0sNbncucVCxB3FaZ/qx3Rcip5zwPW');
