<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/company_users.php';

$page_title = 'Create User';
$current_page = 'user';
$dbError = '';
$success = '';
$errors = [];
$values = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'home_address' => '',
    'home_phone' => '',
    'cell_phone' => '',
];
$pdo = null;

try {
    $pdo = get_company_users_pdo();
    initialize_company_users($pdo);
} catch (Throwable $e) {
    error_log('user-create.php database error: ' . $e->getMessage());
    $dbError = 'The user database is not available right now. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $errors['csrf'] = 'Security validation failed. Please reload the page and try again.';
    } else {
        $validation = validate_company_user_input($_POST);
        $values = $validation['values'];
        $errors = $validation['errors'];

        if ($errors === []) {
            try {
                create_company_user($pdo, $values);
                $success = 'User created successfully.';
                $values = [
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'home_address' => '',
                    'home_phone' => '',
                    'cell_phone' => '',
                ];
            } catch (PDOException $e) {
                error_log('user-create.php insert error: ' . $e->getMessage());
                $errors['database'] = $e->getCode() === '23000'
                    ? 'A user with that email already exists.'
                    : 'The user could not be saved. Please try again later.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h1>Create User</h1>
    <p class="contacts-intro"><a href="/user">User</a> / Create user</p>
</section>

<?php if ($dbError !== ''): ?>
    <div class="directory-alert" role="status"><?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="directory-alert" role="alert">
        <ul class="form-error-list">
            <?php foreach ($errors as $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($pdo instanceof PDO): ?>
    <form method="POST" action="/user/create" class="form-panel user-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-grid">
            <div class="form-field">
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" required maxlength="80" autocomplete="given-name"
                       value="<?= htmlspecialchars($values['first_name']) ?>">
            </div>
            <div class="form-field">
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" required maxlength="80" autocomplete="family-name"
                       value="<?= htmlspecialchars($values['last_name']) ?>">
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="191" autocomplete="email"
                       value="<?= htmlspecialchars($values['email']) ?>">
            </div>
            <div class="form-field">
                <label for="home_address">Home address</label>
                <textarea id="home_address" name="home_address" required maxlength="255" rows="3" autocomplete="street-address"><?= htmlspecialchars($values['home_address']) ?></textarea>
            </div>
            <div class="form-field">
                <label for="home_phone">Home phone</label>
                <input type="tel" id="home_phone" name="home_phone" required maxlength="25" autocomplete="tel"
                       value="<?= htmlspecialchars($values['home_phone']) ?>">
            </div>
            <div class="form-field">
                <label for="cell_phone">Cell phone</label>
                <input type="tel" id="cell_phone" name="cell_phone" required maxlength="25" autocomplete="tel"
                       value="<?= htmlspecialchars($values['cell_phone']) ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save user</button>
            <a href="/user/search" class="btn btn-secondary">Search users</a>
        </div>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
