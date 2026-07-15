<?php
declare(strict_types=1);

$path = getenv('NVX_WP_CONFIG');
if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path) || !is_writable($path)) {
    fwrite(STDERR, "Configuration file is not safely writable.\n");
    exit(1);
}

$content = file_get_contents($path);
if (!is_string($content) || $content === '') {
    fwrite(STDERR, "Unable to read configuration.\n");
    exit(1);
}

$required = [
    'AUTH_KEY',
    'SECURE_AUTH_KEY',
    'LOGGED_IN_KEY',
    'NONCE_KEY',
    'AUTH_SALT',
    'SECURE_AUTH_SALT',
    'LOGGED_IN_SALT',
    'NONCE_SALT',
];
$optional = ['WP_CACHE_KEY_SALT'];

foreach (array_merge($required, $optional) as $name) {
    $pattern = '/^[\t ]*define\([\t ]*[\'\"]' . preg_quote($name, '/') . '[\'\"][\t ]*,.*\);[\t ]*$/m';
    $replacement = "define( '" . $name . "', '" . bin2hex(random_bytes(64)) . "' );";
    $updated = preg_replace($pattern, $replacement, $content, 1, $count);

    if (!is_string($updated)) {
        fwrite(STDERR, "Replacement failed for {$name}.\n");
        exit(1);
    }
    if (in_array($name, $required, true) && $count !== 1) {
        fwrite(STDERR, "Expected exactly one definition for {$name}.\n");
        exit(1);
    }
    if (in_array($name, $optional, true) && $count > 1) {
        fwrite(STDERR, "Multiple definitions found for {$name}.\n");
        exit(1);
    }

    $content = $updated;
}

$tmp = $path . '.incident-new';
$mode = fileperms($path) & 0777;
if (file_put_contents($tmp, $content, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write temporary configuration.\n");
    exit(1);
}
chmod($tmp, $mode);

if (!rename($tmp, $path)) {
    @unlink($tmp);
    fwrite(STDERR, "Atomic configuration replacement failed.\n");
    exit(1);
}

fwrite(STDOUT, "Authentication material rotated without printing values.\n");
