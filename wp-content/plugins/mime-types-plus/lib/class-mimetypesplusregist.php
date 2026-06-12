<?php
/**
 * Mime Types Plus
 *
 * @package    Mime Types Plus
 * @subpackage MimeTypesPlusRegist registered in the database
/*
	Copyright (c) 2015- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mimetypesplusregist = new MimeTypesPlusRegist();

/** ==================================================
 * Registered in the database
 */
class MimeTypesPlusRegist {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.10
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'update_notice' ) );
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_option( 'mimetypesplus' ) || ! get_option( 'mimetypesplus_exts' ) ) {
			$extensions = array();
			$exts = array();
			$mimes = get_allowed_mime_types();
			foreach ( $mimes as $extselect => $mime ) {
				if ( strpos( $extselect, '|' ) ) {
					$extselects = explode( '|', $extselect );
					foreach ( $extselects as $extselect2 ) {
						$extensions[ wp_ext2type( $extselect2 ) ][ $extselect2 ] = $mime;
					}
				} else {
					$extensions[ wp_ext2type( $extselect ) ][ $extselect ] = $mime;
				}
				$exts[] = $extselect;
			}
			update_option( 'mimetypesplus', $extensions );
			$exts = array_unique( $exts );
			$exts = array_values( $exts );
			asort( $exts );
			update_option( 'mimetypesplus_exts', $exts );
		}

		$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
		foreach ( $blogusers as $user ) {
			if ( user_can( $user->ID, 'upload_files' ) ) {
				if ( ! get_user_option( 'mimetypesplus_exts', $user->ID ) ) {
					update_user_option( $user->ID, 'mimetypesplus_exts', get_option( 'mimetypesplus_exts' ) );
				}
			}
		}
	}

	/** ==================================================
	 * Update notice
	 *
	 * @since 2.00
	 */
	public function update_notice() {

		if ( get_option( 'mimetypesplus_settings' ) ) {

			$add = '<a style="text-decoration: none;" href="' . admin_url( 'admin.php?page=mimetypesplus-edit' ) . '">' . __( 'Add', 'mime-types-plus' ) . '</a>';
			?>
			<div class="notice notice-warning is-dismissible"><ul><li>
			<?php
			/* translators: Mime Type Add link */
			echo wp_kses_post( sprintf( __( 'Mime Types Plus needs to be reconfigured. Please %1$s the Mime Type again.', 'mime-types-plus' ), $add ) );
			?>
			</li></ul></div>
			<?php
		}
	}
}


