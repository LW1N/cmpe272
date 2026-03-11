<?php
declare(strict_types=1);

// Backwards-compatible: admin logout moved to /logout
header('Location: /logout');
exit;
