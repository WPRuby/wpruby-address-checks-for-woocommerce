<?php
/**
 * ValidationResult tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\ValidationResult;
use WPRuby\AddressGuard\Tests\TestCase;

class ValidationResultTest extends TestCase {

	public function test_status_helpers(): void {
		$valid = new ValidationResult( array( 'status' => ValidationResult::STATUS_VALID ) );
		$this->assertTrue( $valid->is_valid() );
		$this->assertFalse( $valid->should_block_checkout() );
		$this->assertFalse( $valid->should_warn() );

		$invalid = new ValidationResult( array( 'status' => ValidationResult::STATUS_INVALID ) );
		$this->assertFalse( $invalid->is_valid() );
		$this->assertTrue( $invalid->should_block_checkout() );
		$this->assertTrue( $invalid->should_warn() );

		$unverified = new ValidationResult( array( 'status' => ValidationResult::STATUS_UNVERIFIED ) );
		$this->assertTrue( $unverified->should_warn() );
		$this->assertFalse( $unverified->should_block_checkout() );
	}

	public function test_has_suggestion_detects_normalized_or_suggested_address(): void {
		$with_normalized = new ValidationResult(
			array(
				'status'             => ValidationResult::STATUS_CORRECTED,
				'normalized_address' => $this->sample_address(),
			)
		);
		$without = new ValidationResult( array( 'status' => ValidationResult::STATUS_VALID ) );

		$this->assertTrue( $with_normalized->has_suggestion() );
		$this->assertFalse( $without->has_suggestion() );
	}

	public function test_to_public_array_redacts_internal_fields(): void {
		$result = new ValidationResult(
			array(
				'status'               => ValidationResult::STATUS_INVALID,
				'message'              => 'Invalid',
				'raw_response_summary' => 'secret',
				'errors'               => array( 'error' ),
				'warnings'             => array( 'warn' ),
				'issues'               => array( 'issue' ),
				'rules'                => array( 'matched' => true ),
			)
		);

		$public = $result->to_public_array();

		$this->assertArrayHasKey( 'status', $public );
		$this->assertArrayHasKey( 'message', $public );
		$this->assertArrayNotHasKey( 'raw_response_summary', $public );
		$this->assertArrayNotHasKey( 'errors', $public );
		$this->assertArrayNotHasKey( 'warnings', $public );
		$this->assertArrayNotHasKey( 'issues', $public );
		$this->assertArrayNotHasKey( 'rules', $public );
	}

	public function test_from_provider_result_attaches_original_and_normalizes_corrected(): void {
		$address = new Address( $this->sample_address() );
		$provider = new ValidationResult(
			array(
				'status'            => ValidationResult::STATUS_CORRECTED,
				'suggested_address' => array_merge( $this->sample_address(), array( 'address_1' => '124 Main St' ) ),
			)
		);

		$normalized = ValidationResult::from_provider_result( $provider, $address );

		$this->assertSame( '124 Main St', $normalized->get_suggested_address()['address_1'] );
		$this->assertSame( '123 Main St', $normalized->to_array()['original_address']['address_1'] );
	}

	public function test_corrected_without_suggestion_becomes_unverified(): void {
		$address  = new Address( $this->sample_address() );
		$provider = new ValidationResult(
			array(
				'status' => ValidationResult::STATUS_CORRECTED,
			)
		);

		$normalized = ValidationResult::from_provider_result( $provider, $address );

		$this->assertSame( ValidationResult::STATUS_UNVERIFIED, $normalized->get_status() );
	}
}
