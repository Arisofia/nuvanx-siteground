<?php
/**
 * Legacy alias for the shared blog archive partial.
 *
 * Prefer nvx-blog-archive.php. Kept so any historical include still resolves.
 *
 * @package nuvanx-medical
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_template_directory() . '/template-parts/content/nvx-blog-archive.php';
