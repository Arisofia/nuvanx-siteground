<?php
/**
 * Template Name: Contacto
 *
 * Production pages historically assign this slug. The full NAP + form markup
 * lives in templates/template-contact.php so both template names render the
 * same experience (handler, hours, privacy, accessibility shell).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_template_directory() . '/templates/template-contact.php';
