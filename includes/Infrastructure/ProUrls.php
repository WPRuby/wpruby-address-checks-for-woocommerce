<?php
/**
 * Outbound Address Guard Pro product URLs with UTM attribution.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProUrls
 *
 * Builds tagged product-page URLs for voluntary click-throughs from the free
 * plugin admin. Does not send telemetry from WordPress.
 */
class ProUrls {

	const UTM_SOURCE   = 'address_guard_free';
	const UTM_MEDIUM   = 'plugin_admin';
	const UTM_CAMPAIGN = 'free_to_pro';

	/**
	 * WordPress.org / readme referral parameters (static links only).
	 */
	const WPORG_UTM_SOURCE   = 'wordpress_org';
	const WPORG_UTM_MEDIUM   = 'referral';
	const WPORG_UTM_CAMPAIGN = 'address_guard_free';

	/**
	 * Build a Pro product URL for a plugin-admin CTA location.
	 *
	 * @param string $utm_content Descriptive CTA location slug (e.g. upgrade_tab).
	 *
	 * @return string Escaped URL safe for JSON/REST responses.
	 */
	public static function get( string $utm_content ): string {
		$content = sanitize_key( $utm_content );
		if ( '' === $content ) {
			$content = 'upgrade_tab';
		}

		return esc_url_raw(
			add_query_arg(
				array(
					'utm_source'   => self::UTM_SOURCE,
					'utm_medium'   => self::UTM_MEDIUM,
					'utm_campaign' => self::UTM_CAMPAIGN,
					'utm_content'  => $content,
				),
				WPRUBY_ADDRESS_CHECKS_PRO_URL
			)
		);
	}

	/**
	 * Build a Pro product URL for WordPress.org / readme contexts.
	 *
	 * Useful for tests and documentation; readme.txt embeds these statically.
	 *
	 * @param string $utm_content Descriptive CTA location slug (e.g. upgrade_to_pro).
	 *
	 * @return string Escaped URL.
	 */
	public static function for_wordpress_org( string $utm_content ): string {
		$content = sanitize_key( $utm_content );
		if ( '' === $content ) {
			$content = 'upgrade_to_pro';
		}

		return esc_url_raw(
			add_query_arg(
				array(
					'utm_source'   => self::WPORG_UTM_SOURCE,
					'utm_medium'   => self::WPORG_UTM_MEDIUM,
					'utm_campaign' => self::WPORG_UTM_CAMPAIGN,
					'utm_content'  => $content,
				),
				WPRUBY_ADDRESS_CHECKS_PRO_URL
			)
		);
	}
}
