<?php
/**
 * Pro / Lite conflict helpers.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Tests\TestCase;

use function WPRuby\AddressGuard\wpruby_address_checks_pro_conflict_notice;
use function WPRuby\AddressGuard\wpruby_address_checks_pro_is_active;

class ProConflictTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['address_guard_test_options']['active_plugins'] = array();
	}

	public function test_pro_is_inactive_by_default(): void {
		$this->assertFalse( wpruby_address_checks_pro_is_active() );
	}

	public function test_pro_is_detected_from_active_plugins_option(): void {
		$GLOBALS['address_guard_test_options']['active_plugins'] = array(
			'address-guard-pro/address-guard-for-woocommerce.php',
		);

		$this->assertTrue( wpruby_address_checks_pro_is_active() );
	}

	public function test_conflict_notice_requires_capability(): void {
		ob_start();
		wpruby_address_checks_pro_conflict_notice();
		$this->assertSame( '', ob_get_clean() );

		$this->grant_capability( 'activate_plugins' );
		ob_start();
		wpruby_address_checks_pro_conflict_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Address Guard Pro is active', $output );
		$this->assertStringContainsString( 'Checkout Address Guard for WooCommerce', $output );
	}
}
