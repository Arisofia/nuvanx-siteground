<?php
/**
 * Front page — content owns home markup (including video stage).
 * No legacy wrapper; presentation via global CSS + nvx-brand-home.
 *
 * @package NUVANX_Medical
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

get_footer();
