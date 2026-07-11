<?php
/**
 * Address validator warn/block determination tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Tests\TestCase;

class AddressValidatorTest extends TestCase {

	private function validator( string $mode ): AddressValidator {
		return new AddressValidator( $this->settings_with_validation( $mode ) );
	}

	public function test_block_mode_blocks_invalid_local_addresses(): void {
		$validator = $this->validator( 'block' );
		$address   = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'PO Box 44' )
			)
		);

		$result = $validator->validate( $address, 'shipping', array( 'force' => true ) );

		$this->assertTrue( $validator->should_block_checkout( $result ) );
		$this->assertFalse( $validator->should_warn_checkout( $result ) );
	}

	public function test_warn_mode_warns_without_blocking_invalid_addresses(): void {
		$validator = $this->validator( 'warn' );
		$address   = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'PO Box 44' )
			)
		);

		$result = $validator->validate( $address, 'shipping', array( 'force' => true ) );

		$this->assertFalse( $validator->should_block_checkout( $result ) );
		$this->assertTrue( $validator->should_warn_checkout( $result ) );
	}

	public function test_customer_message_uses_po_box_template_key(): void {
		$validator = $this->validator( 'block' );
		$address   = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'PO Box 12' )
			)
		);

		$result  = $validator->validate( $address, 'shipping', array( 'force' => true ) );
		$message = $validator->customer_message( $result, $address, 'shipping' );

		$this->assertStringContainsString( 'po box', strtolower( $message ) );
	}
}
