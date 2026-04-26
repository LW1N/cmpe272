<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/company_users.php';

$page_title = 'Search Users';
$current_page = 'user';
$query = trim((string) ($_GET['q'] ?? ''));
$dbError = '';
$users = [];
$pdo = null;

try {
    $pdo = get_company_users_pdo();
    initialize_company_users($pdo);
    $users = search_company_users($pdo, $query);
} catch (Throwable $e) {
    error_log('user-search.php database error: ' . $e->getMessage());
    $dbError = 'The user database is not available right now. Please try again later.';
}

require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h1>Search Users</h1>
    <p class="contacts-intro"><a href="/user">User</a> / Search users</p>
</section>

<?php if ($dbError !== ''): ?>
    <div class="directory-alert" role="status"><?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<form method="GET" action="/user/search" class="form-panel user-search-form">
    <label for="q">Search by name, email, or phone</label>
    <div class="search-row">
        <input type="search" id="q" name="q" placeholder="Name, email, or phone number"
               value="<?= htmlspecialchars($query) ?>" autocomplete="off">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="/user/search" class="btn btn-secondary">Reset</a>
    </div>
</form>

<?php if ($pdo instanceof PDO): ?>
    <section class="section">
        <h2><?= $query === '' ? 'All Users' : 'Search Results' ?></h2>
        <?php if ($users === []): ?>
            <div class="contacts-empty">No users matched your search.</div>
        <?php else: ?>
            <div class="contacts-table-wrap">
                <table class="contacts-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Home Address</th>
                            <th scope="col">Home Phone</th>
                            <th scope="col">Cell Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a></td>
                                <td><?= htmlspecialchars($user['home_address']) ?></td>
                                <td><?= htmlspecialchars($user['home_phone']) ?></td>
                                <td><?= htmlspecialchars($user['cell_phone']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
