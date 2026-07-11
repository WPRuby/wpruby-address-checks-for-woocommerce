<?php
/**
 * Order notes integration tests.
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\WooCommerce\OrderNotes;
use WPRuby\AddressGuard\Tests\TestCase;

class OrderNotesIntegrationTest extends TestCase {

	public function test_persist_to_order_adds_private_note(): void {
		wpruby_ag_test_setup_wc_session();
		$settings = $this->settings_with_validation( 'block' );
		$notes    = new OrderNotes( $settings );
		$validator = new AddressValidator( $settings );
		$address   = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'Main Street' )
			)
		);

		$result = $validator->validate( $address, 'shipping', array( 'force' => true ) );
		$notes->remember_result( $result, 'shipping', $address );

		$order = wpruby_ag_test_create_order();
		$notes->persist_on_order_processed( 1, array(), $order );

		$this->assertNotEmpty( $order->get_notes() );
		$this->assertStringContainsString( 'Address Guard', $order->get_notes()[0]['content'] );
		$this->assertSame( 'missing_house_number', $order->get_meta( OrderNotes::META_ISSUE_CODE ) );
	}
}
