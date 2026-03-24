<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/users_directory.php';
require_admin();

$page_title = 'Current Users';
$current_page = 'users';

$users = get_local_users();

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
