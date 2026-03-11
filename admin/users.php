<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

$page_title = 'Current Users';
$current_page = 'admin';

// Sample users list (no database)
$users = [
    ['name' => 'Mary Smith'],
    ['name' => 'John Wang'],
    ['name' => 'Alex Bington'],
    ['name' => 'Priya Nair'],
    ['name' => 'Diego Martinez'],
];

require __DIR__ . '/../includes/header.php';
?>
<h1>Current Users</h1>
<p class="contacts-intro">Registered users of the site (sample list).</p>

<div class="contacts-table-wrap">
    <table class="contacts-table" role="grid">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p><a href="/admin/logout.php" class="btn btn-secondary">Log out</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
