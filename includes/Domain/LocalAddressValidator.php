<?php
/**
 * Local address validation fallbacks.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain;

use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LocalAddressValidator
 *
 * Performs lightweight checks without calling an external provider.
 */
class LocalAddressValidator {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Validate an address locally.
	 *
	 * @param Address $address Address to validate.
	 *
	 * @return ValidationResult
	 */
	public function validate( Address $address ): ValidationResult {
		if ( ! $address->is_populated() ) {
			return new ValidationResult(
				array(
					'status'           => ValidationResult::STATUS_SKIPPED,
					'code'             => 'empty_address',
					'original_address' => $address->to_array(),
				)
			);
		}

		$issues  = array();
		$errors  = array();
		$country = strtoupper( $address->get( 'country' ) );

		if ( '' === trim( $address->get( 'address_1' ) ) ) {
			$issues[] = 'missing_address_1';
			$errors[] = __( 'Street address is required.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( '' === $country ) {
			$issues[] = 'missing_country';
			$errors[] = __( 'Country is required.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->country_requires_postcode( $country ) && '' === trim( $address->get( 'postcode' ) ) ) {
			$issues[] = 'missing_postcode';
			$errors[] = __( 'Postcode is required for the selected country.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->country_requires_state( $country ) && '' === trim( $address->get( 'state' ) ) ) {
			$issues[] = 'missing_state';
			$errors[] = __( 'State or region is required for the selected country.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->settings->is_check_enabled( 'check_po_box' ) && $this->contains_po_box( $address ) ) {
			$issues[] = 'po_box_detected';
			$errors[] = __( 'PO Box addresses may not be deliverable.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->settings->is_check_enabled( 'check_parcel_locker' ) && $this->contains_parcel_locker( $address ) ) {
			$issues[] = 'parcel_locker_detected';
			$errors[] = __( 'Parcel locker addresses may not be deliverable.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->settings->is_check_enabled( 'check_missing_house_number' ) && $this->missing_house_number( $address ) ) {
			$issues[] = 'missing_house_number';
			$errors[] = __( 'Please include a house or building number in the street address.', 'checkout-address-guard-for-woocommerce' );
		}

		if ( $this->settings->is_check_enabled( 'check_postcode_format' ) ) {
			$postcode_issue = $this->postcode_country_mismatch( $address );
			if ( null !== $postcode_issue ) {
				$issues[] = $postcode_issue;
				$errors[] = __( 'The postcode does not match the selected country.', 'checkout-address-guard-for-woocommerce' );
			}
		}

		if ( ! empty( $errors ) ) {
			return new ValidationResult(
				array(
					'status'           => ValidationResult::STATUS_INVALID,
					'code'             => $issues[0],
					'original_address' => $address->to_array(),
					'issues'           => $issues,
					'errors'           => $errors,
					'provider'         => 'local',
				)
			);
		}

		return new ValidationResult(
			array(
				'status'           => ValidationResult::STATUS_VALID,
				'code'             => 'local_valid',
				'original_address' => $address->to_array(),
				'provider'         => 'local',
			)
		);
	}

	/**
	 * Whether the combined address lines contain a PO box reference.
	 *
	 * @param Address $address Address value object.
	 *
	 * @return bool
	 */
	public function contains_po_box( Address $address ): bool {
		$haystack = trim( $address->get( 'address_1' ) . ' ' . $address->get( 'address_2' ) );

		if ( '' === $haystack ) {
			return false;
		}

		foreach ( $this->po_box_patterns( $address->get( 'country' ) ) as $pattern ) {
			if ( 1 === preg_match( $pattern, $haystack ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the address appears to reference a parcel locker.
	 *
	 * @param Address $address Address value object.
	 *
	 * @return bool
	 */
	public function contains_parcel_locker( Address $address ): bool {
		$haystack = trim(
			$address->get( 'address_1' ) . ' ' .
			$address->get( 'address_2' ) . ' ' .
			$address->get( 'company' )
		);

		if ( '' === $haystack ) {
			return false;
		}

		foreach ( $this->parcel_locker_patterns( $address->get( 'country' ) ) as $pattern ) {
			if ( 1 === preg_match( $pattern, $haystack ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a street number appears to be missing for supported countries.
	 *
	 * @param Address $address Address value object.
	 *
	 * @return bool
	 */
	public function missing_house_number( Address $address ): bool {
		$country   = strtoupper( $address->get( 'country' ) );
		$address_1 = trim( $address->get( 'address_1' ) );

		if ( '' === $address_1 || $this->contains_po_box( $address ) ) {
			return false;
		}

		if ( ! in_array( $country, array( 'US', 'CA', 'GB', 'AU', 'NZ', 'IE' ), true ) ) {
			return false;
		}

		foreach ( $this->house_number_patterns( $country ) as $pattern ) {
			if ( 1 === preg_match( $pattern, $address_1 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * PO box regex patterns.
	 *
	 * @param string $country Country code.
	 *
	 * @return string[]
	 */
	private function po_box_patterns( string $country ): array {
		$patterns = array(
			'/\b(p\.?\s*o\.?\s*box|post\s*office\s*box|po\s*box|postfach|bo[iî]te\s*postale|apartado)\b/i',
		);

		/**
		 * Filter PO box detection patterns.
		 *
		 * @param string[] $patterns Regex patterns.
		 * @param string   $country  Country code.
		 */
		return (array) apply_filters( 'address_guard_lite_po_box_patterns', $patterns, $country );
	}

	/**
	 * Parcel locker regex patterns.
	 *
	 * @param string $country Country code.
	 *
	 * @return string[]
	 */
	private function parcel_locker_patterns( string $country ): array {
		$patterns = array(
			'/\b(parcel\s*locker|packstation|pack\s*station|paketshop|amazon\s*locker|inpost|collect\+|parcel\s*point|locker\s*pickup|pudo|pickup\s*point|postfiliale|dhl\s*packstation)\b/i',
		);

		/**
		 * Filter parcel locker detection patterns.
		 *
		 * @param string[] $patterns Regex patterns.
		 * @param string   $country  Country code.
		 */
		return (array) apply_filters( 'address_guard_lite_parcel_locker_patterns', $patterns, $country );
	}

	/**
	 * House number presence patterns (match means number found).
	 *
	 * @param string $country Country code.
	 *
	 * @return string[]
	 */
	private function house_number_patterns( string $country ): array {
		$patterns = array(
			'/^\d+[a-zA-Z]?(\s|\/|-|$)/',
			'/\b\d+[a-zA-Z]?\b/',
		);

		/**
		 * Filter missing house number detection patterns.
		 *
		 * @param string[] $patterns Regex patterns that indicate a house number is present.
		 * @param string   $country  Country code.
		 */
		return (array) apply_filters( 'address_guard_lite_missing_house_number_patterns', $patterns, $country );
	}

	/**
	 * Whether WooCommerce expects a postcode for the country.
	 *
	 * @param string $country Country code.
	 *
	 * @return bool
	 */
	private function country_requires_postcode( string $country ): bool {
		return $this->country_field_is_required( $country, 'postcode' );
	}

	/**
	 * Whether WooCommerce expects a state for the country.
	 *
	 * @param string $country Country code.
	 *
	 * @return bool
	 */
	private function country_requires_state( string $country ): bool {
		return $this->country_field_is_required( $country, 'state' );
	}

	/**
	 * Whether a WooCommerce address field is required for a country.
	 *
	 * @param string $country Country code.
	 * @param string $field   Field key without prefix, e.g. postcode|state.
	 *
	 * @return bool
	 */
	private function country_field_is_required( string $country, string $field ): bool {
		if ( '' === $country || ! function_exists( 'WC' ) || ! WC()->countries ) {
			return false;
		}

		$fields = WC()->countries->get_address_fields( $country, 'billing_' );
		$key    = 'billing_' . sanitize_key( $field );

		if ( ! isset( $fields[ $key ] ) || ! is_array( $fields[ $key ] ) ) {
			return false;
		}

		$field_settings = $fields[ $key ];

		if ( ! empty( $field_settings['hidden'] ) ) {
			return false;
		}

		return ! empty( $field_settings['required'] );
	}

	/**
	 * Detect a simple postcode/country mismatch.
	 *
	 * @param Address $address Address value object.
	 *
	 * @return string|null Issue code or null when no mismatch is detected.
	 */
	private function postcode_country_mismatch( Address $address ): ?string {
		$country  = strtoupper( $address->get( 'country' ) );
		$postcode = strtoupper( preg_replace( '/\s+/', '', $address->get( 'postcode' ) ) );

		if ( '' === $country || '' === $postcode ) {
			return null;
		}

		switch ( $country ) {
			case 'US':
				if ( ! preg_match( '/^\d{5}(\d{4})?$/', $postcode ) ) {
					return 'postcode_country_mismatch';
				}
				break;
			case 'CA':
				if ( ! preg_match( '/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $postcode ) ) {
					return 'postcode_country_mismatch';
				}
				break;
			case 'GB':
				if ( ! preg_match( '/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $postcode ) ) {
					return 'postcode_country_mismatch';
				}
				break;
			case 'AU':
				if ( ! preg_match( '/^\d{4}$/', $postcode ) ) {
					return 'postcode_country_mismatch';
				}
				break;
		}

		return null;
	}
}
