<?php
// Expected output: CSRF rejection; "BYPASS" must never appear.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../core/auth.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION = [];
$_POST = [];
if (($argv[1] ?? '') === 'array') {
    $_SESSION['csrf_token'] = 'valid-token';
    $_POST['_csrf'] = [];
}
csrf_verify();
echo 'BYPASS';
