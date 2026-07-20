<?php
/**
 * Customer message placeholder replacement.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MessageFormatter
 *
 * Replaces {placeholder} tokens in message templates with escaped values.
 */
class MessageFormatter {

	/**
	 * Supported placeholder keys.
	 *
	 * @var string[]
	 */
	public const PLACEHOLDERS = array(
		'address_type',
		'original_address',
		'suggested_address',
		'field',
		'country',
		'postcode',
		'city',
		'rule_name',
		'provider',
		'validation_status',
	);

	/**
	 * Replace placeholders in a message template.
	 *
	 * @param string               $template Message template.
	 * @param array<string,string> $context  Placeholder values.
	 *
	 * @return string
	 */
	public static function format( string $template, array $context = array() ): string {
		$template = trim( $template );
		if ( '' === $template ) {
			return '';
		}

		$replacements = array();
		foreach ( self::PLACEHOLDERS as $key ) {
			$value = isset( $context[ $key ] ) ? (string) $context[ $key ] : '';
			$replacements[ '{' . $key . '}' ] = esc_html( $value );
		}

		return strtr( $template, $replacements );
	}

	/**
	 * Sample placeholder values for admin previews.
	 *
	 * @return array<string,string>
	 */
	public static function sample_context(): array {
		return array(
			'address_type'      => __( 'Shipping address', 'wpruby-address-checks-for-woocommerce' ),
			'original_address'  => '123 Main St, Springfield, IL 62701, US',
			'suggested_address' => '123 Main Street, Springfield, IL 62701, US',
			'field'             => __( 'Street address', 'wpruby-address-checks-for-woocommerce' ),
			'country'           => 'US',
			'postcode'          => '62701',
			'city'              => 'Springfield',
			'rule_name'         => __( 'No PO Boxes', 'wpruby-address-checks-for-woocommerce' ),
			'provider'          => 'google_address_validation',
			'validation_status' => __( 'Invalid', 'wpruby-address-checks-for-woocommerce' ),
		);
	}
}
