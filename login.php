<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    // If the session has timed out, check_session_timeout() will destroy it and
    // redirect to /login?timeout=1 (never reaching the header() below).
    check_session_timeout();
    header('Location: ' . (is_admin() ? '/admin/users.php' : '/'));
    exit;
}

$error = '';
$timeout = !empty($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid   = (string) ($_POST['userid'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Security validation failed. Please reload the page and try again.';
    } elseif (attempt_login($userid, $password)) {
        header('Location: ' . (is_admin() ? '/admin/users.php' : '/'));
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

$csrf_token = generate_csrf_token();
$page_title = 'Log in';
$current_page = null;
require __DIR__ . '/includes/header.php';
?>
<h1>Log in</h1>
<p class="contacts-intro">Sign in to access your account. Admins will see the Users section.</p>

<?php if ($timeout && $error === ''): ?>
    <p class="error">Your session expired due to inactivity. Please log in again.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 20rem;">
        <label for="userid">User ID</label>
        <input type="text" id="userid" name="userid" required autocomplete="username" autofocus
               value="<?= htmlspecialchars((string) ($_POST['userid'] ?? '')) ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit" class="btn btn-primary">Log in</button>
    </div>
</form>

<section class="section">
    <h3>Demo accounts</h3>
    <ul>
        <li><strong>Admin</strong>: userid <code>admin</code> (password is set in <code>admin/config.php</code>)</li>
        <li><strong>Standard</strong>: userid <code>user</code> / password <code>user123</code></li>
    </ul>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

