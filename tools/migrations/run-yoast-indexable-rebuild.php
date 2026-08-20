<?php
/**
 * Runs Yoast's full indexables rebuild after WordPress has loaded.
 *
 * WP-CLI does not load the active theme in every command context, so the
 * controlled Yoast filter must be registered in this post-load process before
 * the `yoast index` subcommand executes. This file is invoked only by the
 * guarded deployment pipelines with the explicit environment flag.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'NVX_ALLOW_STAGING_YOAST_INDEXABLE_REBUILD' ) ) {
	fwrite( STDERR, "YOAST_INDEXABLE_REBUILD=FAIL reason=guarded_wp_cli_bypass_required\n" );
	exit( 1 );
}

if ( ! class_exists( 'WP_CLI' ) || ! method_exists( 'WP_CLI', 'runcommand' ) ) {
	fwrite( STDERR, "YOAST_INDEXABLE_REBUILD=FAIL reason=wp_cli_runcommand_unavailable\n" );
	exit( 1 );
}

add_filter(
	'Yoast\\WP\\SEO\\should_index_indexables',
	static function (): bool {
		return true;
	},
	PHP_INT_MAX
);

WP_CLI::log( 'YOAST_INDEXABLE_REBUILD=START mode=guarded_same_process' );
WP_CLI::runcommand(
	'yoast index --reindex --skip-confirmation --allow-root',
	array(
		'launch'     => false,
		'exit_error' => true,
		'return'     => false,
	)
);
WP_CLI::log( 'YOAST_INDEXABLE_REBUILD=PASS mode=guarded_same_process' );
