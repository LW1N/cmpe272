<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/users_directory.php';

if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
}

$users = array_map(
    static function (array $user): array {
        return [
            'name' => $user['name'],
            'userid' => $user['userid'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
        ];
    },
    get_local_users()
);

echo json_encode(
    [
        'company' => 'Pass & Play',
        'users' => $users,
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
