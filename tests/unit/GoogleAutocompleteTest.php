<?php
/**
 * Google Places autocomplete unit tests.
 *
 * @package WPRuby\AddressGuard\Tests\Unit
 */

namespace WPRuby\AddressGuard\Tests\Unit;

use WP_REST_Request;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Domain\Autocomplete\AutocompleteCountryContext;
use WPRuby\AddressGuard\Domain\Google\GoogleAddressMapper;
use WPRuby\AddressGuard\Domain\Google\GooglePlacesService;
use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\REST\AutocompleteController;
use WPRuby\AddressGuard\REST\SettingsController;
use WPRuby\AddressGuard\Tests\TestCase;

class GoogleAutocompleteTest extends TestCase {

	public function test_api_key_is_masked_for_app_and_never_returned_in_full(): void {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['google_api_key'] = 'AIzaSyTestKeySecretValue123';
		$settings->save( $values );

		$for_app = $settings->for_app();
		$this->assertSame( Settings::MASKED_VALUE, $for_app['google_api_key'] );
		$this->assertStringNotContainsString( 'AIzaSy', (string) $for_app['google_api_key'] );
		$this->assertSame( 'AIzaSyTestKeySecretValue123', $settings->google_api_key() );
	}

	public function test_masked_credential_preserves_existing_key_on_save(): void {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['google_api_key'] = 'AIzaSyKeepThisKey';
		$settings->save( $values );

		$google     = new GooglePlacesService( $settings );
		$controller = new SettingsController( $settings, $google );
		$this->grant_capability( 'manage_woocommerce' );

		$request = new WP_REST_Request( 'POST', '/settings' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'plugin_enabled'       => 'yes',
					'validation_mode'      => 'warn',
					'google_api_key'       => Settings::MASKED_VALUE,
					'autocomplete_enabled' => 'yes',
				)
			)
		);

		$response = $controller->save_settings( $request );
		$data     = $response->get_data();

		$this->assertSame( Settings::MASKED_VALUE, $data['settings']['google_api_key'] );
		$this->assertSame( 'AIzaSyKeepThisKey', $settings->google_api_key() );
	}

	public function test_missing_api_key_disables_autocomplete_runtime(): void {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['autocomplete_enabled'] = 'yes';
		$values['google_api_key']       = '';
		$settings->save( $values );

		$this->assertTrue( $settings->is_autocomplete_toggle_enabled() );
		$this->assertFalse( $settings->is_autocomplete_enabled() );
	}

	public function test_autocomplete_disabled_blocks_rest_permission(): void {
		$settings = new Settings();
		$google   = new GooglePlacesService( $settings );
		$controller = new AutocompleteController( $settings, $google );

		$request = new WP_REST_Request( 'GET', '/address/autocomplete' );
		$this->register_rest_nonce();
		$request->set_header( 'X-WP-Nonce', 'valid-rest-nonce' );

		$result = $controller->check_permission( $request );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'address_guard_autocomplete_disabled', $result->get_error_code() );
	}

	public function test_autocomplete_query_uses_typed_query_and_checkout_country_only(): void {
		$settings = $this->settings_with_autocomplete();

		wpruby_ag_test_queue_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'suggestions' => array(
							array(
								'placePrediction' => array(
									'placeId' => 'ChIJ_test',
									'text'    => array( 'text' => 'Kölner Straße 200, Nürnberg, Germany' ),
								),
							),
						),
					)
				),
			)
		);

		$service = new GooglePlacesService( $settings );
		$result  = $service->search(
			'Kölner Str',
			array(
				'country'             => 'DE',
				'preferred_countries' => array( 'US', 'CA' ),
				'postcode'            => '90425',
				'city'                => 'Nürnberg',
				'state'               => 'BY',
			)
		);

		$requests = wpruby_ag_test_http_requests();
		$this->assertNotEmpty( $requests );
		$body = json_decode( (string) ( $requests[0]['args']['body'] ?? '' ), true );
		$this->assertIsArray( $body );
		$this->assertSame( 'Kölner Str', $body['input'] );
		$this->assertSame( array( 'de' ), $body['includedRegionCodes'] );
		$this->assertArrayNotHasKey( 'postcode', $body );
		$this->assertArrayNotHasKey( 'city', $body );
		$this->assertArrayNotHasKey( 'state', $body );
		$this->assertCount( 1, $result );
		$this->assertSame( 'google_places', $result[0]['provider'] );
		$this->assertSame( 'DE', $result[0]['meta']['country'] );
	}

	public function test_configured_countries_used_when_checkout_country_missing(): void {
		$context = array(
			'country'             => '',
			'preferred_countries' => array( 'US', 'CA', 'GB', 'DE' ),
		);

		$this->assertSame(
			array( 'us', 'ca', 'gb', 'de' ),
			AutocompleteCountryContext::region_codes( $context )
		);
	}

	public function test_german_street_formatting_and_details_normalization(): void {
		$mapper = new GoogleAddressMapper();
		$mapped = $mapper->from_places_components(
			array(
				array(
					'types'     => array( 'street_number' ),
					'longText'  => '200',
					'shortText' => '200',
				),
				array(
					'types'     => array( 'route' ),
					'longText'  => 'Kölner Straße',
					'shortText' => 'Kölner Straße',
				),
				array(
					'types'     => array( 'locality' ),
					'longText'  => 'Nürnberg',
					'shortText' => 'Nürnberg',
				),
				array(
					'types'     => array( 'administrative_area_level_1' ),
					'longText'  => 'Bayern',
					'shortText' => 'BY',
				),
				array(
					'types'     => array( 'postal_code' ),
					'longText'  => '90425',
					'shortText' => '90425',
				),
				array(
					'types'     => array( 'country' ),
					'longText'  => 'Germany',
					'shortText' => 'DE',
				),
			),
			'Kölner Straße 200, 90425 Nürnberg, Germany',
			'ChIJ_de_place'
		);

		$this->assertSame( 'Kölner Straße 200', $mapped['address_1'] );
		$this->assertSame( 'Nürnberg', $mapped['city'] );
		$this->assertSame( 'BY', $mapped['state'] );
		$this->assertSame( '90425', $mapped['postcode'] );
		$this->assertSame( 'DE', $mapped['country'] );
	}

	public function test_us_street_formatting(): void {
		$mapper = new GoogleAddressMapper();
		$mapped = $mapper->from_places_components(
			array(
				array(
					'types'    => array( 'street_number' ),
					'longText' => '200',
				),
				array(
					'types'    => array( 'route' ),
					'longText' => 'Main Street',
				),
				array(
					'types'     => array( 'country' ),
					'shortText' => 'US',
				),
				array(
					'types'     => array( 'administrative_area_level_1' ),
					'longText'  => 'California',
					'shortText' => 'CA',
				),
			),
			'200 Main Street, CA, USA',
			'ChIJ_us'
		);

		$this->assertSame( '200 Main Street', $mapped['address_1'] );
		$this->assertSame( 'CA', $mapped['state'] );
	}

	public function test_provider_error_returns_safe_message_without_raw_google_payload(): void {
		$settings = $this->settings_with_autocomplete();

		wpruby_ag_test_queue_http_response(
			array(
				'response' => array( 'code' => 403 ),
				'body'     => wp_json_encode(
					array(
						'error' => array(
							'status'  => 'PERMISSION_DENIED',
							'message' => 'API key leaked secret should not appear',
							'details' => array(
								array( 'reason' => 'API_KEY_INVALID' ),
							),
						),
					)
				),
			)
		);

		$service    = new GooglePlacesService( $settings );
		$controller = new AutocompleteController( $settings, $service );
		$this->register_rest_nonce();

		$request = new WP_REST_Request( 'GET', '/address/autocomplete' );
		$request->set_header( 'X-WP-Nonce', 'valid-rest-nonce' );
		$request->set_param( 'query', 'Main St' );
		$request->set_param( 'country', 'US' );
		$request->set_param( 'address_type', 'shipping' );

		$result = $controller->search( $request );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'address_guard_autocomplete_unavailable', $result->get_error_code() );
		$this->assertStringNotContainsString( 'leaked', $result->get_error_message() );
		$this->assertStringNotContainsString( 'AIza', $result->get_error_message() );
	}

	public function test_google_test_connection_success_and_missing_key(): void {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['autocomplete_enabled'] = 'yes';
		$values['google_api_key']       = '';
		$settings->save( $values );

		$service = new GooglePlacesService( $settings );
		$fail    = $service->test_connection();
		$this->assertFalse( $fail['success'] );
		$this->assertSame(
			'Google Places Autocomplete could not be reached. Check your API key and enabled Google APIs.',
			$fail['message']
		);

		$settings->save(
			array_merge(
				$settings->all(),
				array( 'google_api_key' => 'AIzaSyTest' )
			)
		);

		wpruby_ag_test_queue_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'suggestions' => array(
							array(
								'placePrediction' => array(
									'placeId' => 'ChIJ_ok',
									'text'    => array( 'text' => '1600 Amphitheatre Parkway' ),
								),
							),
						),
					)
				),
			)
		);

		$ok = ( new GooglePlacesService( $settings ) )->test_connection();
		$this->assertTrue( $ok['success'] );
		$this->assertSame( 'Google Places Autocomplete is connected.', $ok['message'] );
	}

	public function test_local_checks_still_run_after_autocomplete_style_addresses(): void {
		$settings  = $this->settings_with_validation( 'block' );
		$validator = new AddressValidator( $settings );

		$with_number = new Address(
			$this->sample_address(
				array(
					'address_1' => 'Kölner Straße 200',
					'city'      => 'Nürnberg',
					'postcode'  => '90425',
					'country'   => 'DE',
					'state'     => 'BY',
				)
			)
		);
		$result_ok = $validator->validate( $with_number, Address::TYPE_SHIPPING );
		$issues_ok = $result_ok->to_array()['issues'] ?? array();
		$this->assertNotContains( 'missing_house_number', $issues_ok );

		$po_box = new Address(
			$this->sample_address(
				array(
					'address_1' => 'PO Box 123',
				)
			)
		);
		$po_result = $validator->validate( $po_box, Address::TYPE_SHIPPING );
		$this->assertContains( 'po_box_detected', $po_result->to_array()['issues'] ?? array() );
	}

	public function test_http_requests_do_not_log_api_key_in_captured_url(): void {
		$settings = $this->settings_with_autocomplete();

		wpruby_ag_test_queue_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'suggestions' => array() ) ),
			)
		);

		( new GooglePlacesService( $settings ) )->search( 'Main', array( 'country' => 'US' ) );

		$requests = $GLOBALS['wpruby_ag_test_http_requests'];
		$this->assertNotEmpty( $requests );
		$first = $requests[0];
		$this->assertStringNotContainsString( 'AIzaSy', (string) ( $first['url'] ?? '' ) );
		$this->assertArrayHasKey( 'headers', $first['args'] ?? array() );
	}

	/**
	 * Settings with autocomplete enabled and a fake Google key.
	 *
	 * @return Settings
	 */
	private function settings_with_autocomplete(): Settings {
		$settings = new Settings();
		$values   = $settings->defaults();
		$values['plugin_enabled']            = 'yes';
		$values['autocomplete_enabled']      = 'yes';
		$values['google_api_key']            = 'AIzaSyTestKeySecretValue123';
		$values['autocomplete_countries']    = array( 'US', 'CA' );
		$values['validate_shipping_address'] = 'yes';
		$settings->save( $values );

		return $settings;
	}
}
