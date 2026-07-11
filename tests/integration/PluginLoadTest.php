<?php
/**
 * Plugin bootstrap integration tests.
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WPRuby\AddressGuard\Plugin;
use WPRuby\AddressGuard\Tests\TestCase;

class PluginLoadTest extends TestCase {

	public function test_autoloader_loads_core_classes(): void {
		$this->assertTrue( class_exists( Plugin::class ) );
		$this->assertTrue( class_exists( 'WPRuby\\AddressGuard\\Domain\\Address' ) );
		$this->assertTrue( class_exists( 'WPRuby\\AddressGuard\\Infrastructure\\Settings' ) );
	}

	public function test_plugin_singleton_can_be_created_without_fatal(): void {
		$plugin = Plugin::get_instance();

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertTrue( $plugin->is_woocommerce_active() );
	}

	public function test_plugin_boots_settings_when_woocommerce_is_active(): void {
		$plugin = Plugin::get_instance();

		$this->assertInstanceOf( 'WPRuby\\AddressGuard\\Infrastructure\\Settings', $plugin->settings() );
	}

	public function test_missing_woocommerce_notice_is_safe_to_render(): void {
		$plugin = Plugin::get_instance();

		ob_start();
		$plugin->render_missing_woocommerce_notice();
		$this->assertSame( '', ob_get_clean() );

		$this->grant_capability( 'activate_plugins' );
		ob_start();
		$plugin->render_missing_woocommerce_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'WooCommerce', $output );
	}
}
