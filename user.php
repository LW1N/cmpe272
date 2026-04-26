<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/company_users.php';

$page_title = 'User';
$current_page = 'user';
$dbError = '';
$userCount = null;

try {
    $pdo = get_company_users_pdo();
    initialize_company_users($pdo);
    $userCount = count_company_users($pdo);
} catch (Throwable $e) {
    error_log('user.php database error: ' . $e->getMessage());
    $dbError = 'The user database is not available right now. Please try again later.';
}

require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h1>User</h1>
    <p class="contacts-intro">Create and search Pass &amp; Play company users stored in MySQL.</p>
</section>

<?php if ($dbError !== ''): ?>
    <div class="directory-alert" role="status"><?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<?php if ($userCount !== null): ?>
    <div class="user-summary" aria-label="User database summary">
        <strong><?= (int) $userCount ?></strong>
        <span>users in the company database</span>
    </div>
<?php endif; ?>

<section class="section">
    <h2>User Forms</h2>
    <div class="user-action-grid">
        <article class="card user-action-card">
            <h3>Create User</h3>
            <p>Add a company user with name, email, home address, home phone, and cell phone.</p>
            <a href="/user/create" class="btn btn-primary">Create user</a>
        </article>
        <article class="card user-action-card">
            <h3>Search Users</h3>
            <p>Find users by first name, last name, email, home phone, or cell phone.</p>
            <a href="/user/search" class="btn btn-secondary">Search users</a>
        </article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
