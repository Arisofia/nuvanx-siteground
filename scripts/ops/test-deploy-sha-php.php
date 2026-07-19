<?php
/**
 * Isolated runtime contract for the public deployment SHA marker.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

const NVX_TEST_DEPLOY_SHA = '0123456789abcdef0123456789abcdef01234567';

$GLOBALS['nvx_test_theme_dir'] = sys_get_temp_dir() . '/nvx-deploy-sha-' . bin2hex( random_bytes( 6 ) );
if ( ! mkdir( $GLOBALS['nvx_test_theme_dir'], 0700, true ) && ! is_dir( $GLOBALS['nvx_test_theme_dir'] ) ) {
	fwrite( STDERR, "FAIL: unable to create test directory\n" );
	exit( 1 );
}
file_put_contents( $GLOBALS['nvx_test_theme_dir'] . '/.nvx-deploy-sha', NVX_TEST_DEPLOY_SHA . "\n" );

function add_filter( ...$args ): void {}
function add_action( ...$args ): void {}
function wp_get_environment_type(): string { return 'production'; }
function get_template_directory(): string { return $GLOBALS['nvx_test_theme_dir']; }
function is_admin(): bool { return false; }
function esc_attr( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php';

function nvx_deploy_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

nvx_deploy_assert( NVX_TEST_DEPLOY_SHA === nvx_environment_deploy_sha(), 'marker SHA must resolve exactly' );

ob_start();
nvx_environment_render_deploy_sha();
$output = (string) ob_get_clean();
nvx_deploy_assert( false !== strpos( $output, 'name="nvx-deploy-sha"' ), 'meta name must be rendered' );
nvx_deploy_assert( false !== strpos( $output, 'content="' . NVX_TEST_DEPLOY_SHA . '"' ), 'meta content must match exact SHA' );

@unlink( $GLOBALS['nvx_test_theme_dir'] . '/.nvx-deploy-sha' );
@rmdir( $GLOBALS['nvx_test_theme_dir'] );

echo "PASS: deploy SHA PHP contract\n";
