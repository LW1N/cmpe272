<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$page_title = 'Current Users';
$current_page = 'users';

// Build user list from auth config
$users = [];

// Admin user
$users[] = [
    'userid' => ADMIN_USER,
    'role'   => 'Admin',
];

// Standard users
foreach (array_keys(STANDARD_USERS) as $uid) {
    $users[] = [
        'userid' => $uid,
        'role'   => 'User',
    ];
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Current Users</h1>
<p class="contacts-intro">Registered users of the site.</p>

<div class="contacts-table-wrap">
    <table class="contacts-table" role="grid">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">User ID</th>
                <th scope="col">Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($u['userid']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
