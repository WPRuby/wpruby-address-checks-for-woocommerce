<?php
/**
 * Shared PHPUnit helpers.
 *
 * @package WPRuby\AddressGuard\Tests
 */

namespace WPRuby\AddressGuard\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use WPRuby\AddressGuard\Infrastructure\Settings;

abstract class TestCase extends PhpUnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wpruby_address_checks_test_options'] = array(
			'date_format'     => 'F j, Y',
			'time_format'     => 'g:i a',
			'timezone_string' => 'UTC',
		);
		$GLOBALS['wpruby_ag_test_user_caps']       = array();
		$GLOBALS['wpruby_ag_test_nonces']          = array();
		$GLOBALS['wpruby_ag_test_rest_routes']     = array();
		$GLOBALS['wpruby_ag_test_is_admin']        = false;
		$GLOBALS['wpruby_ag_test_wc']              = null;
		$GLOBALS['wpruby_ag_test_wc_order']        = null;
		$GLOBALS['wpruby_ag_test_transients']      = array();
		$GLOBALS['wpruby_ag_test_http_responses']  = array();
		$GLOBALS['wpruby_ag_test_http_requests']   = array();

		wpruby_ag_test_enable_woocommerce();
		wpruby_ag_test_setup_wc();

		delete_option( Settings::OPTION_KEY );
	}

	/**
	 * Build a sample shipping address.
	 *
	 * @param array<string,mixed> $overrides Field overrides.
	 *
	 * @return array<string,string>
	 */
	protected function sample_address( array $overrides = array() ): array {
		return array_merge(
			array(
				'first_name' => 'Jane',
				'last_name'  => 'Doe',
				'company'    => '',
				'address_1'  => '123 Main St',
				'address_2'  => '',
				'city'       => 'Springfield',
				'state'      => 'IL',
				'postcode'   => '62701',
				'country'    => 'US',
				'phone'      => '',
				'email'      => '',
				'type'       => 'shipping',
			),
			$overrides
		);
	}

	/**
	 * Enable validation in plugin settings.
	 *
	 * @param string $mode warn|block.
	 *
	 * @return Settings
	 */
	protected function settings_with_validation( string $mode = 'block' ): Settings {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['plugin_enabled']            = 'yes';
		$values['validation_mode']           = $mode;
		$values['validate_shipping_address'] = 'yes';
		$settings->save( $values );

		return $settings;
	}

	/**
	 * Grant a capability to the current user stub.
	 *
	 * @param string $capability Capability name.
	 *
	 * @return void
	 */
	protected function grant_capability( string $capability ): void {
		$GLOBALS['wpruby_ag_test_user_caps'][ $capability ] = true;
	}

	/**
	 * Register a valid REST nonce for permission checks.
	 *
	 * @param string $nonce Nonce value.
	 *
	 * @return void
	 */
	protected function register_rest_nonce( string $nonce = 'valid-rest-nonce' ): void {
		$GLOBALS['wpruby_ag_test_nonces'][] = $nonce;
	}
}
