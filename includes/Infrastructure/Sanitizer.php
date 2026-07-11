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
