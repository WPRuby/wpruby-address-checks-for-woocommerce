<?php
/**
 * Checkout autocomplete REST endpoints.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\Google\GoogleApiException;
use WPRuby\AddressGuard\Domain\Google\GooglePlacesService;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AutocompleteController
 *
 * Proxies Google Places Autocomplete and Place Details for checkout.
 */
class AutocompleteController {

	const NAMESPACE = 'address-guard/v1';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Google Places service.
	 *
	 * @var GooglePlacesService
	 */
	private $google;

	/**
	 * Constructor.
	 *
	 * @param Settings            $settings Settings accessor.
	 * @param GooglePlacesService $google   Google Places service.
	 */
	public function __construct( Settings $settings, GooglePlacesService $google ) {
		$this->settings = $settings;
		$this->google   = $google;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register autocomplete routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/address/autocomplete',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'query'        => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'country'      => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'address_type' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => Address::TYPE_SHIPPING,
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/address/details',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'details' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'place_id' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'     => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => Address::TYPE_SHIPPING,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission callback for checkout autocomplete.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return true|WP_Error
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! $this->settings->is_autocomplete_enabled() ) {
			return new WP_Error(
				'address_guard_autocomplete_disabled',
				__( 'Address autocomplete is not enabled.', 'checkout-address-guard-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'address_guard_invalid_nonce',
				__( 'Invalid autocomplete request.', 'checkout-address-guard-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * GET /address/autocomplete
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function search( WP_REST_Request $request ) {
		$query = trim( (string) $request->get_param( 'query' ) );
		if ( '' === $query ) {
			return new WP_REST_Response( array(), 200 );
		}

		$country = strtoupper( sanitize_text_field( (string) $request->get_param( 'country' ) ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			$country = '';
		}

		$context = array(
			'country'             => $country,
			'preferred_countries' => $this->settings->autocomplete_countries(),
			'address_type'        => $this->resolve_address_type( $request ),
		);

		try {
			$suggestions = $this->google->search( $query, $context );
		} catch ( GoogleApiException $exception ) {
			return new WP_Error(
				'address_guard_autocomplete_unavailable',
				__( 'Address search is temporarily unavailable.', 'checkout-address-guard-for-woocommerce' ),
				array(
					'status' => 502,
					'code'   => $exception->get_error_code(),
				)
			);
		}

		return new WP_REST_Response( $suggestions, 200 );
	}

	/**
	 * GET /address/details
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function details( WP_REST_Request $request ) {
		$place_id = sanitize_text_field( (string) $request->get_param( 'place_id' ) );
		if ( '' === $place_id ) {
			return new WP_Error(
				'address_guard_missing_place_id',
				__( 'A place ID is required.', 'checkout-address-guard-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		try {
			$details = $this->google->get_details( $place_id );
		} catch ( GoogleApiException $exception ) {
			return new WP_Error(
				'address_guard_details_unavailable',
				__( 'Address search is temporarily unavailable.', 'checkout-address-guard-for-woocommerce' ),
				array(
					'status' => 502,
					'code'   => $exception->get_error_code(),
				)
			);
		}

		return new WP_REST_Response( $details, 200 );
	}

	/**
	 * Resolve billing/shipping address type from the request.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return string
	 */
	private function resolve_address_type( WP_REST_Request $request ): string {
		$type = sanitize_key( (string) ( $request->get_param( 'address_type' ) ?? $request->get_param( 'type' ) ?? Address::TYPE_SHIPPING ) );

		return in_array( $type, array( Address::TYPE_BILLING, Address::TYPE_SHIPPING ), true )
			? $type
			: Address::TYPE_SHIPPING;
	}
}
