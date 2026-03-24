<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/../admin/config.php';

/**
 * @return list<array{
 *   source: string,
 *   source_url: string,
 *   name: string,
 *   userid: string,
 *   email: string,
 *   phone: string,
 *   role: string,
 *   joined: string,
 *   plan: string
 * }>
 */
function get_local_users(): array
{
    $users = [[
        'source' => 'Pass & Play',
        'source_url' => '',
        'name' => 'Admin User',
        'userid' => ADMIN_USER,
        'email' => 'admin@passandplay.com',
        'phone' => '408-555-0199',
        'role' => 'Admin',
        'joined' => '',
        'plan' => '',
    ]];

    foreach (STANDARD_USERS as $userid => $user) {
        $users[] = [
            'source' => 'Pass & Play',
            'source_url' => '',
            'name' => ucwords(str_replace(['.', '_', '-'], ' ', $userid)),
            'userid' => $userid,
            'email' => $user['email'],
            'phone' => $user['phone'] !== '' ? $user['phone'] : 'Not provided',
            'role' => 'User',
            'joined' => '',
            'plan' => '',
        ];
    }

    return $users;
}

/**
 * @return array<string, string>
 */
function get_remote_user_sources(): array
{
    return [
        'Wyatt Avilla' => 'https://cmpe272.wyattavilla.dev/users.php',
        'Company A' => 'https://hyunseungsong.com/api/company_users.php',
        'Amplif AI' => 'http://cmpe272.robbietambunting.com/amplif-ai/api/users-plain.php',
    ];
}

/**
 * @return array{status_code: int, body: string, error: string}
 */
function curl_fetch_remote_user_source(string $url): array
{
    if (!function_exists('curl_init')) {
        return [
            'status_code' => 0,
            'body' => '',
            'error' => 'cURL is not available on this server.',
        ];
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'PassAndPlayUserDirectory/1.0',
        CURLOPT_HTTPHEADER => ['Accept: application/json, text/plain, */*'],
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    return [
        'status_code' => $statusCode,
        'body' => is_string($body) ? trim($body) : '',
        'error' => $error,
    ];
}

/**
 * @return list<array{
 *   source: string,
 *   source_url: string,
 *   name: string,
 *   userid: string,
 *   email: string,
 *   phone: string,
 *   role: string,
 *   joined: string,
 *   plan: string
 * }>
 */
function normalize_remote_user_payload(string $sourceName, string $sourceUrl, string $body): array
{
    if ($body === '') {
        return [];
    }

    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
        $company = trim((string) ($decoded['company'] ?? $sourceName));
        $rows = $decoded['users'] ?? [];

        if (is_array($rows)) {
            $normalized = [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $name = trim((string) ($row['name'] ?? $row['userid'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $normalized[] = [
                    'source' => $company !== '' ? $company : $sourceName,
                    'source_url' => $sourceUrl,
                    'name' => $name,
                    'userid' => trim((string) ($row['userid'] ?? '')),
                    'email' => trim((string) ($row['email'] ?? '')),
                    'phone' => trim((string) ($row['phone'] ?? '')),
                    'role' => trim((string) ($row['role'] ?? '')),
                    'joined' => trim((string) ($row['joined'] ?? '')),
                    'plan' => trim((string) ($row['plan'] ?? '')),
                ];
            }

            return $normalized;
        }
    }

    $normalized = [];
    $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];

    foreach ($lines as $line) {
        $name = trim($line);
        if ($name === '') {
            continue;
        }

        $normalized[] = [
            'source' => $sourceName,
            'source_url' => $sourceUrl,
            'name' => $name,
            'userid' => '',
            'email' => '',
            'phone' => '',
            'role' => '',
            'joined' => '',
            'plan' => '',
        ];
    }

    return $normalized;
}

/**
 * @return array{
 *   users: list<array{
 *     source: string,
 *     source_url: string,
 *     name: string,
 *     userid: string,
 *     email: string,
 *     phone: string,
 *     role: string,
 *     joined: string,
 *     plan: string
 *   }>,
 *   errors: list<string>
 * }
 */
function get_remote_users(): array
{
    if (getenv('DISABLE_REMOTE_USER_FETCH') === '1') {
        return ['users' => [], 'errors' => []];
    }

    $users = [];
    $errors = [];

    foreach (get_remote_user_sources() as $sourceName => $sourceUrl) {
        $response = curl_fetch_remote_user_source($sourceUrl);

        if ($response['error'] !== '') {
            $errors[] = $sourceName . ': ' . $response['error'];
            continue;
        }

        if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
            $errors[] = $sourceName . ': returned HTTP ' . $response['status_code'];
            continue;
        }

        $users = array_merge(
            $users,
            normalize_remote_user_payload($sourceName, $sourceUrl, $response['body'])
        );
    }

    return ['users' => $users, 'errors' => $errors];
}
