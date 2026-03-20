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
    'email'  => 'admin@passandplay.com',
    'phone'  => '408-555-0199',
];

// Standard users
foreach (STANDARD_USERS as $uid => $user) {
    $users[] = [
        'userid' => $uid,
        'role'   => 'User',
        'email'  => $user['email'],
        'phone'  => $user['phone'] !== '' ? $user['phone'] : 'Not provided',
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
                <th scope="col">Email</th>
                <th scope="col">Phone</th>
                <th scope="col">Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($u['userid']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
