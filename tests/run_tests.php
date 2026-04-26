<?php
declare(strict_types=1);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);

define('PROJECT_ROOT', dirname(__DIR__));

putenv('ADMIN_PASSWORD_HASH=$2y$12$bHXB0IfZeE.xLkzOubx/IOUJbi2Cyw5R15omS435JxsQ1O86cql96');
putenv('STANDARD_USERS_JSON={"user":"$2y$12$8c5BG1SvlxiyGtYo8v7qTeAHndGvjGceRvguidhJBtOktvAmKMFL."}');
putenv('LOGIN_MAX_ATTEMPTS=5');
putenv('LOGIN_ATTEMPT_WINDOW=300');
putenv('LOGIN_LOCKOUT_SECONDS=900');
putenv('DISABLE_REMOTE_USER_FETCH=1');

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

require __DIR__ . '/framework.php';

$t = new TestRunner();

echo "Pass & Play — Test Suite\n";
echo "========================\n";

require __DIR__ . '/test_auth.php';
run_auth_tests($t);

require __DIR__ . '/test_contacts_loader.php';
run_contacts_loader_tests($t);

require __DIR__ . '/test_news_data.php';
run_news_data_tests($t);

require __DIR__ . '/test_company_users.php';
run_company_user_tests($t);

require __DIR__ . '/test_page_rendering.php';
run_page_rendering_tests($t);

require __DIR__ . '/test_user_access.php';
run_user_access_tests($t);

$exitCode = $t->summary();
ob_end_flush();
exit($exitCode);
