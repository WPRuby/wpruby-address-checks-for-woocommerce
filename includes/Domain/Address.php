<?php
/**
 * Address value object.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain;

use WPRuby\AddressGuard\Domain\Address\ProviderStateMapper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Address
 *
 * Normalized representation of a WooCommerce checkout address.
 */
class Address {

	const TYPE_BILLING  = 'billing';
	const TYPE_SHIPPING = 'shipping';

	/**
	 * Raw address data.
	 *
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $data Address data.
	 */
	public function __construct( array $data = array() ) {
		$this->data = wp_parse_args( self::sanitize_array( $data ), self::defaults() );
	}

	/**
	 * Default address shape.
	 *
	 * @return array<string,string>
	 */
	public static function defaults(): array {
		return array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'phone'      => '',
			'email'      => '',
			'type'       => self::TYPE_SHIPPING,
		);
	}

	/**
	 * Sanitize raw address input.
	 *
	 * @param array<string,mixed> $data Raw address data.
	 *
	 * @return array<string,string>
	 */
	public static function sanitize_array( array $data ): array {
		$type = isset( $data['type'] ) ? sanitize_key( (string) $data['type'] ) : self::TYPE_SHIPPING;
		if ( ! in_array( $type, array( self::TYPE_BILLING, self::TYPE_SHIPPING ), true ) ) {
			$type = self::TYPE_SHIPPING;
		}

		$country = strtoupper( sanitize_text_field( (string) ( $data['country'] ?? '' ) ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			$country = '';
		}

		return array(
			'first_name' => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
			'company'    => sanitize_text_field( (string) ( $data['company'] ?? '' ) ),
			'address_1'  => sanitize_text_field( (string) ( $data['address_1'] ?? '' ) ),
			'address_2'  => sanitize_text_field( (string) ( $data['address_2'] ?? '' ) ),
			'city'       => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
			'state'      => sanitize_text_field( (string) ( $data['state'] ?? '' ) ),
			'postcode'   => sanitize_text_field( (string) ( $data['postcode'] ?? '' ) ),
			'country'    => $country,
			'phone'      => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
			'email'      => sanitize_email( (string) ( $data['email'] ?? '' ) ),
			'type'       => $type,
		);
	}

	/**
	 * Build an address from prefixed checkout POST fields.
	 *
	 * @param string              $type   billing|shipping.
	 * @param array<string,mixed> $source Optional source array (defaults to $_POST).
	 *
	 * @return self
	 */
	public static function from_checkout_post( string $type, array $source = null ): self {
		$type   = self::TYPE_BILLING === $type ? self::TYPE_BILLING : self::TYPE_SHIPPING;
		$source = is_array( $source ) ? $source : $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only field mapping during checkout validation.
		$prefix = $type . '_';

		return new self(
			array(
				'first_name' => $source[ $prefix . 'first_name' ] ?? '',
				'last_name'  => $source[ $prefix . 'last_name' ] ?? '',
				'company'    => $source[ $prefix . 'company' ] ?? '',
				'address_1'  => $source[ $prefix . 'address_1' ] ?? '',
				'address_2'  => $source[ $prefix . 'address_2' ] ?? '',
				'city'       => $source[ $prefix . 'city' ] ?? '',
				'state'      => $source[ $prefix . 'state' ] ?? '',
				'postcode'   => $source[ $prefix . 'postcode' ] ?? '',
				'country'    => $source[ $prefix . 'country' ] ?? '',
				'phone'      => $source[ $prefix . 'phone' ] ?? '',
				'email'      => self::TYPE_BILLING === $type ? ( $source['billing_email'] ?? '' ) : '',
				'type'       => $type,
			)
		);
	}

	/**
	 * Build an address from WooCommerce Blocks checkout payload keys.
	 *
	 * @param string              $type billing|shipping.
	 * @param array<string,mixed> $data Address payload.
	 *
	 * @return self
	 */
	public static function from_blocks_payload( string $type, array $data ): self {
		$type = self::TYPE_BILLING === $type ? self::TYPE_BILLING : self::TYPE_SHIPPING;

		return new self(
			array(
				'first_name' => $data['first_name'] ?? '',
				'last_name'  => $data['last_name'] ?? '',
				'company'    => $data['company'] ?? '',
				'address_1'  => $data['address_1'] ?? '',
				'address_2'  => $data['address_2'] ?? '',
				'city'       => $data['city'] ?? '',
				'state'      => $data['state'] ?? '',
				'postcode'   => $data['postcode'] ?? '',
				'country'    => $data['country'] ?? '',
				'phone'      => $data['phone'] ?? '',
				'email'      => self::TYPE_BILLING === $type ? ( $data['email'] ?? '' ) : '',
				'type'       => $type,
			)
		);
	}

	public function with_type( string $type ): self {
		$data         = $this->data;
		$data['type'] = Address::TYPE_BILLING === $type ? self::TYPE_BILLING : self::TYPE_SHIPPING;

		return new self( $data );
	}

	/**
	 * Build an address from checkout context, including same-address shipping.
	 *
	 * @param string              $type   billing|shipping.
	 * @param array<string,mixed> $source Optional source array (defaults to $_POST).
	 *
	 * @return self
	 */
	public static function from_checkout_context( string $type, array $source = null ): self {
		$type   = self::TYPE_BILLING === $type ? self::TYPE_BILLING : self::TYPE_SHIPPING;
		$source = is_array( $source ) ? $source : $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only field mapping during checkout validation.

		if ( self::TYPE_SHIPPING === $type && empty( $source['ship_to_different_address'] ) ) {
			return self::from_checkout_post( self::TYPE_BILLING, $source )->with_type( self::TYPE_SHIPPING );
		}

		return self::from_checkout_post( $type, $source );
	}

	/**
	 * Get the raw data array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->data;
	}

	/**
	 * Address type (billing/shipping).
	 *
	 * @return string
	 */
	public function get_type(): string {
		return (string) $this->data['type'];
	}

	/**
	 * Get a single field value.
	 *
	 * @param string $key Field key.
	 *
	 * @return string
	 */
	public function get( string $key ): string {
		return isset( $this->data[ $key ] ) ? (string) $this->data[ $key ] : '';
	}

	/**
	 * Whether the address has enough data to validate.
	 *
	 * @return bool
	 */
	public function is_populated(): bool {
		return '' !== trim( (string) $this->data['address_1'] )
			|| '' !== trim( (string) $this->data['city'] )
			|| '' !== trim( (string) $this->data['postcode'] );
	}

	/**
	 * Whether the address has the minimum fields required for checkout validation.
	 *
	 * @return bool
	 */
	public function has_required_fields(): bool {
		return '' !== trim( (string) $this->data['address_1'] )
			&& '' !== trim( (string) $this->data['country'] );
	}

	/**
	 * Format the address for customer-facing notices.
	 *
	 * @return string
	 */
	public function format_single_line(): string {
		$state_mapper = new ProviderStateMapper();
		$country      = $this->get( 'country' );
		$state        = $this->get( 'state' );

		$parts = array(
			$this->get( 'address_1' ),
			$this->get( 'address_2' ),
			$this->get( 'city' ),
		);

		if ( $state_mapper->should_display_state_in_summary( $country, $state ) ) {
			$parts[] = $state;
		}

		$parts[] = $this->get( 'postcode' );
		$parts[] = $country;

		$parts = array_filter(
			$parts,
			static function ( $part ) {
				return '' !== trim( (string) $part );
			}
		);

		return implode( ', ', $parts );
	}

	/**
	 * Build multi-line address output for admin order panels.
	 *
	 * @return string[]
	 */
	public function format_admin_lines(): array {
		$address_1 = $this->get( 'address_1' );
		$address_2 = $this->get( 'address_2' );

		list( $line_1, $slash_line ) = self::split_slash_subpremise( $address_1 );
		$line_2                      = '' !== $slash_line ? $slash_line : $address_2;

		$lines = array();

		if ( '' !== $line_1 ) {
			$lines[] = $line_1;
		}

		if ( '' !== $line_2 && ! self::is_redundant_address_line( $line_2, $line_1 ) ) {
			$lines[] = $line_2;
		}

		$city_line = self::format_city_state_postcode_line(
			$this->get( 'city' ),
			$this->get( 'state' ),
			$this->get( 'postcode' ),
			$this->get( 'country' )
		);
		if ( '' !== $city_line ) {
			$lines[] = $city_line;
		}

		$country = $this->get( 'country' );
		if ( '' !== $country ) {
			$lines[] = $country;
		}

		return $lines;
	}

	/**
	 * Reconstruct an address from a stored comma-separated summary.
	 *
	 * @param string $summary Stored address summary.
	 *
	 * @return self
	 */
	public static function from_stored_summary( string $summary ): self {
		$summary = trim( $summary );
		if ( '' === $summary ) {
			return new self();
		}

		$parts = array_values(
			array_filter(
				array_map( 'trim', explode( ',', $summary ) ),
				static function ( $part ) {
					return '' !== $part;
				}
			)
		);

		if ( empty( $parts ) ) {
			return new self();
		}

		$country = '';
		if ( 1 < count( $parts ) && preg_match( '/^[A-Z]{2}$/', (string) end( $parts ) ) ) {
			$country = (string) array_pop( $parts );
		}

		$postcode = '';
		if ( ! empty( $parts ) && self::looks_like_postcode( (string) end( $parts ) ) ) {
			$postcode = (string) array_pop( $parts );
		}

		$state_mapper = new ProviderStateMapper();
		$state        = '';
		if ( ! empty( $parts ) && self::looks_like_state_code( (string) end( $parts ) ) ) {
			$state = (string) array_pop( $parts );
		}

		if ( '' !== $state && '' !== $country ) {
			$state = $state_mapper->map_provider_state_to_woocommerce_state( $country, $state );
		}

		$city = '';
		if ( ! empty( $parts ) ) {
			$city = (string) array_pop( $parts );
		}

		$address_1 = (string) ( $parts[0] ?? '' );
		$address_2 = '';
		if ( count( $parts ) > 1 ) {
			$address_2 = implode( ', ', array_slice( $parts, 1 ) );
		}

		return new self(
			array(
				'address_1' => $address_1,
				'address_2' => $address_2,
				'city'      => $city,
				'state'     => $state,
				'postcode'  => $postcode,
				'country'   => $country,
			)
		);
	}

	/**
	 * Split a slash-separated subpremise from address line 1.
	 *
	 * @param string $address_1 Address line 1.
	 *
	 * @return array{0:string,1:string}
	 */
	public static function split_slash_subpremise( string $address_1 ): array {
		$address_1 = trim( $address_1 );
		if ( '' === $address_1 || false === strpos( $address_1, '/' ) ) {
			return array( $address_1, '' );
		}

		if ( preg_match( '/^(.+?)\/(.+)$/', $address_1, $matches ) ) {
			return array( trim( (string) $matches[1] ), trim( (string) $matches[2] ) );
		}

		return array( $address_1, '' );
	}

	/**
	 * Whether an address line duplicates information already in line 1.
	 *
	 * @param string $line      Candidate duplicate line.
	 * @param string $address_1 Primary address line.
	 *
	 * @return bool
	 */
	public static function is_redundant_address_line( string $line, string $address_1 ): bool {
		$line      = trim( $line );
		$address_1 = trim( $address_1 );

		if ( '' === $line ) {
			return true;
		}

		if ( '' !== $address_1 && 0 === strcasecmp( $line, $address_1 ) ) {
			return true;
		}

		if ( '' !== $address_1 && preg_match( '/\b' . preg_quote( $line, '/' ) . '$/iu', $address_1 ) ) {
			return true;
		}

		list( , $slash_line ) = self::split_slash_subpremise( $address_1 );
		if ( '' !== $slash_line && 0 === strcasecmp( $line, $slash_line ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Format city, state, and postcode on one line.
	 *
	 * @param string $city     City.
	 * @param string $state    State.
	 * @param string $postcode Postcode.
	 * @param string $country  ISO country code.
	 *
	 * @return string
	 */
	public static function format_city_state_postcode_line( string $city, string $state, string $postcode, string $country = '' ): string {
		$city     = trim( $city );
		$state    = trim( $state );
		$postcode = trim( $postcode );
		$country  = strtoupper( trim( $country ) );

		$state_mapper = new ProviderStateMapper();
		if ( ! $state_mapper->should_display_state_in_summary( $country, $state ) ) {
			$state = '';
		}

		if ( in_array( $country, array( 'DE', 'AT', 'CH', 'NL', 'BE', 'DK', 'SE', 'NO', 'FI', 'FR', 'ES', 'IT' ), true ) ) {
			$parts = array();
			if ( '' !== $postcode ) {
				$parts[] = $postcode;
			}
			if ( '' !== $city ) {
				$parts[] = $city;
			}

			return implode( ' ', $parts );
		}

		$parts = array();
		if ( '' !== $city ) {
			$parts[] = $city;
		}

		$region = trim( $state . ( '' !== $state && '' !== $postcode ? ' ' : '' ) . $postcode );
		if ( '' !== $region ) {
			$parts[] = $region;
		}

		return implode( ', ', $parts );
	}

	/**
	 * Whether a token looks like a postal code.
	 *
	 * @param string $value Candidate postcode.
	 *
	 * @return bool
	 */
	private static function looks_like_postcode( string $value ): bool {
		return (bool) preg_match( '/^[A-Z0-9][A-Z0-9\s-]{1,10}$/i', trim( $value ) );
	}

	/**
	 * Whether a token looks like a state/province code.
	 *
	 * @param string $value Candidate state code.
	 *
	 * @return bool
	 */
	private static function looks_like_state_code( string $value ): bool {
		$value = trim( $value );

		if ( preg_match( '/^[A-Z]{2}-[A-Z0-9]{1,3}$/i', $value ) ) {
			return true;
		}

		return (bool) preg_match( '/^[A-Z]{2,3}$/', $value );
	}
}
