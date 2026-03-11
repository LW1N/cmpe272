<?php
declare(strict_types=1);

/**
 * Admin login page.
 *
 * Admin userid: admin
 * To set or change the admin password: edit admin/config.php and set
 * ADMIN_PASSWORD_HASH. Generate a new hash with:
 *   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
 */

require_once __DIR__ . '/auth.php';

// Already logged in → go to users list
if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/users.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid   = (string) ($_POST['userid'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($userid, $password)) {
        header('Location: /admin/users.php');
        exit;
    }
    $error = 'Invalid credentials';
}

$page_title = 'Admin Login';
$current_page = null;
require __DIR__ . '/../includes/header.php';
?>
<h1>Admin Login</h1>
<p class="contacts-intro">Sign in to access the admin area.</p>

<?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="/admin/login.php">
    <div style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 20rem;">
        <label for="userid">User ID</label>
        <input type="text" id="userid" name="userid" required autocomplete="username" autofocus
               value="<?= htmlspecialchars((string) ($_POST['userid'] ?? '')) ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit" class="btn btn-primary">Log in</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
