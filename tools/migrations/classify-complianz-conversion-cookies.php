<?php
/**
 * Classify Complianz cookies/services that currently block conversion or GDPR policy.
 *
 * Run:
 *   wp eval-file tools/migrations/classify-complianz-conversion-cookies.php --allow-root
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
$banners  = $wpdb->prefix . 'cmplz_cookiebanners';

$service_ids = array();
$rows        = $wpdb->get_results( "SELECT ID, name, slug, language FROM {$services}", ARRAY_A );
foreach ( (array) $rows as $row ) {
	$key = strtolower( (string) $row['slug'] ) . '|' . strtolower( (string) $row['language'] );
	$service_ids[ $key ] = (int) $row['ID'];
}

$service = static function ( string $slug, string $lang ) use ( $service_ids ): int {
	return $service_ids[ $slug . '|' . $lang ] ?? 0;
};

$updated_cookies  = 0;
$updated_services = 0;
$deleted_services = 0;

/**
 * @param array<int,array{names:array<int,string>,en:string,es:string,service:string}> $map
 */
$apply_cookie_map = static function ( array $map ) use ( $wpdb, $cookies, $service, &$updated_cookies ): void {
	foreach ( $map as $rule ) {
		foreach ( $rule['names'] as $name ) {
			foreach ( array( 'en', 'es' ) as $lang ) {
				$sid = $service( $rule['service'], $lang );
				$purpose = 'en' === $lang ? $rule['en'] : $rule['es'];
				$count = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$cookies}
						 SET purpose = %s, serviceID = CASE WHEN %d > 0 THEN %d ELSE serviceID END, showOnPolicy = 1
						 WHERE deleted = 0 AND language = %s AND name = %s",
						$purpose,
						$sid,
						$sid,
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
};

$apply_cookie_map(
	array(
		array(
			'names'   => array( 'hubspotutk', '__hssrc', '__hssc', '__hstc', 'hs-messages-is-open', 'messagesUtk' ),
			'en'      => 'Functional',
			'es'      => 'Funcional',
			'service' => 'hubspot',
		),
		array(
			'names'   => array( '__kl_key', '_kx', 'klaviyoOnsite', 'klaviyoPagesVisitCountV2', '__kla_id' ),
			'en'      => 'Marketing',
			'es'      => 'Marketing',
			'service' => 'klaviyo',
		),
		array(
			'names'   => array( '$last_referrer', '$referrer', 'multiFbc', '*_state', 'lastExternalReferrer', 'lastExternalReferrerTime' ),
			'en'      => 'Marketing',
			'es'      => 'Marketing',
			'service' => 'facebook',
		),
		array(
			'names'   => array( 'f' ),
			'en'      => 'Marketing',
			'es'      => 'Marketing',
			'service' => 'tiktok',
		),
		array(
			'names'   => array( 'joinchat_country_code' ),
			'en'      => 'Functional',
			'es'      => 'Funcional',
			'service' => 'whatsapp',
		),
		array(
			'names'   => array( 'wp_consent_functional', 'wp_consent_marketing', 'wp_consent_preferences', 'wp_consent_statistics', 'wp_consent_statistics-anonymous' ),
			'en'      => 'Functional',
			'es'      => 'Funcional',
			'service' => 'complianz',
		),
		array(
			'names'   => array( 'wp-settings-time-1' ),
			'en'      => 'Functional',
			'es'      => 'Funcional',
			'service' => 'wordpress',
		),
	)
);

$updated_services += (int) $wpdb->query(
	"UPDATE {$services}
	 SET category = 'functional',
	     serviceType = CASE
	       WHEN language = 'es' THEN 'formulario de contacto (funcional)'
	       ELSE 'contact form (functional)'
	     END
	 WHERE slug = 'hubspot'"
);

$deleted_services += (int) $wpdb->query(
	"DELETE FROM {$services} WHERE slug IN ('woocommerce', 'divi-elegant-themes')"
);

$banner_updated = $wpdb->query(
	"UPDATE {$banners} SET position = 'bottom' WHERE archived = 0 AND position = 'bottom-right'"
);

update_option( 'cmplz_generate_new_cookiepolicy_snapshot', true );
update_option( 'cmplz_documents_update_date', time() );
update_option( 'cmplz_cookie_data_verified_date', time() );
update_option( 'nvx_cmplz_cookie_classified_20260817', time() );

if ( function_exists( 'cmplz_update_all_banners' ) ) {
	cmplz_update_all_banners();
}

printf(
	"COMPLIANZ_CLASSIFY=PASS cookies=%d services=%d deleted_services=%d banner=%s site=%s\n",
	$updated_cookies,
	$updated_services,
	$deleted_services,
	is_int( $banner_updated ) && $banner_updated > 0 ? 'bottom' : 'unchanged',
	home_url( '/' )
);
