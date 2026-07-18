<?php
/**
 * Map Google Places address components to WooCommerce fields.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain\Google;

use WPRuby\AddressGuard\Domain\Address\AddressComponentFormatter;
use WPRuby\AddressGuard\Domain\Address\ProviderStateMapper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GoogleAddressMapper
 */
class GoogleAddressMapper {

	/**
	 * Street line formatter.
	 *
	 * @var AddressComponentFormatter
	 */
	private $street_formatter;

	/**
	 * State mapper.
	 *
	 * @var ProviderStateMapper
	 */
	private $state_mapper;

	/**
	 * Constructor.
	 *
	 * @param AddressComponentFormatter|null $street_formatter Optional street formatter.
	 * @param ProviderStateMapper|null       $state_mapper     Optional state mapper.
	 */
	public function __construct( ?AddressComponentFormatter $street_formatter = null, ?ProviderStateMapper $state_mapper = null ) {
		$this->street_formatter = $street_formatter ?? new AddressComponentFormatter();
		$this->state_mapper     = $state_mapper ?? new ProviderStateMapper();
	}

	/**
	 * Map Places API (New) address components to WooCommerce address fields.
	 *
	 * @param array<int,array<string,mixed>> $components Address components from Google.
	 * @param string                         $formatted  Formatted address string.
	 * @param string                         $place_id   Google place ID.
	 *
	 * @return array<string,string>
	 */
	public function from_places_components( array $components, string $formatted = '', string $place_id = '' ): array {
		$by_type = $this->index_components( $components );

		$street_number = $this->component_value( $by_type, 'street_number' );
		$route         = $this->component_value( $by_type, 'route' );
		$country       = strtoupper( sanitize_text_field( $this->component_short_value( $by_type, 'country' ) ) );
		$address_1     = $this->street_formatter->format_street_line(
			$street_number,
			$route,
			$country,
			array(
				'street_number' => $street_number,
				'route'         => $route,
			)
		);

		if ( '' === $address_1 && '' !== $formatted ) {
			$address_1 = $this->first_line_from_formatted( $formatted );
		}

		$city = $this->first_component_value(
			$by_type,
			array( 'locality', 'postal_town', 'administrative_area_level_2', 'sublocality', 'sublocality_level_1' )
		);

		$provider_state = $this->component_short_value( $by_type, 'administrative_area_level_1' );
		if ( '' === $provider_state ) {
			$provider_state = $this->component_value( $by_type, 'administrative_area_level_1' );
		}

		return array(
			'address_1' => sanitize_text_field( $address_1 ),
			'address_2' => sanitize_text_field( $this->component_value( $by_type, 'subpremise' ) ),
			'city'      => sanitize_text_field( $city ),
			'state'     => sanitize_text_field( $this->state_mapper->map_provider_state_to_woocommerce_state( $country, $provider_state ) ),
			'postcode'  => sanitize_text_field( $this->component_value( $by_type, 'postal_code' ) ),
			'country'   => $country,
			'formatted' => sanitize_text_field( $formatted ),
			'place_id'  => sanitize_text_field( $place_id ),
		);
	}

	/**
	 * Strip metadata keys for WooCommerce address fields.
	 *
	 * @param array<string,string> $address Mapped address.
	 *
	 * @return array<string,string>
	 */
	public function to_woocommerce_address( array $address ): array {
		return array(
			'address_1' => (string) ( $address['address_1'] ?? '' ),
			'address_2' => (string) ( $address['address_2'] ?? '' ),
			'city'      => (string) ( $address['city'] ?? '' ),
			'state'     => (string) ( $address['state'] ?? '' ),
			'postcode'  => (string) ( $address['postcode'] ?? '' ),
			'country'   => (string) ( $address['country'] ?? '' ),
		);
	}

	/**
	 * Index components by type.
	 *
	 * @param array<int,array<string,mixed>> $components Raw components.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function index_components( array $components ): array {
		$indexed = array();

		foreach ( $components as $component ) {
			if ( ! is_array( $component ) ) {
				continue;
			}

			$types = isset( $component['types'] ) && is_array( $component['types'] ) ? $component['types'] : array();
			foreach ( $types as $type ) {
				$type = sanitize_key( (string) $type );
				if ( '' !== $type && ! isset( $indexed[ $type ] ) ) {
					$indexed[ $type ] = $component;
				}
			}
		}

		return $indexed;
	}

	/**
	 * Get long text for a component type.
	 *
	 * @param array<string,array<string,mixed>> $by_type Indexed components.
	 * @param string                            $type    Component type.
	 *
	 * @return string
	 */
	private function component_value( array $by_type, string $type ): string {
		if ( ! isset( $by_type[ $type ] ) || ! is_array( $by_type[ $type ] ) ) {
			return '';
		}

		$component = $by_type[ $type ];

		if ( isset( $component['longText'] ) ) {
			return (string) $component['longText'];
		}

		if ( isset( $component['long_name'] ) ) {
			return (string) $component['long_name'];
		}

		return (string) ( $component['text'] ?? '' );
	}

	/**
	 * Get short text for a component type.
	 *
	 * @param array<string,array<string,mixed>> $by_type Indexed components.
	 * @param string                            $type    Component type.
	 *
	 * @return string
	 */
	private function component_short_value( array $by_type, string $type ): string {
		if ( ! isset( $by_type[ $type ] ) || ! is_array( $by_type[ $type ] ) ) {
			return '';
		}

		$component = $by_type[ $type ];

		if ( isset( $component['shortText'] ) ) {
			return (string) $component['shortText'];
		}

		if ( isset( $component['short_name'] ) ) {
			return (string) $component['short_name'];
		}

		return $this->component_value( $by_type, $type );
	}

	/**
	 * Return the first available component value from a list of types.
	 *
	 * @param array<string,array<string,mixed>> $by_type Indexed components.
	 * @param string[]                          $types   Candidate types.
	 *
	 * @return string
	 */
	private function first_component_value( array $by_type, array $types ): string {
		foreach ( $types as $type ) {
			$value = $this->component_value( $by_type, $type );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Extract the first line from a formatted address.
	 *
	 * @param string $formatted Formatted address.
	 *
	 * @return string
	 */
	private function first_line_from_formatted( string $formatted ): string {
		$parts = preg_split( '/,\s*/', $formatted );
		if ( ! is_array( $parts ) || empty( $parts ) ) {
			return $formatted;
		}

		return (string) $parts[0];
	}
}
