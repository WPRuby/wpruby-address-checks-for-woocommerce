<?php
/**
 * Checkout validation hook integration tests (classic shortcode checkout).
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WP_Error;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\WooCommerce\CheckoutCompatibility;
use WPRuby\AddressGuard\WooCommerce\ClassicCheckoutIntegration;
use WPRuby\AddressGuard\WooCommerce\OrderNotes;
use WPRuby\AddressGuard\Tests\TestCase;

class ClassicCheckoutIntegrationTest extends TestCase {

	private function classic_checkout(): ClassicCheckoutIntegration {
		$settings      = $this->settings_with_validation( 'block' );
		$validator     = new AddressValidator( $settings );
		$compatibility = new CheckoutCompatibility();
		$order_notes   = new OrderNotes( $settings );

		return new ClassicCheckoutIntegration( $settings, $validator, $compatibility, $order_notes );
	}

	public function test_register_exposes_classic_checkout_validation_hook(): void {
		$integration = $this->classic_checkout();
		$integration->register();

		$this->assertTrue( method_exists( $integration, 'validate_checkout_address' ) );
	}

	public function test_classic_checkout_adds_error_for_po_box_in_block_mode(): void {
		wpruby_ag_test_setup_cart();

		$_POST = array(
			'ship_to_different_address' => '1',
			'shipping_first_name'       => 'Jane',
			'shipping_last_name'        => 'Doe',
			'shipping_address_1'        => 'PO Box 55',
			'shipping_city'             => 'Springfield',
			'shipping_state'            => 'IL',
			'shipping_postcode'         => '62701',
			'shipping_country'          => 'US',
		);

		$integration = $this->classic_checkout();
		$errors      = new WP_Error();
		$integration->validate_checkout_address( array(), $errors );

		$this->assertNotEmpty( $errors->get_error_messages() );
		$this->assertSame( 'address_guard_shipping', $errors->get_error_code() );
	}

	public function test_classic_checkout_allows_valid_address(): void {
		wpruby_ag_test_setup_cart();

		$_POST = array(
			'ship_to_different_address' => '1',
			'shipping_first_name'       => 'Jane',
			'shipping_last_name'        => 'Doe',
			'shipping_address_1'        => '123 Main St',
			'shipping_city'             => 'Springfield',
			'shipping_state'            => 'IL',
			'shipping_postcode'         => '62701',
			'shipping_country'          => 'US',
		);

		$integration = $this->classic_checkout();
		$errors      = new WP_Error();
		$integration->validate_checkout_address( array(), $errors );

		$this->assertSame( array(), $errors->get_error_messages() );
	}

	public function test_warn_mode_allows_invalid_address(): void {
		wpruby_ag_test_setup_cart();
		$GLOBALS['wpruby_ag_test_wc_notices'] = array();

		$settings    = $this->settings_with_validation( 'warn' );
		$validator   = new AddressValidator( $settings );
		$integration = new ClassicCheckoutIntegration(
			$settings,
			$validator,
			new CheckoutCompatibility(),
			new OrderNotes( $settings )
		);

		$_POST = array(
			'ship_to_different_address' => '1',
			'shipping_first_name'       => 'Jane',
			'shipping_last_name'        => 'Doe',
			'shipping_address_1'        => 'PO Box 55',
			'shipping_city'             => 'Springfield',
			'shipping_state'            => 'IL',
			'shipping_postcode'         => '62701',
			'shipping_country'          => 'US',
		);

		$errors = new WP_Error();
		$integration->validate_checkout_address( array(), $errors );

		$this->assertSame( array(), $errors->get_error_messages() );
		$this->assertNotEmpty( $GLOBALS['wpruby_ag_test_wc_notices'] );
	}

	public function test_addresses_to_validate_uses_billing_when_not_shipping_to_different_address(): void {
		wpruby_ag_test_setup_cart();

		$settings = $this->settings_with_validation( 'block' );
		$settings->save(
			array_merge(
				$settings->all(),
				array(
					'validate_billing_address'  => 'yes',
					'validate_shipping_address' => 'yes',
				)
			)
		);

		$integration = new ClassicCheckoutIntegration(
			$settings,
			new AddressValidator( $settings ),
			new CheckoutCompatibility(),
			new OrderNotes( $settings )
		);

		$types = $integration->addresses_to_validate(
			array(
				'billing_address_1' => '123 Main St',
				'billing_country'     => 'US',
			)
		);

		$this->assertSame( array( Address::TYPE_BILLING ), $types );
	}

	public function test_addresses_to_validate_includes_shipping_when_ship_to_different_address(): void {
		wpruby_ag_test_setup_cart();

		$integration = $this->classic_checkout();
		$types       = $integration->addresses_to_validate(
			array(
				'ship_to_different_address' => '1',
			)
		);

		$this->assertContains( Address::TYPE_SHIPPING, $types );
	}
}
