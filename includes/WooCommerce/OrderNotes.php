<?php
/**
 * Lightweight order notes for address check triggers.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\WooCommerce;

use WC_Order;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OrderNotes
 *
 * Persists simple validation notes at checkout.
 */
class OrderNotes {

	const SESSION_SNAPSHOTS_KEY = 'address_guard_lite_snapshots';

	const META_ISSUE_CODE = '_address_guard_lite_issue_code';
	const META_ACTION     = '_address_guard_lite_action';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Address validator.
	 *
	 * @var AddressValidator|null
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->settings->is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'persist_on_order_processed' ), 25, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'persist_on_order_processed_blocks' ), 25, 1 );
	}

	/**
	 * Store a validation snapshot in the session for order persistence.
	 *
	 * @param ValidationResult $result  Validation result.
	 * @param string           $type    billing|shipping.
	 * @param Address          $address Submitted address.
	 *
	 * @return void
	 */
	public function remember_result( ValidationResult $result, string $type, Address $address ): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		if ( $result->is_skipped() || $result->is_valid() ) {
			return;
		}

		$data = $result->to_array();
		$checkout = (array) ( $data['checkout'] ?? array() );

		if ( empty( $checkout['block'] ) && empty( $checkout['warn'] ) ) {
			return;
		}

		$snapshots = (array) WC()->session->get( self::SESSION_SNAPSHOTS_KEY, array() );
		$snapshots[ $type ] = array(
			'code'   => (string) ( $data['code'] ?? '' ),
			'action' => ! empty( $checkout['block'] ) ? 'block' : 'warn',
		);

		WC()->session->set( self::SESSION_SNAPSHOTS_KEY, $snapshots );
	}

	/**
	 * Persist notes when a classic checkout order is processed.
	 *
	 * @param int                  $order_id Order ID.
	 * @param array<string,mixed>  $data     Posted checkout data.
	 * @param WC_Order             $order    Order object.
	 *
	 * @return void
	 */
	public function persist_on_order_processed( int $order_id, array $data, WC_Order $order ): void {
		unset( $order_id, $data );
		$this->persist_to_order( $order );
	}

	/**
	 * Persist notes when a blocks checkout order is processed.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	public function persist_on_order_processed_blocks( WC_Order $order ): void {
		$this->persist_to_order( $order );
	}

	/**
	 * Write order notes and meta from session snapshots.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function persist_to_order( WC_Order $order ): void {
		if ( ! $this->settings->should_add_order_validation_notes() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$snapshots = (array) WC()->session->get( self::SESSION_SNAPSHOTS_KEY, array() );
		if ( empty( $snapshots ) ) {
			return;
		}

		$validator = $this->validator();

		foreach ( $snapshots as $type => $snapshot ) {
			if ( ! is_array( $snapshot ) ) {
				continue;
			}

			$code   = sanitize_key( (string) ( $snapshot['code'] ?? '' ) );
			$action = sanitize_key( (string) ( $snapshot['action'] ?? 'warn' ) );

			if ( '' === $code ) {
				continue;
			}

			$label = $validator->issue_label( $code );
			$note  = sprintf(
				/* translators: %s: detected issue label */
				__( 'Address Guard: %s detected.', 'checkout-address-guard-for-woocommerce' ),
				$label
			);

			$order->add_order_note( $note, false, true );
			$order->update_meta_data( self::META_ISSUE_CODE, $code );
			$order->update_meta_data( self::META_ACTION, $action );
		}

		$order->save();
		WC()->session->set( self::SESSION_SNAPSHOTS_KEY, array() );
	}

	/**
	 * Lazy validator for issue labels.
	 *
	 * @return AddressValidator
	 */
	private function validator(): AddressValidator {
		if ( null === $this->validator ) {
			$this->validator = new AddressValidator( $this->settings );
		}

		return $this->validator;
	}
}
