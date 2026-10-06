<?php
/**
 * Router for PHP's built-in server, mirroring deploy/nginx-amcqtest.conf:
 *
 *   php -S 127.0.0.1:8899 tests/dev_router.php
 *
 * Blocks the same paths nginx blocks, serves static assets directly, and
 * sends everything else to CodeIgniter.
 */
$root = dirname(__DIR__);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('~^/(application|system|database|deploy|tests|\.git)(/|$)~', $path)
	|| preg_match('~\.(md|sh|sql|log|example|py)$~', $path)
	|| preg_match('~/\.~', $path)) {
	http_response_code(404);
	echo 'Not found';
	return TRUE;
}
if ($path !== '/' && is_file($root . $path) && substr($path, -4) !== '.php') {
	return FALSE;   // let the built-in server send the asset
}

$_SERVER['CI_ENV'] = getenv('CI_ENV') ?: 'development';
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir($root);
require $root . '/index.php';
