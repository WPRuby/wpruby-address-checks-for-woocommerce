<?php
/**
 * Resolve autocomplete country bias/restriction from checkout and settings.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Autocomplete;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AutocompleteCountryContext
 *
 * Country context priority for autocomplete providers:
 * 1. Checkout selected country (customer choice wins over merchant preference).
 * 2. Autocomplete countries setting (merchant-configured bias when checkout country is empty).
 * 3. Provider region/country preference from provider config.
 * 4. Provider default behavior (no region restriction).
 */
class AutocompleteCountryContext {

	/**
	 * Resolve ISO region codes for provider restriction/bias.
	 *
	 * @param array<string,mixed> $context         Provider context from the REST endpoint.
	 * @param string              $provider_region Optional provider default region code.
	 *
	 * @return string[] Lowercase ISO 3166-1 alpha-2 region codes.
	 */
	public static function region_codes( array $context, string $provider_region = '' ): array {
		$checkout_country = strtoupper( sanitize_text_field( (string) ( $context['country'] ?? '' ) ) );
		if ( preg_match( '/^[A-Z]{2}$/', $checkout_country ) ) {
			return array( strtolower( $checkout_country ) );
		}

		$preferred = self::normalize_region_codes( (array) ( $context['preferred_countries'] ?? array() ) );
		if ( ! empty( $preferred ) ) {
			return $preferred;
		}

		$region = strtolower( sanitize_text_field( $provider_region ) );
		if ( preg_match( '/^[a-z]{2}$/', $region ) ) {
			return array( $region );
		}

		return array();
	}

	/**
	 * Resolve a single country code for providers that accept one country parameter.
	 *
	 * @param array<string,mixed> $context         Provider context from the REST endpoint.
	 * @param string              $provider_region Optional provider default region code.
	 *
	 * @return string Uppercase ISO 3166-1 alpha-2 country code, or empty string.
	 */
	public static function primary_country( array $context, string $provider_region = '' ): string {
		$regions = self::region_codes( $context, $provider_region );

		return '' !== ( $regions[0] ?? '' ) ? strtoupper( (string) $regions[0] ) : '';
	}

	/**
	 * Normalize a list of country codes to lowercase ISO alpha-2 values.
	 *
	 * @param array<int,mixed> $codes Raw country codes.
	 *
	 * @return string[]
	 */
	private static function normalize_region_codes( array $codes ): array {
		$regions = array();

		foreach ( $codes as $code ) {
			$code = strtolower( sanitize_text_field( (string) $code ) );
			if ( preg_match( '/^[a-z]{2}$/', $code ) ) {
				$regions[] = $code;
			}
		}

		return array_values( array_unique( $regions ) );
	}
}
