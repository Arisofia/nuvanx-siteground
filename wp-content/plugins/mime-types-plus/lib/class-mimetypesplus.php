<?php
/**
 * Mime Types Plus
 *
 * @package    Mime Types Plus
 * @subpackage MimeTypesPlus Main Functions
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

$mimetypesplus = new MimeTypesPlus();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class MimeTypesPlus {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.10
	 */
	public function __construct() {

		add_filter( 'ext2type', array( $this, 'custom_ext2type' ) );
		add_filter( 'upload_mimes', array( $this, 'custom_upload_mimes' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'allow_upload_extension' ), 10, 4 );
	}

	/** ==================================================
	 * Custom mimes
	 *
	 * @param array $mime_types  mime_types.
	 * @return array $mime_types
	 * @since 1.00
	 */
	public function custom_upload_mimes( $mime_types ) {

		$def_mime_types   = get_option( 'mimetypesplus' );
		$unset_mime_types = get_user_option( 'mimetypesplus_unset', get_current_user_id() );
		$set_mime_types   = get_user_option( 'mimetypesplus_exts', get_current_user_id() );

		if ( ! empty( $set_mime_types ) ) {
			foreach ( $set_mime_types as $key ) {
				foreach ( $def_mime_types as $types ) {
					foreach ( $types as $ext => $mimetype ) {
						if ( $ext === $key ) {
							$mime_types[ $key ] = $mimetype;
						}
					}
				}
			}
		}

		if ( ! empty( $unset_mime_types ) ) {
			foreach ( $unset_mime_types as $key ) {
				unset( $mime_types[ $key ] );
			}
		}

		return $mime_types;
	}

	/** ==================================================
	 * Custom exe2type
	 *
	 * @param array $stack_ext2type  stack_ext2type.
	 * @return array $stack_ext2type
	 * @since 1.00
	 */
	public function custom_ext2type( $stack_ext2type ) {

		$mime_types = get_option( 'mimetypesplus' );
		if ( ! empty( $mime_types ) ) {
			$types_all = array_keys( $mime_types );
			foreach ( $mime_types as $type1 => $types ) {
				foreach ( $types as $ext => $mimetype ) {
					foreach ( $types_all as $type2 ) {
						if ( $type1 === $type2 ) {
							$stack_ext2type[ $type2 ][] = $ext;
						}
					}
				}
			}
		}

		return $stack_ext2type;
	}

	/** ==================================================
	 * Allow extension
	 *
	 * @param array  $data  data.
	 * @param string $file  file.
	 * @param string $filename  filename.
	 * @param array  $mimes  mimes.
	 * @return array $data
	 * @since 2.01
	 */
	public function allow_upload_extension( $data, $file, $filename, $mimes ) {

		if ( ! empty( $data ) ) {
			$filetype = wp_check_filetype( $filename );
			$data['ext'] = $filetype['ext'];
			$data['type'] = $filetype['type'];
		}

		return $data;
	}
}
