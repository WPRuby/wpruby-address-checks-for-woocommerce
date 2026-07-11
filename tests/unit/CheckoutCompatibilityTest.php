<?php
/**
 * Checkout compatibility detection tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\WooCommerce\CheckoutCompatibility;
use WPRuby\AddressGuard\Tests\TestCase;

class CheckoutCompatibilityTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wpruby_ag_test_is_checkout']            = false;
		$GLOBALS['wpruby_ag_test_checkout_blocks']         = false;
		$GLOBALS['wpruby_ag_test_has_checkout_shortcode']  = false;
		$GLOBALS['wpruby_ag_test_wc_endpoint']             = '';
		$GLOBALS['wpruby_ag_test_is_admin']                = false;
		$GLOBALS['wpruby_ag_test_checkout_post']           = (object) array(
			'post_content' => '[woocommerce_checkout]',
		);
	}

	public function test_detects_classic_checkout_on_checkout_page_without_blocks(): void {
		$GLOBALS['wpruby_ag_test_is_checkout']            = true;
		$GLOBALS['wpruby_ag_test_has_checkout_shortcode'] = true;

		$compatibility = new CheckoutCompatibility();

		$this->assertSame( CheckoutCompatibility::CONTEXT_CLASSIC, $compatibility->detect_context() );
		$this->assertTrue( $compatibility->is_classic_checkout() );
		$this->assertFalse( $compatibility->checkout_page_uses_blocks() );
	}

	public function test_detects_blocks_checkout_page_configuration(): void {
		$GLOBALS['wpruby_ag_test_is_checkout']     = true;
		$GLOBALS['wpruby_ag_test_checkout_blocks'] = true;

		$compatibility = new CheckoutCompatibility();

		$this->assertSame( CheckoutCompatibility::CONTEXT_BLOCKS, $compatibility->detect_context() );
		$this->assertTrue( $compatibility->checkout_page_uses_blocks() );
		$this->assertTrue( $compatibility->is_classic_checkout() );
	}

	public function test_loads_assets_on_checkout_even_when_page_configured_for_blocks(): void {
		$GLOBALS['wpruby_ag_test_is_checkout']     = true;
		$GLOBALS['wpruby_ag_test_checkout_blocks'] = true;

		$compatibility = new CheckoutCompatibility();

		$this->assertTrue( $compatibility->should_load_checkout_assets() );
	}

	public function test_does_not_load_assets_on_order_received(): void {
		$GLOBALS['wpruby_ag_test_is_checkout'] = true;
		$GLOBALS['wpruby_ag_test_wc_endpoint'] = 'order-received';

		$compatibility = new CheckoutCompatibility();

		$this->assertFalse( $compatibility->should_load_checkout_assets() );
		$this->assertSame( CheckoutCompatibility::CONTEXT_ORDER_RECEIVED, $compatibility->detect_context() );
	}

	public function test_does_not_load_assets_on_order_pay(): void {
		$GLOBALS['wpruby_ag_test_is_checkout'] = true;
		$GLOBALS['wpruby_ag_test_wc_endpoint'] = 'order-pay';

		$compatibility = new CheckoutCompatibility();

		$this->assertFalse( $compatibility->should_load_checkout_assets() );
		$this->assertSame( CheckoutCompatibility::CONTEXT_ORDER_PAY, $compatibility->detect_context() );
	}

	public function test_summary_for_app_reports_dual_support(): void {
		$GLOBALS['wpruby_ag_test_is_checkout']            = true;
		$GLOBALS['wpruby_ag_test_has_checkout_shortcode'] = true;

		$summary = ( new CheckoutCompatibility() )->summary_for_app();

		$this->assertTrue( $summary['supports_blocks'] );
		$this->assertTrue( $summary['supports_classic'] );
		$this->assertSame( CheckoutCompatibility::CONTEXT_CLASSIC, $summary['checkout_detected'] );
	}
}
