<?php
/**
 * WooCommerce country options for the admin app.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CountryOptions
 *
 * Builds normalized country option lists for Vue select components.
 */
class CountryOptions {

	/**
	 * Return country options for the admin app.
	 *
	 * @return array<int,array{value:string,label:string}>
	 */
	public static function for_app(): array {
		$countries = array();

		if ( function_exists( 'WC' ) && WC()->countries ) {
			foreach ( WC()->countries->get_countries() as $code => $label ) {
				$countries[] = array(
					'value' => (string) $code,
					'label' => (string) $label,
				);
			}
		}

		if ( ! empty( $countries ) ) {
			return $countries;
		}

		return self::fallback();
	}

	/**
	 * Small safe fallback when WooCommerce country data is unavailable.
	 *
	 * @return array<int,array{value:string,label:string}>
	 */
	private static function fallback(): array {
		return array(
			array(
				'value' => 'US',
				'label' => __( 'United States', 'wpruby-address-checks-for-woocommerce' ),
			),
			array(
				'value' => 'CA',
				'label' => __( 'Canada', 'wpruby-address-checks-for-woocommerce' ),
			),
			array(
				'value' => 'GB',
				'label' => __( 'United Kingdom', 'wpruby-address-checks-for-woocommerce' ),
			),
			array(
				'value' => 'DE',
				'label' => __( 'Germany', 'wpruby-address-checks-for-woocommerce' ),
			),
		);
	}
}
