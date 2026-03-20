<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';
$timeout = !empty($_GET['timeout']);
$authConfigErrors = auth_configuration_errors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid   = (string) ($_POST['userid'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($authConfigErrors !== []) {
        $error = 'Log in is unavailable until authentication secrets are configured.';
    } elseif (is_login_rate_limited($userid)) {
        $error = 'Too many login attempts. Please wait and try again.';
    } elseif (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Security validation failed. Please reload the page and try again.';
    } elseif (attempt_login($userid, $password)) {
        header('Location: /');
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
<?php if ($authConfigErrors !== [] && $error === ''): ?>
    <p class="error">Log in is unavailable until authentication secrets are configured.</p>
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
<?php require __DIR__ . '/includes/footer.php'; ?>
