<?php
declare(strict_types=1);

/**
 * Standard (non-admin) users for demo purposes.
 *
 * Configure optional standard users via STANDARD_USERS_JSON, for example:
 *   {"user":"$2y$12$..."}
 * or:
 *   {"user":{"password_hash":"$2y$12$...","email":"user@example.com","phone":"408-555-0100"}}
 *
 * Generate hashes via:
 *   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
 */

/**
 * @return array<string, array{password_hash: string, email: string, phone: string}>
 */
function sample_standard_user_directory(): array
{
    return [
        'user' => [
            'password_hash' => '',
            'email' => 'user@passandplay.com',
            'phone' => '408-555-0100',
        ],
        'maya.chen' => [
            'password_hash' => '',
            'email' => 'maya.chen@passandplay.com',
            'phone' => '669-555-0112',
        ],
        'jordan.lee' => [
            'password_hash' => '',
            'email' => 'jordan.lee@passandplay.com',
            'phone' => '',
        ],
        'sofia.patel' => [
            'password_hash' => '',
            'email' => 'sofia.patel@passandplay.com',
            'phone' => '415-555-0146',
        ],
        'marco.rivera' => [
            'password_hash' => '',
            'email' => 'marco.rivera@passandplay.com',
            'phone' => '',
        ],
    ];
}

function default_user_email(string $userid): string
{
    return strtolower($userid) . '@passandplay.com';
}

/**
 * @return array<string, array{password_hash: string, email: string, phone: string}>
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

    foreach ($decoded as $userid => $config) {
        if (!is_string($userid) || !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $userid)) {
            continue;
        }

        if (is_string($config) && $config !== '') {
            $users[$userid] = [
                'password_hash' => $config,
                'email' => default_user_email($userid),
                'phone' => '',
            ];
            continue;
        }

        if (!is_array($config)) {
            continue;
        }

        $passwordHash = (string) ($config['password_hash'] ?? '');
        $email = trim((string) ($config['email'] ?? default_user_email($userid)));
        $phone = trim((string) ($config['phone'] ?? ''));

        if ($passwordHash === '' || $email === '') {
            continue;
        }

        $users[$userid] = [
            'password_hash' => $passwordHash,
            'email' => $email,
            'phone' => $phone,
        ];
    }

    return $users;
}

/**
 * @param array<string, array{password_hash: string, email: string, phone: string}> $baseUsers
 * @param array<string, array{password_hash: string, email: string, phone: string}> $configuredUsers
 * @return array<string, array{password_hash: string, email: string, phone: string}>
 */
function merge_standard_users(array $baseUsers, array $configuredUsers): array
{
    $merged = $baseUsers;

    foreach ($configuredUsers as $userid => $config) {
        $existing = $merged[$userid] ?? [
            'password_hash' => '',
            'email' => default_user_email($userid),
            'phone' => '',
        ];

        $merged[$userid] = [
            'password_hash' => $config['password_hash'],
            'email' => $config['email'] !== '' ? $config['email'] : $existing['email'],
            'phone' => $config['phone'] !== '' ? $config['phone'] : $existing['phone'],
        ];
    }

    return $merged;
}

define(
    'STANDARD_USERS',
    merge_standard_users(
        sample_standard_user_directory(),
        parse_standard_users_json((string) getenv('STANDARD_USERS_JSON'))
    )
);
