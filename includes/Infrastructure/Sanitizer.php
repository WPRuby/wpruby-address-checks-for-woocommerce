<?php
/**
 * Input sanitization helpers.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sanitizer
 *
 * Centralised sanitization for settings inputs.
 */
class Sanitizer {

	/**
	 * Sanitize a yes/no checkbox value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string yes|no
	 */
	public static function checkbox( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}

		if ( is_numeric( $value ) ) {
			return (int) $value ? 'yes' : 'no';
		}

		$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

		return in_array( $value, array( 'yes', 'true', '1', 'on' ), true ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize a validation mode.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function validation_mode( $value ): string {
		$value = is_string( $value ) ? sanitize_key( $value ) : 'warn';

		return in_array( $value, Settings::VALIDATION_MODES, true ) ? $value : 'warn';
	}

	/**
	 * Sanitize a credential / API key string.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function credential( $value ): string {
		$value = is_string( $value ) ? trim( $value ) : '';
		$value = preg_replace( '/[\r\n\t]+/', '', $value );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Sanitize a list of ISO country codes.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string[]
	 */
	public static function country_codes( $value ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$codes = array();
		foreach ( $value as $code ) {
			$code = strtoupper( sanitize_text_field( (string) $code ) );
			if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
				$codes[] = $code;
			}
		}

		return array_values( array_unique( $codes ) );
	}

	/**
	 * Sanitize message templates.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return array<string,string>
	 */
	public static function messages( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();
		foreach ( $value as $key => $message ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}

			$clean[ $key ] = sanitize_textarea_field( (string) $message );
		}

		return $clean;
	}
}
