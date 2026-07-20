<?php
/**
 * Address validation service.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\Domain;

use WPRuby\AddressGuard\Infrastructure\MessageFormatter;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AddressValidator
 *
 * Coordinates local checks and customer messaging.
 */
class AddressValidator {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Local validator.
	 *
	 * @var LocalAddressValidator
	 */
	private $local_validator;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings        = $settings;
		$this->local_validator = new LocalAddressValidator( $settings );
	}

	/**
	 * Validate an address.
	 *
	 * @param Address             $address Address to validate.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $context Optional context such as checkout.
	 *
	 * @return ValidationResult
	 */
	public function validate( Address $address, string $type = 'shipping', array $context = array() ): ValidationResult {
		$type  = Address::TYPE_BILLING === $type ? Address::TYPE_BILLING : Address::TYPE_SHIPPING;
		$force = ! empty( $context['force'] );

		if ( ! $force && ! $this->should_validate_type( $type ) ) {
			return new ValidationResult(
				array(
					'status'           => ValidationResult::STATUS_SKIPPED,
					'code'             => 'validation_disabled_for_type',
					'original_address' => $address->to_array(),
				)
			);
		}

		if ( ! $address->is_populated() ) {
			return new ValidationResult(
				array(
					'status'           => ValidationResult::STATUS_SKIPPED,
					'code'             => 'empty_address',
					'original_address' => $address->to_array(),
				)
			);
		}

		/**
		 * Filter the validation result before local checks run.
		 *
		 * @param ValidationResult|null $result  Pre-computed result, or null to continue.
		 * @param Address               $address Address being validated.
		 * @param string                $type    billing|shipping.
		 * @param array<string,mixed>   $context Validation context.
		 * @param Settings              $settings Settings accessor.
		 */
		$filtered = apply_filters(
			'wpruby_address_checks_validate_address',
			null,
			$address,
			$type,
			$context,
			$this->settings
		);
		if ( $filtered instanceof ValidationResult ) {
			return $this->finalize_result( $filtered, $address, $type );
		}

		$local_result = $this->local_validator->validate( $address );

		return $this->finalize_result( $local_result, $address, $type );
	}

	/**
	 * Whether validation is active for the current plugin settings.
	 *
	 * @return bool
	 */
	public function is_validation_active(): bool {
		return $this->settings->is_enabled();
	}

	/**
	 * Whether a given address type should be validated.
	 *
	 * @param string $type billing|shipping.
	 *
	 * @return bool
	 */
	public function should_validate_type( string $type ): bool {
		if ( ! $this->is_validation_active() ) {
			return false;
		}

		if ( Address::TYPE_BILLING === $type ) {
			return $this->settings->is_validate_billing_enabled();
		}

		return $this->settings->is_validate_shipping_enabled();
	}

	/**
	 * Whether checkout should be blocked for a result under current settings.
	 *
	 * @param ValidationResult $result Validation result.
	 *
	 * @return bool
	 */
	public function should_block_checkout( ValidationResult $result ): bool {
		if ( ! $this->is_validation_active() || 'block' !== $this->settings->validation_mode() ) {
			return false;
		}

		return ValidationResult::STATUS_INVALID === $result->get_status();
	}

	/**
	 * Whether checkout should show a warning for a result under current settings.
	 *
	 * @param ValidationResult $result Validation result.
	 *
	 * @return bool
	 */
	public function should_warn_checkout( ValidationResult $result ): bool {
		if ( ! $this->is_validation_active() || 'warn' !== $this->settings->validation_mode() ) {
			return false;
		}

		return ValidationResult::STATUS_INVALID === $result->get_status();
	}

	/**
	 * Resolve the customer-facing message for a result.
	 *
	 * @param ValidationResult    $result  Validation result.
	 * @param Address|null        $address Optional address for placeholders.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $extra   Extra placeholder context.
	 *
	 * @return string
	 */
	public function customer_message( ValidationResult $result, ?Address $address = null, string $type = 'shipping', array $extra = array() ): string {
		if ( '' !== $result->get_message() && empty( $extra['force_template'] ) ) {
			return $result->get_message();
		}

		$key = $this->message_key_for_result( $result );
		if ( '' === $key ) {
			return '';
		}

		return $this->format_customer_message( $key, $result, $address, $type, $extra );
	}

	/**
	 * Resolve the checkout-blocked message for a result.
	 *
	 * @param ValidationResult    $result  Validation result.
	 * @param Address|null        $address Optional address for placeholders.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $extra   Extra placeholder context.
	 *
	 * @return string
	 */
	public function blocked_message( ValidationResult $result, ?Address $address = null, string $type = 'shipping', array $extra = array() ): string {
		$key = $this->message_key_for_result( $result );
		if ( '' !== $key ) {
			return $this->format_customer_message( $key, $result, $address, $type, $extra );
		}

		return $this->format_customer_message( 'validation_blocked', $result, $address, $type, $extra );
	}

	/**
	 * Resolve the checkout-warning message for a result.
	 *
	 * @param ValidationResult    $result  Validation result.
	 * @param Address|null        $address Optional address for placeholders.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $extra   Extra placeholder context.
	 *
	 * @return string
	 */
	public function warning_message( ValidationResult $result, ?Address $address = null, string $type = 'shipping', array $extra = array() ): string {
		$key = $this->message_key_for_result( $result );
		if ( '' !== $key ) {
			return $this->format_customer_message( $key, $result, $address, $type, $extra );
		}

		return $this->format_customer_message( 'validation_warning', $result, $address, $type, $extra );
	}

	/**
	 * Human-readable label for an issue code (used in order notes).
	 *
	 * @param string $code Issue code.
	 *
	 * @return string
	 */
	public function issue_label( string $code ): string {
		$labels = array(
			'missing_house_number'      => __( 'Missing house number', 'wpruby-address-checks-for-woocommerce' ),
			'po_box_detected'           => __( 'PO box detected', 'wpruby-address-checks-for-woocommerce' ),
			'parcel_locker_detected'    => __( 'Parcel locker detected', 'wpruby-address-checks-for-woocommerce' ),
			'postcode_country_mismatch' => __( 'Postcode format mismatch', 'wpruby-address-checks-for-woocommerce' ),
			'missing_address_1'         => __( 'Missing street address', 'wpruby-address-checks-for-woocommerce' ),
			'missing_country'           => __( 'Missing country', 'wpruby-address-checks-for-woocommerce' ),
			'missing_postcode'          => __( 'Missing postcode', 'wpruby-address-checks-for-woocommerce' ),
			'missing_state'             => __( 'Missing state', 'wpruby-address-checks-for-woocommerce' ),
		);

		return $labels[ $code ] ?? sanitize_text_field( $code );
	}

	/**
	 * Attach customer messaging and checkout behavior metadata.
	 *
	 * @param ValidationResult $result  Validation result.
	 * @param Address          $address Original address.
	 * @param string           $type    billing|shipping.
	 *
	 * @return ValidationResult
	 */
	private function finalize_result( ValidationResult $result, Address $address, string $type ): ValidationResult {
		$data = $result->to_array();
		if ( empty( $data['original_address'] ) ) {
			$data['original_address'] = $address->to_array();
		}

		$final = new ValidationResult( $data );

		if ( '' === (string) ( $data['message'] ?? '' ) && ! $final->is_skipped() && ! $final->is_valid() ) {
			$data['message'] = $this->customer_message( $final, $address, $type, array( 'force_template' => true ) );
		}

		$final = new ValidationResult( $data );

		$data['checkout'] = array(
			'mode'   => $this->settings->validation_mode(),
			'block'  => $this->should_block_checkout( $final ),
			'warn'   => $this->should_warn_checkout( $final ),
			'notice' => (string) ( $data['message'] ?? '' ),
		);

		return new ValidationResult( $data );
	}

	/**
	 * Resolve the settings message key for a validation result.
	 *
	 * @param ValidationResult $result Validation result.
	 *
	 * @return string
	 */
	private function message_key_for_result( ValidationResult $result ): string {
		$data = $result->to_array();
		$code = (string) ( $data['code'] ?? '' );

		switch ( $code ) {
			case 'po_box_detected':
				return 'po_box_blocked';
			case 'parcel_locker_detected':
				return 'locker_blocked';
			case 'missing_house_number':
				return 'missing_house_number';
			case 'postcode_country_mismatch':
				return 'country_postcode_mismatch';
		}

		if ( ValidationResult::STATUS_INVALID === $result->get_status() ) {
			return 'validation_blocked';
		}

		return '';
	}

	/**
	 * Format a settings message template for a validation result.
	 *
	 * @param string              $key     Message key.
	 * @param ValidationResult    $result  Validation result.
	 * @param Address|null        $address Optional address for placeholders.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $extra   Extra placeholder context.
	 *
	 * @return string
	 */
	private function format_customer_message( string $key, ValidationResult $result, ?Address $address, string $type, array $extra = array() ): string {
		return $this->settings->format_message( $key, $this->message_context( $result, $address, $type, $extra ) );
	}

	/**
	 * Build placeholder context for a validation result.
	 *
	 * @param ValidationResult    $result  Validation result.
	 * @param Address|null        $address Optional address for placeholders.
	 * @param string              $type    billing|shipping.
	 * @param array<string,mixed> $extra   Extra placeholder context.
	 *
	 * @return array<string,string>
	 */
	private function message_context( ValidationResult $result, ?Address $address, string $type, array $extra = array() ): array {
		$data     = $result->to_array();
		$type     = Address::TYPE_BILLING === $type ? Address::TYPE_BILLING : Address::TYPE_SHIPPING;
		$original = new Address( is_array( $data['original_address'] ?? null ) ? $data['original_address'] : ( $address ? $address->to_array() : array() ) );
		$current  = $address ?: $original;

		$context = array(
			'address_type'     => $this->address_type_label( $type ),
			'original_address' => $original->format_single_line(),
			'field'            => $this->field_label_for_code( (string) ( $data['code'] ?? '' ) ),
			'country'          => $current->get( 'country' ),
			'postcode'         => $current->get( 'postcode' ),
			'city'             => $current->get( 'city' ),
		);

		foreach ( $extra as $key => $value ) {
			if ( is_string( $key ) && in_array( $key, MessageFormatter::PLACEHOLDERS, true ) ) {
				$context[ $key ] = (string) $value;
			}
		}

		return $context;
	}

	/**
	 * Human-readable address type label.
	 *
	 * @param string $type billing|shipping.
	 *
	 * @return string
	 */
	private function address_type_label( string $type ): string {
		return Address::TYPE_BILLING === $type
			? __( 'Billing address', 'wpruby-address-checks-for-woocommerce' )
			: __( 'Shipping address', 'wpruby-address-checks-for-woocommerce' );
	}

	/**
	 * Human-readable field label for a validation issue code.
	 *
	 * @param string $code Validation issue code.
	 *
	 * @return string
	 */
	private function field_label_for_code( string $code ): string {
		$labels = array(
			'missing_address_1'         => __( 'Street address', 'wpruby-address-checks-for-woocommerce' ),
			'missing_country'           => __( 'Country', 'wpruby-address-checks-for-woocommerce' ),
			'missing_postcode'          => __( 'Postcode', 'wpruby-address-checks-for-woocommerce' ),
			'missing_state'             => __( 'State / Region', 'wpruby-address-checks-for-woocommerce' ),
			'missing_house_number'      => __( 'House number', 'wpruby-address-checks-for-woocommerce' ),
			'postcode_country_mismatch' => __( 'Postcode', 'wpruby-address-checks-for-woocommerce' ),
			'po_box_detected'           => __( 'Street address', 'wpruby-address-checks-for-woocommerce' ),
			'parcel_locker_detected'    => __( 'Street address', 'wpruby-address-checks-for-woocommerce' ),
		);

		return $labels[ $code ] ?? '';
	}
}
