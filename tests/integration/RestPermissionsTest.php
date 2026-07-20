<?php
/**
 * REST permission integration tests.
 *
 * @package WPRuby\AddressGuard\Tests\Integration
 */

namespace WPRuby\AddressGuard\Tests\Integration;

use WP_Error;
use WP_REST_Request;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\REST\SettingsController;
use WPRuby\AddressGuard\REST\ValidationController;
use WPRuby\AddressGuard\Tests\TestCase;

class RestPermissionsTest extends TestCase {

	public function test_admin_settings_endpoint_requires_manage_woocommerce(): void {
		$settings   = $this->settings_with_validation( 'block' );
		$google     = new \WPRuby\AddressGuard\Domain\Google\GooglePlacesService( $settings );
		$controller = new SettingsController( $settings, $google );

		$result = $controller->check_permission();
		$this->assertInstanceOf( WP_Error::class, $result );

		$this->grant_capability( 'manage_woocommerce' );
		$this->assertTrue( $controller->check_permission() );
	}

	public function test_validation_endpoint_requires_enabled_validation_and_nonce(): void {
		$settings   = $this->settings_with_validation( 'block' );
		$validator  = new AddressValidator( $settings );
		$controller = new ValidationController( $settings, $validator );

		$request = new WP_REST_Request( 'POST', '/address/validate' );
		$request->set_param( 'type', 'shipping' );
		$request->set_param( 'context', 'checkout' );

		$result = $controller->check_permission( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'wpruby_ac_invalid_nonce', $result->get_error_code() );

		$this->register_rest_nonce();
		$request->set_header( 'X-WP-Nonce', 'valid-rest-nonce' );

		$this->assertTrue( $controller->check_permission( $request ) );
	}
}
