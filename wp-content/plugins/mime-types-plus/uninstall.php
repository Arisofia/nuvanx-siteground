<?php
/**
 * Uninstall
 *
 * @package Mime Types Plus
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_name1 = 'mimetypesplus_settings';
$option_name2 = 'mimetypesplus';
$option_name3 = 'mimetypesplus_exts';

/* For Single site */
if ( ! is_multisite() ) {
	$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $blogusers as $user ) {
		delete_user_option( $user->ID, 'mimetypesplus_exts', false );
		delete_user_option( $user->ID, 'mimetypesplus_unset', false );
	}
	delete_option( $option_name1 );
	delete_option( $option_name2 );
	delete_option( $option_name3 );
} else {
	/* For Multisite */
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		$blogusers = get_users(
			array(
				'blog_id' => $blogid,
				'fields' => array( 'ID' ),
			)
		);
		foreach ( $blogusers as $user ) {
			delete_user_option( $user->ID, 'mimetypesplus_exts', false );
			delete_user_option( $user->ID, 'mimetypesplus_unset', false );
		}
		delete_option( $option_name1 );
		delete_option( $option_name2 );
		delete_option( $option_name3 );
	}
	switch_to_blog( $original_blog_id );

	/* For site options. */
	delete_site_option( $option_name1 );
	delete_site_option( $option_name2 );
	delete_site_option( $option_name3 );
}
