<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/users_directory.php';

$page_title = 'User Directory';
$current_page = 'directory';

$localUsers = get_local_users();
$remoteDirectory = get_remote_users();
$remoteUsers = $remoteDirectory['users'];
$remoteErrors = $remoteDirectory['errors'];
$allUsers = array_merge($localUsers, $remoteUsers);

require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h1>User Directory</h1>
    <p class="contacts-intro">This page combines Pass &amp; Play users with users fetched live from partner apps using cURL.</p>
    <p class="contacts-intro">Local-only API for curl: <a href="/api/local_users.php"><code>/api/local_users.php</code></a></p>
</section>

<?php if (!empty($remoteErrors)): ?>
    <div class="directory-alert" role="status">
        Some remote directories could not be loaded right now:
        <?= htmlspecialchars(implode(' | ', $remoteErrors)) ?>
    </div>
<?php endif; ?>

<div class="directory-stats">
    <article class="directory-stat-card">
        <strong><?= count($localUsers) ?></strong>
        <span>Local users</span>
    </article>
    <article class="directory-stat-card">
        <strong><?= count($remoteUsers) ?></strong>
        <span>Remote users</span>
    </article>
    <article class="directory-stat-card">
        <strong><?= count($allUsers) ?></strong>
        <span>Total listed users</span>
    </article>
</div>

<section class="section">
    <h2>Connected Sources</h2>
    <div class="card-grid">
        <article class="card">
            <h3>Pass &amp; Play</h3>
            <p>Local app users defined by this site.</p>
        </article>
        <?php foreach (get_remote_user_sources() as $sourceName => $sourceUrl): ?>
            <article class="card">
                <h3><?= htmlspecialchars($sourceName) ?></h3>
                <p><a href="<?= htmlspecialchars($sourceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($sourceUrl) ?></a></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <h2>Combined Users</h2>
    <div class="contacts-table-wrap">
        <table class="contacts-table" role="grid">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Source</th>
                    <th scope="col">Name</th>
                    <th scope="col">User ID</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Role</th>
                    <th scope="col">Joined</th>
                    <th scope="col">Plan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $i => $user): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($user['source']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['userid'] !== '' ? $user['userid'] : 'N/A') ?></td>
                        <td>
                            <?php if ($user['email'] !== ''): ?>
                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['phone'] !== '' ? $user['phone'] : 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['role'] !== '' ? $user['role'] : 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['joined'] !== '' ? $user['joined'] : 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['plan'] !== '' ? $user['plan'] : 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
