<?php
/**
 * Mime Types Plus
 *
 * @package    Mime Types Plus
 * @subpackage MimeTypesPlusAdmin Management screen
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

$mimetypesplusadmin = new MimeTypesPlusAdmin();

/** ==================================================
 * Management screen
 */
class MimeTypesPlusAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.10
	 */
	public function __construct() {

		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'mime-types-plus/mimetypesplus.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=mimetypesplus' ) . '">Mime Types Plus</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mimetypesplus-edit' ) . '">' . __( 'Edit Mime Type', 'mime-types-plus' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mimetypesplus-users-edit' ) . '">' . __( 'Edit Users', 'mime-types-plus' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 2.00
	 */
	public function add_pages() {
		add_menu_page(
			'Mime Types Plus',
			'Mime Types Plus',
			'manage_options',
			'mimetypesplus',
			array( $this, 'manage_page' ),
			'dashicons-upload'
		);
		add_submenu_page(
			'mimetypesplus',
			__( 'Edit Mime Type', 'mime-types-plus' ),
			__( 'Edit Mime Type', 'mime-types-plus' ),
			'manage_options',
			'mimetypesplus-edit',
			array( $this, 'edit_mimetype' )
		);
		add_submenu_page(
			'mimetypesplus',
			__( 'Edit Users', 'mime-types-plus' ),
			__( 'Edit Users', 'mime-types-plus' ),
			'manage_options',
			'mimetypesplus-users-edit',
			array( $this, 'edit_users' )
		);
	}

	/** ==================================================
	 * Edit mimetype
	 *
	 * @since 2.00
	 */
	public function edit_mimetype() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mime-types-plus' ) );
		}

		$this->options_updated();

		$scriptname = admin_url( 'admin.php?page=mimetypesplus-edit' );
		$extentions = get_option( 'mimetypesplus' );

		?>
		<div class="wrap">
			<h2>Mime Types Plus
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mimetypesplus-users-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Users', 'mime-types-plus' ); ?></a>
			</h2>
			<div style="clear: both;"></div>

			<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'mtp_add', 'mimetypesplus_add' ); ?>
				<h3><?php esc_html_e( 'Add Mime Type', 'mime-types-plus' ); ?></h3>
				<div style="padding-top: 5px; padding-bottom: 5px;">
				<?php esc_html_e( 'Extension', 'mime-types-plus' ); ?>:
				<input type="text" name="ext" size="4" />
				</div>
				<div style="padding-top: 5px; padding-bottom: 5px;">
				<?php esc_html_e( 'Mime Type', 'mime-types-plus' ); ?>:
				<input type="text" name="mime" />
				</div>

				<div style="padding-top: 5px; padding-bottom: 5px;">
				<?php esc_html_e( 'File type:', 'mime-types-plus' ); ?>
				<select name="type">
				<option value=""><?php esc_html_e( 'Select', 'mime-types-plus' ); ?></option>
				<option value="image">image</option>
				<option value="audio">audio</option>
				<option value="video">video</option>
				<option value="document">document</option>
				<option value="spreadsheet">spreadsheet</option>
				<option value="interactive">interactive</option>
				<option value="text">text</option>
				<option value="archive">archive</option>
				<option value="code">code</option>
				</select>
				</div>

				<?php submit_button( __( 'Add', 'mime-types-plus' ), 'primary', 'MimeAdd', true ); ?>

			</form>

			<hr>

			<h3><?php esc_html_e( 'Edit Mime Type', 'mime-types-plus' ); ?></h3>
			<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'mtp_upmime', 'mimetypesplus_updatemime' ); ?>
				<?php submit_button( __( 'Update Mime Type', 'mime-types-plus' ), 'primary', 'UpdateMime', true ); ?>
				<table border=1 cellspacing="0" cellpadding="5" bordercolor="#000000" style="table-layout: fixed; border-collapse: collapse;">
				<thead>
				<th><?php esc_html_e( 'Delete', 'mime-types-plus' ); ?></th>
				<th style="text-align: left; width: 80px;"><?php echo esc_html( __( 'Extension', 'mime-types-plus' ) . ' : ' . __( 'Mime Types', 'mime-types-plus' ) ); ?></th>
				<th style="width: 40px;"><?php esc_html_e( 'File type:', 'mime-types-plus' ); ?></th>
				</thead>
				<?php
				foreach ( $extentions as $type => $extmimes ) {
					foreach ( $extmimes as $ext => $mimetype ) {
						?>
						<tr>
						<td style="text-align: center;"><input type="checkbox" name="exts[]" value="<?php echo esc_attr( $ext ); ?>"</td>
						<td style="word-wrap: break-word;"><?php echo esc_html( $ext . ' : ' . $mimetype ); ?></td>
						<td>
						<select name="types[<?php echo esc_attr( $ext ); ?>]">
						<option value=""><?php esc_html_e( 'Select', 'mime-types-plus' ); ?></option>
						<option value="image" 
						<?php
						if ( 'image' == $type ) {
							echo 'selected="selected"';}
						?>
						>image</option>
						<option value="audio" 
						<?php
						if ( 'audio' == $type ) {
							echo 'selected="selected"';}
						?>
						>audio</option>
						<option value="video" 
						<?php
						if ( 'video' == $type ) {
							echo 'selected="selected"';}
						?>
						>video</option>
						<option value="document" 
						<?php
						if ( 'document' == $type ) {
							echo 'selected="selected"';}
						?>
						>document</option>
						<option value="spreadsheet" 
						<?php
						if ( 'spreadsheet' == $type ) {
							echo 'selected="selected"';}
						?>
						>spreadsheet</option>
						<option value="interactive" 
						<?php
						if ( 'interactive' == $type ) {
							echo 'selected="selected"';}
						?>
						>interactive</option>
						<option value="text" 
						<?php
						if ( 'text' == $type ) {
							echo 'selected="selected"';}
						?>
						>text</option>
						<option value="archive" 
						<?php
						if ( 'archive' == $type ) {
							echo 'selected="selected"';}
						?>
						>archive</option>
						<option value="code" 
						<?php
						if ( 'code' == $type ) {
							echo 'selected="selected"';}
						?>
						>code</option>
						</select>
						</td>
						</tr>
						<?php
					}
				}
				?>
				</table>
				<?php submit_button( __( 'Update Mime Type', 'mime-types-plus' ), 'primary', 'UpdateMime', true ); ?>
			</form>

		</div>
		<?php
	}

	/** ==================================================
	 * Settings users page
	 *
	 * @since 2.00
	 */
	public function edit_users() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mime-types-plus' ) );
		}

		$this->options_updated();

		$userexts_id = get_current_user_id();
		if ( isset( $_POST['UserSelect'] ) && ! empty( $_POST['UserSelect'] ) ||
				isset( $_POST['UserExts'] ) && ! empty( $_POST['UserExts'] ) ||
				isset( $_POST['UserDefault'] ) && ! empty( $_POST['UserDefault'] ) ) {
			if ( check_admin_referer( 'mtp_eu', 'mimetypesplus_edit_users' ) ) {
				if ( isset( $_POST['userexts_id'] ) && ! empty( $_POST['userexts_id'] ) ) {
					$userexts_id = absint( $_POST['userexts_id'] );
				}
			}
		}

		$scriptname = admin_url( 'admin.php?page=mimetypesplus-users-edit' );

		?>
		<div class="wrap">
			<h2>Mime Types Plus
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mimetypesplus-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Mime Type', 'mime-types-plus' ); ?></a>
			</h2>
			<div style="clear: both;"></div>

			<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'mtp_eu', 'mimetypesplus_edit_users' ); ?>
				<h3><?php esc_html_e( 'Specify uploadable and non-uploadable extensions for each user', 'mime-types-plus' ); ?></h3>
				<div style="padding-top: 5px; padding-bottom: 5px;">
				<select name="userexts_id">
				<?php
				$blogusers = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
				foreach ( $blogusers as $user ) {
					if ( user_can( $user->ID, 'upload_files' ) ) {
						?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" 
						<?php
						if ( $userexts_id == $user->ID ) {
							echo 'selected="selected"';}
						?>
						><?php echo esc_html( $user->display_name ); ?></option>
						<?php
					}
				}
				?>
				</select>
				<?php submit_button( __( 'Select', 'mime-types-plus' ), 'large', 'UserSelect', false ); ?>
				<table border=1 cellspacing="0" cellpadding="5" bordercolor="#000000" style="border-collapse: collapse;">
				<thead>
				<tr>
				<th colspan="8">
				<?php esc_html_e( 'Uploadable', 'mime-types-plus' ); ?>
				</th>
				</tr>
				</thead>
				<?php
				$col_count = 0;
				$extsets = get_user_option( 'mimetypesplus_exts', $userexts_id );
				foreach ( $extsets as $ext ) {
					++$col_count;
					if ( 1 == $col_count ) {
						?>
						<tr>
						<?php
					}
					?>
					<td>
					<input type="checkbox" name="exts[]" value="<?php echo esc_attr( $ext ); ?>"><?php echo esc_html( $ext ); ?>
					</td>
					<?php
					if ( 8 == $col_count ) {
						?>
						</tr>
						<?php
						$col_count = 0;
					}
				}
				if ( 0 < $col_count ) {
					?>
					</tr>
					<?php
				}
				$unsets = get_user_option( 'mimetypesplus_unset', $userexts_id );
				if ( ! empty( $unsets ) ) {
					?>
					<th colspan="8">
					<?php esc_html_e( 'Non-Uploadable', 'mime-types-plus' ); ?>
					</th>
					<?php
					$col_count = 0;
					foreach ( $unsets as $unset_ext ) {
						++$col_count;
						if ( 1 == $col_count ) {
							?>
							<tr>
							<?php
						}
						?>
						<td>
						<input type="checkbox" name="unset_exts[]" value="<?php echo esc_attr( $unset_ext ); ?>"><?php echo esc_html( $unset_ext ); ?>
						</td>
						<?php
						if ( 8 == $col_count ) {
							?>
							</tr>
							<?php
							$col_count = 0;
						}
					}
					if ( 0 < $col_count ) {
						?>
						</tr>
						<?php
					}
				}
				?>
				</table>
				</div>
				<?php submit_button( __( 'Settings', 'mime-types-plus' ), 'primary', 'UserExts', false ); ?>&nbsp;&nbsp;
				<?php submit_button( __( 'Default', 'mime-types-plus' ), 'primary', 'UserDefault', false ); ?>
			</form>

		</div>
		<?php
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mime-types-plus' ) );
		}

		?>

		<div class="wrap">

		<h2>Mime Types Plus
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mimetypesplus-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Mime Type', 'mime-types-plus' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mimetypesplus-users-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Users', 'mime-types-plus' ); ?></a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Add the mime type that can be used in the media library to each file type', 'mime-types-plus' ); ?></h3>

		<?php $this->credit(); ?>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:', 'mime-types-plus' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'mime-types-plus' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'mime-types-plus' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s', 'mime-types-plus' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'mime-types-plus' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;', 'mime-types-plus' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( ! empty( $_POST ) ) {

			/* Edit User */
			if ( isset( $_POST['UserExts'] ) && ! empty( $_POST['UserExts'] ) ) {
				if ( check_admin_referer( 'mtp_eu', 'mimetypesplus_edit_users' ) ) {
					if ( isset( $_POST['userexts_id'] ) && ! empty( $_POST['userexts_id'] ) ) {
						$userid = absint( $_POST['userexts_id'] );
						if ( isset( $_POST['exts'] ) && ! empty( $_POST['exts'] ) ) {
							$exts = filter_var(
								wp_unslash( $_POST['exts'] ),
								FILTER_CALLBACK,
								array(
									'options' => function ( $value ) {
										return sanitize_text_field( $value );
									},
								)
							);

							$extsets = get_user_option( 'mimetypesplus_exts', $userid );
							$extsets = array_diff( $extsets, $exts );
							asort( $extsets );
							update_user_option( $userid, 'mimetypesplus_exts', $extsets );

							$unsets = get_user_option( 'mimetypesplus_unset', $userid );
							if ( ! empty( $unsets ) ) {
								$unsets = array_merge( $unsets, $exts );
								asort( $unsets );
							} else {
								$unsets = $exts;
							}
							update_user_option( $userid, 'mimetypesplus_unset', $unsets );
						}
						if ( isset( $_POST['unset_exts'] ) && ! empty( $_POST['unset_exts'] ) ) {
							$unset_exts = filter_var(
								wp_unslash( $_POST['unset_exts'] ),
								FILTER_CALLBACK,
								array(
									'options' => function ( $value ) {
										return sanitize_text_field( $value );
									},
								)
							);

							$extsets = get_user_option( 'mimetypesplus_exts', $userid );
							$extsets = array_merge( $extsets, $unset_exts );
							asort( $extsets );
							update_user_option( $userid, 'mimetypesplus_exts', $extsets );

							$unsets = get_user_option( 'mimetypesplus_unset', $userid );
							$unsets = array_diff( $unsets, $unset_exts );
							asort( $unsets );
							update_user_option( $userid, 'mimetypesplus_unset', $unsets );
						}
					}
				}
			}

			/* Default User */
			if ( isset( $_POST['UserDefault'] ) && ! empty( $_POST['UserDefault'] ) ) {
				if ( check_admin_referer( 'mtp_eu', 'mimetypesplus_edit_users' ) ) {
					if ( isset( $_POST['userexts_id'] ) && ! empty( $_POST['userexts_id'] ) ) {
						$userid = absint( $_POST['userexts_id'] );
						update_user_option( $userid, 'mimetypesplus_exts', get_option( 'mimetypesplus_exts' ) );
						delete_user_option( $userid, 'mimetypesplus_unset' );
					}
				}
			}

			/* Edit Mime */
			if ( isset( $_POST['UpdateMime'] ) && ! empty( $_POST['UpdateMime'] ) ) {
				if ( check_admin_referer( 'mtp_upmime', 'mimetypesplus_updatemime' ) ) {

					/* Delete Mime */
					if ( isset( $_POST['exts'] ) && ! empty( $_POST['exts'] ) ) {
						$exts = filter_var(
							wp_unslash( $_POST['exts'] ),
							FILTER_CALLBACK,
							array(
								'options' => function ( $value ) {
									return sanitize_text_field( $value );
								},
							)
						);

						$update_count = 0;
						$extentions = get_option( 'mimetypesplus' );
						foreach ( $extentions as $type => $extmimes ) {
							foreach ( $extmimes as $ext => $mimetype ) {
								if ( in_array( $ext, $exts ) ) {
									unset( $extentions[ $type ][ $ext ] );
									++$update_count;
								}
							}
						}

						if ( 0 < $update_count ) {
							update_option( 'mimetypesplus', $extentions );

							$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
							foreach ( $blogusers as $user ) {
								if ( user_can( $user->ID, 'upload_files' ) ) {
									if ( get_user_option( 'mimetypesplus_exts', $user->ID ) ) {
										$extsets = get_user_option( 'mimetypesplus_exts', $user->ID );
										$extsets = array_diff( $extsets, $exts );
										asort( $extsets );
										update_user_option( $user->ID, 'mimetypesplus_exts', $extsets );

										$unsets = get_user_option( 'mimetypesplus_unset', $user->ID );
										if ( ! empty( $unsets ) ) {
											$unsets = array_diff( $unsets, $exts );
											asort( $unsets );
											update_user_option( $user->ID, 'mimetypesplus_unset', $unsets );
										}
									}
								}
							}
							echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Mime Type', 'mime-types-plus' ) . ' --> ' . __( 'Delete', 'mime-types-plus' ) ) . '</li></ul></div>';
						}
					}

					/* Edit type */
					if ( isset( $_POST['types'] ) && ! empty( $_POST['types'] ) ) {
						$types = filter_var(
							wp_unslash( $_POST['types'] ),
							FILTER_CALLBACK,
							array(
								'options' => function ( $value ) {
									return sanitize_text_field( $value );
								},
							)
						);
						$update_count = 0;
						$extentions = get_option( 'mimetypesplus' );
						foreach ( $extentions as $type => $extmimes ) {
							foreach ( $extmimes as $ext => $mimetype ) {
								foreach ( $types as $ext2 => $type2 ) {
									if ( $ext2 === $ext && $type <> $type2 ) {
										unset( $extentions[ $type ][ $ext ] );
										$extentions[ $type2 ][ $ext ] = $mimetype;
										++$update_count;
									}
								}
							}
						}
						if ( 0 < $update_count ) {
							update_option( 'mimetypesplus', $extentions );
							echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'File type:', 'mime-types-plus' ) . ' --> ' . __( 'Update', 'mime-types-plus' ) ) . '</li></ul></div>';
						}
					}
				}
			}

			/* Add Mime */
			if ( isset( $_POST['MimeAdd'] ) && ! empty( $_POST['MimeAdd'] ) ) {
				if ( check_admin_referer( 'mtp_add', 'mimetypesplus_add' ) ) {
					$ext = null;
					$mime = null;
					$type = null;
					if ( isset( $_POST['ext'] ) && ! empty( $_POST['ext'] ) ) {
						$ext = sanitize_text_field( wp_unslash( $_POST['ext'] ) );
					}
					if ( isset( $_POST['mime'] ) && ! empty( $_POST['mime'] ) ) {
						$mime = sanitize_text_field( wp_unslash( $_POST['mime'] ) );
					}
					if ( isset( $_POST['type'] ) && ! empty( $_POST['type'] ) ) {
						$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
					}
					if ( is_null( $ext ) || is_null( $mime ) || is_null( $type ) ) {
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'The input data is not enough.', 'mime-types-plus' ) . '</li></ul></div>';
						return;
					}

					$is_mime = false;
					$mime_types = get_option( 'mimetypesplus' );
					foreach ( $mime_types as $types ) {
						if ( array_key_exists( $ext, $types ) ) {
							$is_mime = true;
						}
					}
					if ( ! $is_mime ) {
						$mime_types[ $type ][ $ext ] = $mime;
						update_option( 'mimetypesplus', $mime_types );

						$blogusers = get_users( array( 'fields' => array( 'ID' ) ) );
						foreach ( $blogusers as $user ) {
							if ( user_can( $user->ID, 'upload_files' ) ) {
								if ( get_user_option( 'mimetypesplus_exts', $user->ID ) ) {
									$extsets = get_user_option( 'mimetypesplus_exts', $user->ID );
									$extsets[] = $ext;
									asort( $extsets );
									update_user_option( $user->ID, 'mimetypesplus_exts', $extsets );
								}
							}
						}

						echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Mime Type has been added.', 'mime-types-plus' ) ) . '</li></ul></div>';
						/* Remove old option */
						if ( get_option( 'mimetypesplus_settings' ) ) {
							delete_option( 'mimetypesplus_settings' );
						}
					} else {
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html( __( 'Mime Type already exists.', 'mime-types-plus' ) ) . '</li></ul></div>';
					}
				}
			}
		}
	}
}


