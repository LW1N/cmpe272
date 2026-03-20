<?php
declare(strict_types=1);

/**
 * Standard (non-admin) users for demo purposes.
 *
 * Configure optional standard users via STANDARD_USERS_JSON, for example:
 *   {"user":"$2y$12$..."}
 *
 * Generate hashes via:
 *   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
 */

/**
 * @return array<string, string>
 */
function parse_standard_users_json(string $raw): array
{
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $users = [];

    foreach ($decoded as $userid => $hash) {
        if (!is_string($userid) || !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $userid) || !is_string($hash) || $hash === '') {
            continue;
        }

        $users[$userid] = $hash;
    }

    return $users;
}

define('STANDARD_USERS', [
    ...parse_standard_users_json((string) getenv('STANDARD_USERS_JSON')),
]);
