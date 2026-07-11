<?php
/**
 * Message placeholder formatting tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WPRuby\AddressGuard\Infrastructure\MessageFormatter;
use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\Tests\TestCase;

class MessageFormatterTest extends TestCase {

	public function test_replaces_placeholders_and_escapes_html(): void {
		$message = MessageFormatter::format(
			'Please review {address_type} in {city}, {country}. Rule: {rule_name}',
			array(
				'address_type' => 'Shipping address',
				'city'         => 'Springfield',
				'country'      => 'US',
				'rule_name'    => '<script>alert(1)</script>',
			)
		);

		$this->assertStringContainsString( 'Shipping address', $message );
		$this->assertStringContainsString( 'Springfield', $message );
		$this->assertStringContainsString( 'US', $message );
		$this->assertStringNotContainsString( '<script>', $message );
	}

	public function test_unknown_placeholders_remain_unchanged(): void {
		$message = MessageFormatter::format( 'Hello {unknown}', array() );
		$this->assertSame( 'Hello {unknown}', $message );
	}

	public function test_settings_format_message_uses_templates(): void {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['messages']['po_box_blocked'] = 'PO boxes are blocked for {country}.';
		$settings->save( $values );

		$formatted = $settings->format_message(
			'po_box_blocked',
			array( 'country' => 'US' )
		);

		$this->assertSame( 'PO boxes are blocked for US.', $formatted );
	}

	public function test_sample_context_includes_all_placeholder_keys(): void {
		$sample = MessageFormatter::sample_context();

		foreach ( MessageFormatter::PLACEHOLDERS as $key ) {
			$this->assertArrayHasKey( $key, $sample );
			$this->assertNotSame( '', $sample[ $key ] );
		}
	}
}
