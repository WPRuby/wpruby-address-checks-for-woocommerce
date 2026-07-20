<?php
/**
 * Classic WooCommerce shortcode checkout integration.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\WooCommerce;

use WP_Error;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClassicCheckoutIntegration
 *
 * Handles classic [woocommerce_checkout] validation and notices.
 */
class ClassicCheckoutIntegration {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Address validator.
	 *
	 * @var AddressValidator
	 */
	private $validator;

	/**
	 * Checkout compatibility helper.
	 *
	 * @var CheckoutCompatibility
	 */
	private $compatibility;

	/**
	 * Order notes helper.
	 *
	 * @var OrderNotes
	 */
	private $order_notes;

	/**
	 * Constructor.
	 *
	 * @param Settings              $settings      Settings accessor.
	 * @param AddressValidator      $validator     Address validator.
	 * @param CheckoutCompatibility $compatibility Checkout compatibility helper.
	 * @param OrderNotes            $order_notes   Order notes helper.
	 */
	public function __construct(
		Settings $settings,
		AddressValidator $validator,
		CheckoutCompatibility $compatibility,
		OrderNotes $order_notes
	) {
		$this->settings      = $settings;
		$this->validator     = $validator;
		$this->compatibility = $compatibility;
		$this->order_notes   = $order_notes;
	}

	/**
	 * Register classic checkout hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->settings->is_enabled() || ! $this->validator->is_validation_active() ) {
			return;
		}

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_address' ), 20, 2 );
	}

	/**
	 * Whether classic validation assets should load.
	 *
	 * @return bool
	 */
	public function should_enqueue_validation(): bool {
		if ( ! $this->validator->is_validation_active() ) {
			return false;
		}

		if ( ! $this->settings->is_validate_billing_enabled() && ! $this->settings->is_validate_shipping_enabled() ) {
			return false;
		}

		return $this->compatibility->should_load_checkout_assets();
	}

	/**
	 * Frontend configuration for classic checkout validation JS.
	 *
	 * @return array<string,mixed>
	 */
	public function validation_config(): array {
		$messages = array();
		foreach ( array(
			'po_box_blocked',
			'locker_blocked',
			'missing_house_number',
			'country_postcode_mismatch',
			'validation_blocked',
			'validation_warning',
		) as $key ) {
			$messages[ $key ] = $this->settings->message( $key );
		}

		return array(
			'restUrl'         => esc_url_raw( rest_url( 'wpruby-address-checks/v1/' ) ),
			'restNonce'       => wp_create_nonce( 'wp_rest' ),
			'mode'            => $this->settings->validation_mode(),
			'billingEnabled'  => $this->settings->is_validate_billing_enabled(),
			'shippingEnabled' => $this->settings->is_validate_shipping_enabled(),
			'checkoutBlocks'  => $this->compatibility->checkout_page_uses_blocks(),
			'checkoutMode'    => 'auto',
			'i18n'            => array(
				'messages' => $messages,
			),
		);
	}

	/**
	 * Validate classic checkout submission.
	 *
	 * @param array<string,mixed> $data   Posted checkout data.
	 * @param WP_Error            $errors Checkout errors.
	 *
	 * @return void
	 */
	public function validate_checkout_address( array $data, WP_Error $errors ): void {
		$source  = is_array( $_POST ) ? $_POST : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies checkout nonce before this hook.
		$context = array(
			'context' => 'checkout',
			'source'  => 'classic',
		);

		foreach ( $this->addresses_to_validate( $source ) as $type ) {
			$address = Address::from_checkout_context( $type, $source );
			$this->validate_and_apply( $address, $type, $errors, $context );
		}
	}

	/**
	 * Determine which address types should be validated for classic checkout.
	 *
	 * @param array<string,mixed> $source Posted checkout data.
	 *
	 * @return string[]
	 */
	public function addresses_to_validate( array $source ): array {
		$needs_shipping    = $this->cart_needs_shipping();
		$ship_to_different = ! empty( $source['ship_to_different_address'] );
		$validate_billing  = $this->validator->should_validate_type( Address::TYPE_BILLING );
		$validate_shipping = $this->validator->should_validate_type( Address::TYPE_SHIPPING );
		$types             = array();

		if ( $validate_billing ) {
			$types[] = Address::TYPE_BILLING;
		}

		if ( $validate_shipping && $needs_shipping ) {
			if ( $ship_to_different ) {
				$types[] = Address::TYPE_SHIPPING;
			} elseif ( ! $validate_billing ) {
				$types[] = Address::TYPE_SHIPPING;
			}
		}

		if ( $validate_billing && $validate_shipping && $needs_shipping && ! $ship_to_different ) {
			$types = array( Address::TYPE_BILLING );
		}

		return array_values( array_unique( $types ) );
	}

	/**
	 * Validate an address and apply checkout behavior for classic checkout.
	 *
	 * @param Address             $address Address value object.
	 * @param string              $type    billing|shipping.
	 * @param WP_Error            $errors  Checkout errors.
	 * @param array<string,mixed> $context Validation context.
	 *
	 * @return void
	 */
	private function validate_and_apply( Address $address, string $type, WP_Error $errors, array $context ): void {
		if ( ! $address->has_required_fields() ) {
			return;
		}

		$result = $this->validator->validate( $address, $type, $context );
		$this->order_notes->remember_result( $result, $type, $address );
		$this->apply_result_to_checkout( $result, $type, $errors, $address );
	}

	/**
	 * Apply a validation result to classic checkout.
	 *
	 * @param ValidationResult $result  Validation result.
	 * @param string           $type    billing|shipping.
	 * @param WP_Error         $errors  Checkout errors.
	 * @param Address          $address Address value object.
	 *
	 * @return void
	 */
	private function apply_result_to_checkout( ValidationResult $result, string $type, WP_Error $errors, Address $address ): void {
		if ( $result->is_skipped() || $result->is_valid() ) {
			return;
		}

		$field_key = Address::TYPE_BILLING === $type ? 'billing' : 'shipping';
		$message   = $this->notice_message( $result, $address, $type );

		if ( $this->validator->should_block_checkout( $result ) ) {
			$errors->add( 'wpruby_ac_' . $field_key, wp_kses_post( $message ) );
			return;
		}

		if ( $this->validator->should_warn_checkout( $result ) ) {
			wc_add_notice( wp_kses_post( $message ), 'notice' );
		}
	}

	/**
	 * Resolve the customer-facing notice text.
	 *
	 * @param ValidationResult $result  Validation result.
	 * @param Address          $address Address value object.
	 * @param string           $type    billing|shipping.
	 *
	 * @return string
	 */
	private function notice_message( ValidationResult $result, Address $address, string $type ): string {
		if ( $this->validator->should_block_checkout( $result ) ) {
			return $this->validator->blocked_message( $result, $address, $type );
		}

		if ( $this->validator->should_warn_checkout( $result ) ) {
			return $this->validator->warning_message( $result, $address, $type );
		}

		return $this->validator->customer_message( $result, $address, $type );
	}

	/**
	 * Whether the cart needs a shipping address.
	 *
	 * @return bool
	 */
	private function cart_needs_shipping(): bool {
		return function_exists( 'WC' )
			&& WC()->cart
			&& WC()->cart->needs_shipping_address();
	}
}
