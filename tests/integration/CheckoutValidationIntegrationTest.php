<?php
/**
 * Checkout Blocks validation hook integration tests.
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\WooCommerce\CheckoutCompatibility;
use WPRuby\AddressGuard\WooCommerce\CheckoutValidation;
use WPRuby\AddressGuard\WooCommerce\OrderNotes;
use WPRuby\AddressGuard\Tests\TestCase;

class CheckoutValidationIntegrationTest extends TestCase {

	private function checkout_validation(): CheckoutValidation {
		$settings      = $this->settings_with_validation( 'block' );
		$validator     = new AddressValidator( $settings );
		$compatibility = new CheckoutCompatibility();
		$order_notes   = new OrderNotes( $settings );

		return new CheckoutValidation( $settings, $validator, $compatibility, $order_notes );
	}

	public function test_register_exposes_blocks_checkout_validation_hook(): void {
		$validation = $this->checkout_validation();
		$validation->register();

		$this->assertTrue( method_exists( $validation, 'validate_blocks_checkout' ) );
	}
}
