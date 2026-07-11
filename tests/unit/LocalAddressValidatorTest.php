<?php
/**
 * Local address validator tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\LocalAddressValidator;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\Tests\TestCase;

class LocalAddressValidatorTest extends TestCase {

	private LocalAddressValidator $validator;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = new LocalAddressValidator( $this->settings_with_validation( 'block' ) );
	}

	public function test_skips_empty_address(): void {
		$result = $this->validator->validate( new Address() );

		$this->assertTrue( $result->is_skipped() );
		$this->assertSame( 'empty_address', $result->to_array()['code'] );
	}

	public function test_flags_po_box(): void {
		$address = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'PO Box 123' )
			)
		);

		$result = $this->validator->validate( $address );

		$this->assertSame( ValidationResult::STATUS_INVALID, $result->get_status() );
		$this->assertContains( 'po_box_detected', $result->to_array()['issues'] );
	}

	public function test_flags_parcel_locker(): void {
		$address = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'Packstation 101' )
			)
		);

		$result = $this->validator->validate( $address );

		$this->assertContains( 'parcel_locker_detected', $result->to_array()['issues'] );
	}

	public function test_flags_missing_house_number(): void {
		$address = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'Main Street' )
			)
		);

		$result = $this->validator->validate( $address );

		$this->assertSame( ValidationResult::STATUS_INVALID, $result->get_status() );
		$this->assertContains( 'missing_house_number', $result->to_array()['issues'] );
	}

	public function test_flags_postcode_country_mismatch(): void {
		$address = new Address(
			array_merge(
				$this->sample_address(),
				array( 'postcode' => 'INVALID' )
			)
		);

		$result = $this->validator->validate( $address );

		$this->assertContains( 'postcode_country_mismatch', $result->to_array()['issues'] );
	}

	public function test_respects_disabled_po_box_check(): void {
		$settings = $this->settings_with_validation( 'block' );
		$values   = $settings->all();
		$values['check_po_box'] = 'no';
		$settings->save( $values );

		$validator = new LocalAddressValidator( $settings );
		$address   = new Address(
			array_merge(
				$this->sample_address(),
				array( 'address_1' => 'PO Box 123' )
			)
		);

		$result = $validator->validate( $address );

		$this->assertTrue( $result->is_valid() );
	}

	public function test_requires_postcode_when_wc_marks_it_required(): void {
		wpruby_ag_test_setup_wc( array( 'postcode' ) );

		$address = new Address(
			array_merge(
				$this->sample_address(),
				array( 'postcode' => '' )
			)
		);

		$result = $this->validator->validate( $address );

		$this->assertContains( 'missing_postcode', $result->to_array()['issues'] );
	}

	public function test_valid_local_address_passes(): void {
		$result = $this->validator->validate( new Address( $this->sample_address() ) );

		$this->assertTrue( $result->is_valid() );
		$this->assertSame( 'local', $result->to_array()['provider'] );
	}
}
