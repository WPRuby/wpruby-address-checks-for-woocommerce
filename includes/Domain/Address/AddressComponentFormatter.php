<?php
/**
 * Country-aware street address formatting from provider components.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Address;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AddressComponentFormatter
 */
class AddressComponentFormatter {

	/**
	 * Countries that typically place the street number before the route.
	 *
	 * @var string[]
	 */
	private const NUMBER_FIRST_COUNTRIES = array( 'US', 'CA', 'GB', 'AU', 'NZ' );

	/**
	 * Countries that typically place the route before the street number.
	 *
	 * @var string[]
	 */
	private const ROUTE_FIRST_COUNTRIES = array( 'DE', 'AT', 'CH', 'NL', 'BE', 'DK', 'SE', 'NO', 'FI', 'FR', 'ES', 'IT' );

	/**
	 * Format address line 1 from street number and route components.
	 *
	 * @param string               $street_number Street number component.
	 * @param string               $route         Route component.
	 * @param string               $country       ISO 3166-1 alpha-2 country code.
	 * @param array<string,string> $components    Optional indexed provider components.
	 *
	 * @return string
	 */
	public function format_street_line( string $street_number, string $route, string $country, array $components = array() ): string {
		$street_number = trim( $street_number );
		$route         = trim( $route );
		$country       = strtoupper( trim( $country ) );

		if ( '' === $street_number && '' === $route ) {
			return '';
		}

		if ( '' === $street_number ) {
			return $route;
		}

		if ( '' === $route ) {
			return $street_number;
		}

		$format = $this->should_put_number_first( $country )
			? trim( $street_number . ' ' . $route )
			: trim( $route . ' ' . $street_number );

		$components = array_merge(
			$components,
			array(
				'street_number' => $street_number,
				'route'         => $route,
			)
		);

		/**
		 * Filter the formatted street address line.
		 *
		 * @param string               $format     Formatted street line.
		 * @param string               $country    ISO country code.
		 * @param array<string,string> $components Provider address components.
		 */
		return (string) apply_filters( 'address_guard_street_line_format', $format, $country, $components );
	}

	/**
	 * Whether the street number should appear before the route for a country.
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool
	 */
	public function should_put_number_first( string $country ): bool {
		$country = strtoupper( trim( $country ) );

		if ( in_array( $country, self::ROUTE_FIRST_COUNTRIES, true ) ) {
			return false;
		}

		if ( in_array( $country, self::NUMBER_FIRST_COUNTRIES, true ) ) {
			return true;
		}

		/**
		 * Filter whether the street number should appear before the route.
		 *
		 * @param bool   $number_first Default based on built-in country lists.
		 * @param string $country      ISO country code.
		 */
		return (bool) apply_filters( 'address_guard_street_number_first', true, $country );
	}
}
