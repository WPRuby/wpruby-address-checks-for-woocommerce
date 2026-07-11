<?php
/**
 * Settings integration tests.
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\Plugin;
use WPRuby\AddressGuard\Tests\TestCase;

class SettingsIntegrationTest extends TestCase {

	public function test_activation_persists_defaults_when_missing(): void {
		Plugin::activate();

		$stored = get_option( Settings::OPTION_KEY, false );
		$this->assertIsArray( $stored );
		$this->assertSame( 'yes', $stored['plugin_enabled'] );
		$this->assertSame( 'warn', $stored['validation_mode'] );
	}

	public function test_settings_merge_defaults_for_new_keys(): void {
		$settings = new Settings();
		$defaults = $settings->defaults();

		$this->assertTrue( $settings->is_enabled() );
		$this->assertArrayHasKey( 'messages', $defaults );
		$this->assertNotEmpty( $settings->message( 'missing_house_number' ) );
	}

	public function test_check_toggles_default_to_enabled(): void {
		$settings = new Settings();

		$this->assertTrue( $settings->is_check_enabled( 'check_missing_house_number' ) );
		$this->assertTrue( $settings->is_check_enabled( 'check_po_box' ) );
		$this->assertTrue( $settings->is_check_enabled( 'check_parcel_locker' ) );
		$this->assertTrue( $settings->is_check_enabled( 'check_postcode_format' ) );
	}
}
