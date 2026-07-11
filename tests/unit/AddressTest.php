<?php
/**
 * Address value object tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Tests\TestCase;

class AddressTest extends TestCase {

	public function test_sanitize_array_normalizes_country_and_type(): void {
		$sanitized = Address::sanitize_array(
			array(
				'country' => 'us',
				'type'    => 'billing',
				'email'   => 'user@example.test',
			)
		);

		$this->assertSame( 'US', $sanitized['country'] );
		$this->assertSame( 'billing', $sanitized['type'] );
		$this->assertSame( 'user@example.test', $sanitized['email'] );
	}

	public function test_sanitize_array_rejects_invalid_country_and_type(): void {
		$sanitized = Address::sanitize_array(
			array(
				'country' => 'USA',
				'type'    => 'invalid',
			)
		);

		$this->assertSame( '', $sanitized['country'] );
		$this->assertSame( Address::TYPE_SHIPPING, $sanitized['type'] );
	}

	public function test_from_checkout_post_maps_prefixed_fields(): void {
		$address = Address::from_checkout_post(
			'shipping',
			array(
				'shipping_first_name' => 'Jane',
				'shipping_address_1'  => '123 Main St',
				'shipping_country'    => 'US',
			)
		);

		$this->assertSame( 'Jane', $address->get( 'first_name' ) );
		$this->assertSame( '123 Main St', $address->get( 'address_1' ) );
		$this->assertSame( 'US', $address->get( 'country' ) );
		$this->assertSame( Address::TYPE_SHIPPING, $address->get_type() );
	}

	public function test_from_checkout_context_uses_billing_for_same_address_shipping(): void {
		$address = Address::from_checkout_context(
			'shipping',
			array(
				'billing_first_name' => 'John',
				'billing_address_1'  => '456 Oak Ave',
				'billing_country'    => 'CA',
			)
		);

		$this->assertSame( Address::TYPE_SHIPPING, $address->get_type() );
		$this->assertSame( '456 Oak Ave', $address->get( 'address_1' ) );
		$this->assertSame( 'CA', $address->get( 'country' ) );
	}

	public function test_format_single_line_joins_populated_parts(): void {
		$address = new Address( $this->sample_address() );

		$this->assertSame(
			'123 Main St, Springfield, IL, 62701, US',
			$address->format_single_line()
		);
	}

	public function test_format_admin_lines_splits_address_fields(): void {
		$address = new Address(
			array(
				'address_1' => 'Lützenkirchener str 160',
				'address_2' => '160',
				'city'      => 'Leverkusen',
				'postcode'  => '51381',
				'country'   => 'DE',
			)
		);

		$this->assertSame(
			array(
				'Lützenkirchener str 160',
				'51381 Leverkusen',
				'DE',
			),
			$address->format_admin_lines()
		);
	}

	public function test_format_admin_lines_splits_slash_subpremise_and_drops_duplicate(): void {
		$address = new Address(
			array(
				'address_1' => 'Lützenkirchener Str. 160/160',
				'city'      => 'Leverkusen',
				'postcode'  => '51381',
				'country'   => 'DE',
			)
		);

		$this->assertSame(
			array(
				'Lützenkirchener Str. 160',
				'51381 Leverkusen',
				'DE',
			),
			$address->format_admin_lines()
		);
	}

	public function test_from_stored_summary_parses_legacy_comma_format(): void {
		$address = Address::from_stored_summary( '123 Main St, Apt 4, Springfield, IL, 62701, US' );

		$this->assertSame(
			array(
				'123 Main St',
				'Apt 4',
				'Springfield, IL 62701',
				'US',
			),
			$address->format_admin_lines()
		);
	}

	public function test_has_required_fields_requires_address_and_country(): void {
		$complete = new Address( $this->sample_address() );
		$missing  = new Address( array( 'city' => 'Springfield' ) );

		$this->assertTrue( $complete->has_required_fields() );
		$this->assertFalse( $missing->has_required_fields() );
	}
}
