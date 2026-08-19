#!/usr/bin/env php
<?php
/*
 * Provision HubSpot runtime credential from production to staging wp-config.php
 *
 * Usage:
 *   PROD_SECRET_FILE=/path/to/secret STAGING_CONFIG=/path/to/wp-config.php php provision-hubspot-credential.php
 *
 * Exit codes:
 *   0: Success
 *   1: General error
 *   2: Prerequisites failed (secret too short or config missing)
 *   3: Insertion anchor not found
 *   4: Credential update failed
 *   5: Atomic write/rename failed
 */

$secret_file = (string) getenv('PROD_SECRET_FILE');
$config_path = (string) getenv('STAGING_CONFIG');

$secret = is_file($secret_file) ? rtrim((string) file_get_contents($secret_file), "\r\n") : '';

// Validate prerequisites
if (strlen($secret) < 20) {
    fwrite(STDERR, "ERROR: HubSpot secret too short (must be >= 20 characters)\n");
    exit(2);
}

if (!is_file($config_path)) {
    fwrite(STDERR, "ERROR: Staging wp-config.php not found: $config_path\n");
    exit(2);
}

$config = (string) file_get_contents($config_path);
$line = "define( 'NVX_HUBSPOT_ACCESS_TOKEN', " . var_export($secret, true) . " );";

// Strategy 1: Update existing define
$define_pattern = '/^[ \t]*define\s*\(\s*[\'\"]NVX_HUBSPOT_ACCESS_TOKEN[\'\"]\s*,.*?\);\s*$/m';
if (preg_match($define_pattern, $config) === 1) {
    $updated = preg_replace($define_pattern, $line, $config, 1);
    if ($updated === null) {
        fwrite(STDERR, "ERROR: Failed to replace existing NVX_HUBSPOT_ACCESS_TOKEN define\n");
        exit(4);
    }
} else {
    // Strategy 2: Insert before "That's all, stop editing" comment
    $anchor_pattern = '/^[ \t]*\/\*\s*That[\'’]s all, stop editing.*$/mi';
    if (preg_match($anchor_pattern, $config) === 1) {
        $updated = preg_replace($anchor_pattern, $line . "\n\n$0", $config, 1);
        if ($updated === null) {
            fwrite(STDERR, "ERROR: Failed to insert before stop editing comment\n");
            exit(4);
        }
    } else {
        // Strategy 3: Insert before wp-settings.php require
        $settings_pattern = '/^[ \t]*require_once\s+ABSPATH\s*\.\s*[\'\"]wp-settings\.php[\'\"]\s*;\s*$/m';
        if (preg_match($settings_pattern, $config) !== 1) {
            fwrite(STDERR, "ERROR: No valid insertion anchor found in wp-config.php (tried: existing define, stop editing comment, wp-settings.php require)\n");
            exit(3);
        }
        $updated = preg_replace($settings_pattern, $line . "\n\n$0", $config, 1);
        if ($updated === null) {
            fwrite(STDERR, "ERROR: Failed to insert before wp-settings.php require\n");
            exit(4);
        }
    }
}

// Validate update succeeded
if (!is_string($updated) || $updated === $config) {
    if (strpos($config, 'NVX_HUBSPOT_ACCESS_TOKEN') === false) {
        fwrite(STDERR, "ERROR: Credential update failed - config unchanged and NVX_HUBSPOT_ACCESS_TOKEN not present\n");
        exit(4);
    }
}

// Atomic write operation
$tmp = $config_path . '.nvx-hubspot-' . getmypid() . '.tmp';
$mode = fileperms($config_path);

try {
    if (file_put_contents($tmp, $updated, LOCK_EX) === false) {
        throw new RuntimeException('Failed to write temporary file');
    }
    
    if (is_int($mode)) {
        @chmod($tmp, $mode & 0777);
    }
    
    if (!@rename($tmp, $config_path)) {
        throw new RuntimeException('Failed to rename temporary file to wp-config.php');
    }
} catch (Throwable $e) {
    @unlink($tmp);
    fwrite(STDERR, "ERROR: Atomic credential update failed: " . $e->getMessage() . "\n");
    exit(5);
}

exit(0);
