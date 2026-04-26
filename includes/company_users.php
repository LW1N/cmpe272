<?php
declare(strict_types=1);

const COMPANY_USERS_TABLE = 'users';

/**
 * @return array{host: string, name: string, user: string, pass: string}
 */
function get_company_user_db_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: 'mysql',
        'name' => getenv('DB_NAME') ?: 'demo',
        'user' => getenv('DB_USER') ?: 'demo',
        'pass' => getenv('DB_PASS') ?: '',
    ];
}

function get_company_users_pdo(): PDO
{
    $config = get_company_user_db_config();

    return new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function initialize_company_users(PDO $pdo): void
{
    create_company_users_table($pdo);
    seed_sample_company_users($pdo);
}

function create_company_users_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS " . COMPANY_USERS_TABLE . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            home_address VARCHAR(255) NOT NULL,
            home_phone VARCHAR(32) NOT NULL,
            cell_phone VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_name (last_name, first_name),
            INDEX idx_users_email (email),
            INDEX idx_users_home_phone (home_phone),
            INDEX idx_users_cell_phone (cell_phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return list<array{first_name: string, last_name: string, email: string, home_address: string, home_phone: string, cell_phone: string}>
 */
function get_sample_company_users(): array
{
    return [
        ['first_name' => 'Avery', 'last_name' => 'Chen', 'email' => 'avery.chen@passandplay.com', 'home_address' => '101 Market St, San Jose, CA 95113', 'home_phone' => '408-555-0101', 'cell_phone' => '408-555-1101'],
        ['first_name' => 'Jordan', 'last_name' => 'Patel', 'email' => 'jordan.patel@passandplay.com', 'home_address' => '214 River Oaks Dr, Campbell, CA 95008', 'home_phone' => '408-555-0102', 'cell_phone' => '408-555-1102'],
        ['first_name' => 'Mia', 'last_name' => 'Garcia', 'email' => 'mia.garcia@passandplay.com', 'home_address' => '88 Willow Ave, Santa Clara, CA 95050', 'home_phone' => '408-555-0103', 'cell_phone' => '408-555-1103'],
        ['first_name' => 'Noah', 'last_name' => 'Kim', 'email' => 'noah.kim@passandplay.com', 'home_address' => '510 Orchard Ln, Sunnyvale, CA 94086', 'home_phone' => '408-555-0104', 'cell_phone' => '408-555-1104'],
        ['first_name' => 'Sophia', 'last_name' => 'Nguyen', 'email' => 'sophia.nguyen@passandplay.com', 'home_address' => '742 Blossom Hill Rd, Los Gatos, CA 95032', 'home_phone' => '408-555-0105', 'cell_phone' => '408-555-1105'],
        ['first_name' => 'Ethan', 'last_name' => 'Martinez', 'email' => 'ethan.martinez@passandplay.com', 'home_address' => '390 Meridian Ave, San Jose, CA 95126', 'home_phone' => '408-555-0106', 'cell_phone' => '408-555-1106'],
        ['first_name' => 'Isabella', 'last_name' => 'Johnson', 'email' => 'isabella.johnson@passandplay.com', 'home_address' => '66 Castro St, Mountain View, CA 94041', 'home_phone' => '650-555-0107', 'cell_phone' => '650-555-1107'],
        ['first_name' => 'Liam', 'last_name' => 'Brown', 'email' => 'liam.brown@passandplay.com', 'home_address' => '1255 Homestead Rd, Cupertino, CA 95014', 'home_phone' => '408-555-0108', 'cell_phone' => '408-555-1108'],
        ['first_name' => 'Olivia', 'last_name' => 'Davis', 'email' => 'olivia.davis@passandplay.com', 'home_address' => '907 Lincoln Ave, Palo Alto, CA 94301', 'home_phone' => '650-555-0109', 'cell_phone' => '650-555-1109'],
        ['first_name' => 'Lucas', 'last_name' => 'Wilson', 'email' => 'lucas.wilson@passandplay.com', 'home_address' => '333 Santana Row, San Jose, CA 95128', 'home_phone' => '408-555-0110', 'cell_phone' => '408-555-1110'],
        ['first_name' => 'Amelia', 'last_name' => 'Lee', 'email' => 'amelia.lee@passandplay.com', 'home_address' => '48 Central Ave, Los Altos, CA 94022', 'home_phone' => '650-555-0111', 'cell_phone' => '650-555-1111'],
        ['first_name' => 'Mason', 'last_name' => 'Taylor', 'email' => 'mason.taylor@passandplay.com', 'home_address' => '611 Park Ave, San Jose, CA 95110', 'home_phone' => '408-555-0112', 'cell_phone' => '408-555-1112'],
        ['first_name' => 'Harper', 'last_name' => 'Anderson', 'email' => 'harper.anderson@passandplay.com', 'home_address' => '2030 El Camino Real, Santa Clara, CA 95050', 'home_phone' => '408-555-0113', 'cell_phone' => '408-555-1113'],
        ['first_name' => 'Logan', 'last_name' => 'Thomas', 'email' => 'logan.thomas@passandplay.com', 'home_address' => '74 Winchester Blvd, San Jose, CA 95128', 'home_phone' => '408-555-0114', 'cell_phone' => '408-555-1114'],
        ['first_name' => 'Evelyn', 'last_name' => 'Moore', 'email' => 'evelyn.moore@passandplay.com', 'home_address' => '1192 Grant Rd, Mountain View, CA 94040', 'home_phone' => '650-555-0115', 'cell_phone' => '650-555-1115'],
        ['first_name' => 'Benjamin', 'last_name' => 'Jackson', 'email' => 'benjamin.jackson@passandplay.com', 'home_address' => '801 N First St, San Jose, CA 95112', 'home_phone' => '408-555-0116', 'cell_phone' => '408-555-1116'],
        ['first_name' => 'Charlotte', 'last_name' => 'White', 'email' => 'charlotte.white@passandplay.com', 'home_address' => '455 University Ave, Palo Alto, CA 94301', 'home_phone' => '650-555-0117', 'cell_phone' => '650-555-1117'],
        ['first_name' => 'James', 'last_name' => 'Harris', 'email' => 'james.harris@passandplay.com', 'home_address' => '1720 The Alameda, San Jose, CA 95126', 'home_phone' => '408-555-0118', 'cell_phone' => '408-555-1118'],
        ['first_name' => 'Ella', 'last_name' => 'Martin', 'email' => 'ella.martin@passandplay.com', 'home_address' => '29 Stevens Creek Blvd, Cupertino, CA 95014', 'home_phone' => '408-555-0119', 'cell_phone' => '408-555-1119'],
        ['first_name' => 'Henry', 'last_name' => 'Thompson', 'email' => 'henry.thompson@passandplay.com', 'home_address' => '980 Saratoga Ave, San Jose, CA 95129', 'home_phone' => '408-555-0120', 'cell_phone' => '408-555-1120'],
    ];
}

function seed_sample_company_users(PDO $pdo): void
{
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO " . COMPANY_USERS_TABLE . "
            (first_name, last_name, email, home_address, home_phone, cell_phone)
        VALUES
            (:first_name, :last_name, :email, :home_address, :home_phone, :cell_phone)
    ");

    foreach (get_sample_company_users() as $user) {
        $stmt->execute($user);
    }
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   values: array{first_name: string, last_name: string, email: string, home_address: string, home_phone: string, cell_phone: string},
 *   errors: array<string, string>
 * }
 */
function validate_company_user_input(array $input): array
{
    $values = [
        'first_name' => trim((string) ($input['first_name'] ?? '')),
        'last_name' => trim((string) ($input['last_name'] ?? '')),
        'email' => strtolower(trim((string) ($input['email'] ?? ''))),
        'home_address' => trim((string) ($input['home_address'] ?? '')),
        'home_phone' => trim((string) ($input['home_phone'] ?? '')),
        'cell_phone' => trim((string) ($input['cell_phone'] ?? '')),
    ];
    $errors = [];

    if ($values['first_name'] === '') {
        $errors['first_name'] = 'First name is required.';
    } elseif (strlen($values['first_name']) > 80) {
        $errors['first_name'] = 'First name must be 80 characters or fewer.';
    }

    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Last name is required.';
    } elseif (strlen($values['last_name']) > 80) {
        $errors['last_name'] = 'Last name must be 80 characters or fewer.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($values['email']) > 191 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($values['home_address'] === '') {
        $errors['home_address'] = 'Home address is required.';
    } elseif (strlen($values['home_address']) > 255) {
        $errors['home_address'] = 'Home address must be 255 characters or fewer.';
    }

    if (!is_company_user_phone_valid($values['home_phone'])) {
        $errors['home_phone'] = 'Enter a valid home phone number.';
    }

    if (!is_company_user_phone_valid($values['cell_phone'])) {
        $errors['cell_phone'] = 'Enter a valid cell phone number.';
    }

    return ['values' => $values, 'errors' => $errors];
}

function is_company_user_phone_valid(string $phone): bool
{
    return preg_match('/^[0-9+().\-\s]{7,25}$/', $phone) === 1;
}

/**
 * @param array{first_name: string, last_name: string, email: string, home_address: string, home_phone: string, cell_phone: string} $values
 */
function create_company_user(PDO $pdo, array $values): int
{
    $stmt = $pdo->prepare("
        INSERT INTO " . COMPANY_USERS_TABLE . "
            (first_name, last_name, email, home_address, home_phone, cell_phone)
        VALUES
            (:first_name, :last_name, :email, :home_address, :home_phone, :cell_phone)
    ");
    $stmt->execute($values);

    return (int) $pdo->lastInsertId();
}

/**
 * @return list<array{id: int, first_name: string, last_name: string, email: string, home_address: string, home_phone: string, cell_phone: string, created_at: string}>
 */
function search_company_users(PDO $pdo, string $query, int $limit = 50): array
{
    $limit = max(1, min(100, $limit));
    $query = trim($query);

    if ($query === '') {
        $stmt = $pdo->query("
            SELECT id, first_name, last_name, email, home_address, home_phone, cell_phone, created_at
            FROM " . COMPANY_USERS_TABLE . "
            ORDER BY last_name, first_name
            LIMIT {$limit}
        ");

        return $stmt->fetchAll();
    }

    $phoneDigits = company_user_digits_only($query);
    $phoneSearchSql = '';
    $params = ['term' => '%' . escape_like_term($query) . '%'];

    if ($phoneDigits !== '') {
        $phoneSearchSql = "
            OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(home_phone, '-', ''), ' ', ''), '(', ''), ')', ''), '.', ''), '+', '') LIKE :phone_digits
            OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cell_phone, '-', ''), ' ', ''), '(', ''), ')', ''), '.', ''), '+', '') LIKE :phone_digits";
        $params['phone_digits'] = '%' . $phoneDigits . '%';
    }

    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, home_address, home_phone, cell_phone, created_at
        FROM " . COMPANY_USERS_TABLE . "
        WHERE first_name LIKE :term ESCAPE '\\\\'
            OR last_name LIKE :term ESCAPE '\\\\'
            OR CONCAT(first_name, ' ', last_name) LIKE :term ESCAPE '\\\\'
            OR CONCAT(last_name, ' ', first_name) LIKE :term ESCAPE '\\\\'
            OR email LIKE :term ESCAPE '\\\\'
            OR home_phone LIKE :term ESCAPE '\\\\'
            OR cell_phone LIKE :term ESCAPE '\\\\'
            {$phoneSearchSql}
        ORDER BY last_name, first_name
        LIMIT {$limit}
    ");
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function count_company_users(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . COMPANY_USERS_TABLE);

    return (int) $stmt->fetchColumn();
}

function escape_like_term(string $value): string
{
    return str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        $value
    );
}

function company_user_digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}
