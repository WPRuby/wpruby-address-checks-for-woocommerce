<?php
/**
 * Map provider administrative area values to WooCommerce state codes.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Address;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProviderStateMapper
 */
class ProviderStateMapper {

	/**
	 * Map a provider state value to a WooCommerce-compatible state code.
	 *
	 * @param string $country        ISO 3166-1 alpha-2 country code.
	 * @param string $provider_state Provider administrative area value.
	 *
	 * @return string
	 */
	public function map_provider_state_to_woocommerce_state( string $country, string $provider_state ): string {
		$country        = strtoupper( trim( $country ) );
		$provider_state = trim( $provider_state );

		if ( '' === $provider_state || '' === $country ) {
			return '';
		}

		$states = $this->woocommerce_states( $country );

		$normalized = $this->normalize_iso_subdivision_code( $country, $provider_state );
		if ( '' !== $normalized && ( empty( $states ) || isset( $states[ $normalized ] ) ) ) {
			return $normalized;
		}

		$alias_code = $this->state_alias_code( $country, $provider_state );
		if ( '' !== $alias_code && ( empty( $states ) || isset( $states[ $alias_code ] ) ) ) {
			return $alias_code;
		}

		if ( empty( $states ) ) {
			return '';
		}

		if ( isset( $states[ $provider_state ] ) ) {
			return $provider_state;
		}

		$provider_key = $this->normalize_lookup_key( $provider_state );
		foreach ( $states as $code => $label ) {
			if ( $this->normalize_lookup_key( (string) $code ) === $provider_key ) {
				return (string) $code;
			}

			if ( $this->normalize_lookup_key( (string) $label ) === $provider_key ) {
				return (string) $code;
			}
		}

		/**
		 * Filter provider state mapping before returning the fallback value.
		 *
		 * @param string $mapped_state   Mapped WooCommerce state code.
		 * @param string $country        ISO country code.
		 * @param string $provider_state Original provider state value.
		 * @param array<string,string> $states WooCommerce states for the country.
		 */
		return (string) apply_filters(
			'wpruby_address_guard_provider_state_mapping',
			$normalized,
			$country,
			$provider_state,
			$states
		);
	}

	/**
	 * Whether a state should be included in customer-facing address summaries.
	 *
	 * @param string $country ISO country code.
	 * @param string $state   WooCommerce state code.
	 *
	 * @return bool
	 */
	public function should_display_state_in_summary( string $country, string $state ): bool {
		$country = strtoupper( trim( $country ) );
		$state   = trim( $state );

		if ( '' === $state ) {
			return false;
		}

		// German checkout rarely needs a visible state line in summaries.
		if ( 'DE' === $country ) {
			return false;
		}

		/**
		 * Filter whether a state should appear in formatted address summaries.
		 *
		 * @param bool   $should_display Default visibility.
		 * @param string $country        ISO country code.
		 * @param string $state          WooCommerce state code.
		 */
		return (bool) apply_filters( 'wpruby_address_guard_display_state_in_summary', true, $country, $state );
	}

	/**
	 * Whether two state values refer to the same subdivision.
	 *
	 * @param string $left    First state value.
	 * @param string $right   Second state value.
	 * @param string $country ISO country code.
	 *
	 * @return bool
	 */
	public function states_equivalent( string $left, string $right, string $country ): bool {
		$left  = trim( $left );
		$right = trim( $right );

		if ( 0 === strcasecmp( $left, $right ) ) {
			return true;
		}

		if ( '' === $left || '' === $right ) {
			return true;
		}

		$mapped_left  = $this->map_provider_state_to_woocommerce_state( $country, $left );
		$mapped_right = $this->map_provider_state_to_woocommerce_state( $country, $right );

		return '' !== $mapped_left
			&& '' !== $mapped_right
			&& 0 === strcasecmp( $mapped_left, $mapped_right );
	}

	/**
	 * Resolve WooCommerce states for a country.
	 *
	 * @param string $country ISO country code.
	 *
	 * @return array<string,string>
	 */
	private function woocommerce_states( string $country ): array {
		if ( ! function_exists( 'WC' ) || ! WC()->countries || ! method_exists( WC()->countries, 'get_states' ) ) {
			return array();
		}

		$states = WC()->countries->get_states( $country );

		return is_array( $states ) ? $states : array();
	}

	/**
	 * Normalize ISO 3166-2 style values such as DE-NW to NW.
	 *
	 * @param string $country ISO country code.
	 * @param string $value   Provider state value.
	 *
	 * @return string
	 */
	private function normalize_iso_subdivision_code( string $country, string $value ): string {
		$value = strtoupper( trim( $value ) );

		if ( preg_match( '/^' . preg_quote( $country, '/' ) . '-([A-Z0-9]{1,3})$/', $value, $matches ) ) {
			return (string) $matches[1];
		}

		if ( preg_match( '/^[A-Z]{2,3}$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Normalize a state label or code for lookup comparisons.
	 *
	 * @param string $value Raw value.
	 *
	 * @return string
	 */
	private function normalize_lookup_key( string $value ): string {
		$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
		$value = str_replace( 'ß', 'ss', $value );
		$value = preg_replace( '/[[:punct:]]+/u', ' ', (string) $value );
		$value = preg_replace( '/\s+/u', ' ', (string) $value );

		return trim( (string) $value );
	}

	/**
	 * Resolve a state code from known aliases when WooCommerce data is unavailable.
	 *
	 * @param string $country        ISO country code.
	 * @param string $provider_state Provider state value.
	 *
	 * @return string
	 */
	private function state_alias_code( string $country, string $provider_state ): string {
		$key = $this->normalize_lookup_key( $provider_state );
		if ( '' === $key ) {
			return '';
		}

		$aliases = $this->state_aliases_for_country( $country );

		return (string) ( $aliases[ $key ] ?? '' );
	}

	/**
	 * Known provider state aliases by country.
	 *
	 * @param string $country ISO country code.
	 *
	 * @return array<string,string>
	 */
	private function state_aliases_for_country( string $country ): array {
		if ( 'DE' !== strtoupper( $country ) ) {
			return array();
		}

		return array(
			'north rhine westphalia' => 'NW',
			'nordrhein westfalen'    => 'NW',
			'bavaria'                => 'BY',
			'bayern'                 => 'BY',
			'baden wurttemberg'      => 'BW',
			'baden wuerttemberg'     => 'BW',
			'berlin'                 => 'BE',
			'hamburg'                => 'HH',
			'hesse'                  => 'HE',
			'hessen'                 => 'HE',
			'lower saxony'           => 'NI',
			'niedersachsen'          => 'NI',
			'saxony'                 => 'SN',
			'sachsen'                => 'SN',
		);
	}
}
