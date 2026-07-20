<?php
/**
 * Checkout context detection for Blocks and classic shortcode checkout.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CheckoutCompatibility
 *
 * Detects which WooCommerce checkout experience is active and whether
 * storefront assets should load on the current request.
 */
class CheckoutCompatibility {

	const CONTEXT_BLOCKS         = 'blocks';
	const CONTEXT_CLASSIC        = 'classic';
	const CONTEXT_ORDER_PAY      = 'order_pay';
	const CONTEXT_ORDER_RECEIVED = 'order_received';
	const CONTEXT_ADMIN          = 'admin';
	const CONTEXT_OTHER          = 'other';

	/**
	 * Detect the current checkout-related context.
	 *
	 * @return string One of the CONTEXT_* constants.
	 */
	public function detect_context(): string {
		if ( is_admin() ) {
			return self::CONTEXT_ADMIN;
		}

		if ( $this->is_order_received_page() ) {
			return self::CONTEXT_ORDER_RECEIVED;
		}

		if ( $this->is_order_pay_page() ) {
			return self::CONTEXT_ORDER_PAY;
		}

		if ( ! $this->is_checkout_request() ) {
			return self::CONTEXT_OTHER;
		}

		if ( $this->checkout_page_uses_blocks() ) {
			return self::CONTEXT_BLOCKS;
		}

		return self::CONTEXT_CLASSIC;
	}

	/**
	 * Whether the current front-end request is the WooCommerce checkout page.
	 *
	 * @return bool
	 */
	public function is_checkout_request(): bool {
		return function_exists( 'is_checkout' ) && is_checkout();
	}

	/**
	 * Whether the configured checkout page content uses WooCommerce Checkout Blocks.
	 *
	 * Used for admin status and as a JS fallback hint only. Storefront scripts
	 * should not rely on this alone because merchants may switch checkout types.
	 *
	 * @return bool
	 */
	public function checkout_page_uses_blocks(): bool {
		return $this->page_has_checkout_block( 'woocommerce/checkout' );
	}

	/**
	 * Whether the configured checkout page uses Checkout Blocks.
	 *
	 * @return bool
	 */
	public function is_checkout_blocks(): bool {
		return $this->checkout_page_uses_blocks();
	}

	/**
	 * Whether the configured checkout page uses classic shortcode checkout.
	 *
	 * @return bool
	 */
	public function checkout_page_uses_classic(): bool {
		if ( $this->checkout_page_uses_blocks() ) {
			return false;
		}

		return $this->checkout_page_has_classic_marker();
	}

	/**
	 * Whether the current request is classic shortcode checkout.
	 *
	 * @return bool
	 */
	public function is_classic_checkout(): bool {
		if ( ! $this->is_checkout_request() ) {
			return false;
		}

		if ( $this->is_order_received_page() || $this->is_order_pay_page() ) {
			return false;
		}

		// Runtime classic checkout is any checkout request that is not the
		// order-received / order-pay endpoints. JS confirms the DOM mode.
		return true;
	}

	/**
	 * Whether the WooCommerce order-pay endpoint is active.
	 *
	 * @return bool
	 */
	public function is_order_pay_page(): bool {
		return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' );
	}

	/**
	 * Whether the WooCommerce order-received endpoint is active.
	 *
	 * @return bool
	 */
	public function is_order_received_page(): bool {
		return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Whether WPRuby Address Checks checkout assets may load on this request.
	 *
	 * @return bool
	 */
	public function should_load_checkout_assets(): bool {
		if ( is_admin() ) {
			return false;
		}

		if ( ! $this->is_checkout_request() ) {
			return false;
		}

		if ( $this->is_order_received_page() || $this->is_order_pay_page() ) {
			return false;
		}

		return true;
	}

	/**
	 * Summary payload for admin UI and localized scripts.
	 *
	 * @return array<string,mixed>
	 */
	public function summary_for_app(): array {
		$page_blocks  = $this->checkout_page_uses_blocks();
		$page_classic = $this->checkout_page_uses_classic();
		$detected     = $page_blocks ? self::CONTEXT_BLOCKS : ( $page_classic ? self::CONTEXT_CLASSIC : self::CONTEXT_OTHER );

		return array(
			'supports_blocks'         => true,
			'supports_classic'        => true,
			'checkout_blocks'         => $page_blocks,
			'checkout_classic'        => $page_classic || ! $page_blocks,
			'checkout_detected'       => $detected,
			'detected_label'          => $this->label_for_context( $detected ),
			'active_on_request'       => $this->is_checkout_request() ? $detected : '',
		);
	}

	/**
	 * Human-readable label for a checkout context.
	 *
	 * @param string $context Checkout context constant.
	 *
	 * @return string
	 */
	public function label_for_context( string $context ): string {
		switch ( $context ) {
			case self::CONTEXT_BLOCKS:
				return __( 'Checkout Blocks', 'wpruby-address-checks-for-woocommerce' );
			case self::CONTEXT_CLASSIC:
				return __( 'Classic shortcode checkout', 'wpruby-address-checks-for-woocommerce' );
			case self::CONTEXT_ORDER_PAY:
				return __( 'Order pay page', 'wpruby-address-checks-for-woocommerce' );
			case self::CONTEXT_ORDER_RECEIVED:
				return __( 'Order received page', 'wpruby-address-checks-for-woocommerce' );
			case self::CONTEXT_ADMIN:
				return __( 'Admin', 'wpruby-address-checks-for-woocommerce' );
			default:
				return __( 'Unknown', 'wpruby-address-checks-for-woocommerce' );
		}
	}

	/**
	 * Whether the configured checkout page uses a classic checkout marker.
	 *
	 * @return bool
	 */
	private function checkout_page_has_classic_marker(): bool {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return true;
		}

		$page_id = (int) wc_get_page_id( 'checkout' );
		if ( $page_id <= 0 ) {
			return false;
		}

		if ( $this->page_has_checkout_block( 'woocommerce/classic-shortcode', $page_id ) ) {
			return true;
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return true;
		}

		if ( function_exists( 'has_shortcode' ) && has_shortcode( $post->post_content, 'woocommerce_checkout' ) ) {
			return true;
		}

		return ! $this->page_has_checkout_block( 'woocommerce/checkout', $page_id );
	}

	/**
	 * Whether a checkout page contains a given block.
	 *
	 * @param string   $block_name Block name.
	 * @param int|null $page_id    Optional checkout page ID.
	 *
	 * @return bool
	 */
	private function page_has_checkout_block( string $block_name, ?int $page_id = null ): bool {
		if ( ! function_exists( 'has_block' ) || ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$page_id = null === $page_id ? (int) wc_get_page_id( 'checkout' ) : $page_id;
		if ( $page_id <= 0 ) {
			return false;
		}

		return has_block( $block_name, $page_id );
	}
}
