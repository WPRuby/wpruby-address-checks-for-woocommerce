<?php
/**
 * Checkout Blocks address validation integration.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\WooCommerce;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use WC_Order;
use WP_REST_Request;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CheckoutValidation
 *
 * Validates billing and shipping addresses during Checkout Blocks checkout.
 */
class CheckoutValidation {

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->settings->is_enabled() || ! $this->validator->is_validation_active() ) {
			return;
		}

		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'validate_blocks_checkout' ), 5, 2 );
	}

	/**
	 * Whether Checkout Blocks validation assets should load.
	 *
	 * @return bool
	 */
	public function should_enqueue(): bool {
		if ( ! $this->validator->is_validation_active() ) {
			return false;
		}

		if ( ! $this->settings->is_validate_billing_enabled() && ! $this->settings->is_validate_shipping_enabled() ) {
			return false;
		}

		return $this->compatibility->should_load_checkout_assets();
	}

	/**
	 * Frontend configuration for Checkout Blocks validation JS.
	 *
	 * @return array<string,mixed>
	 */
	public function frontend_config(): array {
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
	 * Validate Checkout Blocks submission.
	 *
	 * @param WC_Order        $order   Order being created.
	 * @param WP_REST_Request $request Store API request.
	 *
	 * @return void
	 */
	public function validate_blocks_checkout( WC_Order $order, WP_REST_Request $request ): void {
		unset( $order );

		if ( ! $this->compatibility->is_checkout_blocks() ) {
			return;
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return;
		}

		foreach ( $this->addresses_to_validate_from_blocks( $params ) as $type ) {
			$key     = Address::TYPE_BILLING === $type ? 'billing_address' : 'shipping_address';
			$payload = isset( $params[ $key ] ) && is_array( $params[ $key ] ) ? $params[ $key ] : array();

			if ( Address::TYPE_SHIPPING === $type && empty( $payload ) && empty( $params['ship_to_different_address'] ?? false ) ) {
				$payload = isset( $params['billing_address'] ) && is_array( $params['billing_address'] ) ? $params['billing_address'] : array();
			}

			$address = Address::from_blocks_payload( $type, $payload );
			$this->validate_and_apply_blocks( $address, $type );
		}
	}

	/**
	 * Determine which address types should be validated for blocks checkout.
	 *
	 * @param array<string,mixed> $params Checkout request params.
	 *
	 * @return string[]
	 */
	private function addresses_to_validate_from_blocks( array $params ): array {
		$needs_shipping    = $this->cart_needs_shipping();
		$ship_to_different = ! empty( $params['ship_to_different_address'] );
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
	 * Validate an address and apply checkout behavior for blocks checkout.
	 *
	 * @param Address $address Address value object.
	 * @param string  $type    billing|shipping.
	 *
	 * @return void
	 */
	private function validate_and_apply_blocks( Address $address, string $type ): void {
		if ( ! $address->has_required_fields() ) {
			return;
		}

		$result = $this->validator->validate(
			$address,
			$type,
			array(
				'context' => 'checkout',
				'source'  => 'blocks',
			)
		);

		$this->order_notes->remember_result( $result, $type, $address );
		$this->apply_result_to_checkout_blocks( $result, $type, $address );
	}

	/**
	 * Apply a validation result to blocks checkout.
	 *
	 * @param ValidationResult $result Validation result.
	 * @param string           $type   billing|shipping.
	 * @param Address          $address Address value object.
	 *
	 * @return void
	 */
	private function apply_result_to_checkout_blocks( ValidationResult $result, string $type, Address $address ): void {
		if ( $result->is_skipped() || $result->is_valid() ) {
			return;
		}

		$message = $this->notice_message( $result, $address, $type );

		if ( $this->validator->should_block_checkout( $result ) ) {
			$this->throw_blocks_error( $message, $type );
		}

		if ( $this->validator->should_warn_checkout( $result ) ) {
			wc_add_notice( wp_kses_post( $message ), 'notice' );
		}
	}

	/**
	 * Resolve the customer-facing notice text.
	 *
	 * @param ValidationResult $result Validation result.
	 * @param Address          $address Address value object.
	 * @param string           $type   billing|shipping.
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
	 * Throw a Store API route exception when checkout must be blocked.
	 *
	 * @param string $message Customer-facing message.
	 * @param string $type    billing|shipping.
	 *
	 * @return void
	 */
	private function throw_blocks_error( string $message, string $type ): void {
		if ( ! class_exists( RouteException::class ) ) {
			wp_die( esc_html( $message ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_die handles escaping.
		}

		throw new RouteException(
			'wpruby_ac_' . sanitize_key( $type ) . '_invalid',
			esc_html( wp_strip_all_tags( $message ) ),
			400
		);
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
