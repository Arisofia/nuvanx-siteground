<?php
/**
 * Restore HubSpot analytics cookies to consent-gated categories.
 *
 * The valoración form stays usable via the first-party form and the hsforms
 * embed whitelist. Tracking cookies (__hstc, hubspotutk, session pair) must
 * not be forced Functional.
 *
 * Run:
 *   wp eval-file tools/migrations/split-hubspot-form-vs-tracking-cookies.php --allow-root
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval-file.\n" );
	exit( 1 );
}

global $wpdb;

$cookies  = $wpdb->prefix . 'cmplz_cookies';
$services = $wpdb->prefix . 'cmplz_services';

$updated_cookies  = 0;
$updated_services = 0;

$map = array(
	array(
		'names' => array( 'hubspotutk', '__hstc' ),
		'en'    => 'Marketing',
		'es'    => 'Marketing',
	),
	array(
		'names' => array( '__hssc', '__hssrc' ),
		'en'    => 'Statistics',
		'es'    => 'Estadísticas',
	),
	array(
		'names' => array( 'hs-messages-is-open', 'messagesUtk' ),
		'en'    => 'Preferences',
		'es'    => 'Preferencias',
	),
);

foreach ( $map as $rule ) {
	foreach ( $rule['names'] as $name ) {
		foreach ( array( 'en', 'es' ) as $lang ) {
			$purpose = 'en' === $lang ? $rule['en'] : $rule['es'];
			$count   = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$cookies}
					 SET purpose = %s
					 WHERE deleted = 0 AND language = %s AND name = %s",
					$purpose,
					$lang,
					$name
				)
			);
			if ( is_int( $count ) ) {
				$updated_cookies += $count;
			}
		}
	}
}

$updated_services += (int) $wpdb->query(
	"UPDATE {$services}
	 SET category = '',
	     serviceType = CASE
	       WHEN language = 'es' THEN 'marketing automatizado (marketing por email automático)'
	       ELSE 'marketing automation (automated email marketing)'
	     END
	 WHERE slug = 'hubspot'"
);

update_option( 'cmplz_generate_new_cookiepolicy_snapshot', true );
update_option( 'cmplz_documents_update_date', time() );
update_option( 'cmplz_cookie_data_verified_date', time() );
update_option( 'nvx_cmplz_hubspot_split_20260817', time() );

printf(
	"HUBSPOT_COOKIE_SPLIT=PASS cookies=%d services=%d site=%s\n",
	$updated_cookies,
	$updated_services,
	home_url( '/' )
);
