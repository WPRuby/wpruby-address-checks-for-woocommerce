<?php
/**
 * Storefront address validation REST endpoint.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPRuby\AddressGuard\Domain\Address;
use WPRuby\AddressGuard\Domain\AddressValidator;
use WPRuby\AddressGuard\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ValidationController
 */
class ValidationController {

	const NAMESPACE = 'address-guard/v1';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Address validator.
	 *
	 * @var AddressValidator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param Settings         $settings  Settings accessor.
	 * @param AddressValidator $validator Address validator.
	 */
	public function __construct( Settings $settings, AddressValidator $validator ) {
		$this->settings  = $settings;
		$this->validator = $validator;
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
	 * Register validation routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/address/validate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'validate_address' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'address' => array(
							'required' => true,
							'type'     => 'object',
						),
						'type'    => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => Address::TYPE_SHIPPING,
						),
						'context' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => 'checkout',
						),
					),
				),
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return true|WP_Error
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! $this->settings->is_enabled() || ! $this->validator->is_validation_active() ) {
			return new WP_Error(
				'address_guard_validation_disabled',
				__( 'Address validation is not enabled.', 'address-guard-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'address_guard_invalid_nonce',
				__( 'Invalid validation request.', 'address-guard-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * POST /address/validate
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_address( WP_REST_Request $request ) {
		$params  = $this->request_params( $request );
		$address = isset( $params['address'] ) && is_array( $params['address'] ) ? $params['address'] : array();
		$type    = sanitize_key( (string) ( $params['type'] ?? Address::TYPE_SHIPPING ) );

		if ( ! in_array( $type, array( Address::TYPE_BILLING, Address::TYPE_SHIPPING ), true ) ) {
			$type = Address::TYPE_SHIPPING;
		}

		if ( ! $this->validator->should_validate_type( $type ) ) {
			return new WP_Error(
				'address_guard_validation_type_disabled',
				__( 'Validation is not enabled for this address type.', 'address-guard-for-woocommerce' ),
				array( 'status' => 403 )
			);
		}

		$address['type'] = $type;
		$value           = new Address( $address );

		if ( ! $value->is_populated() ) {
			return new WP_Error(
				'address_guard_empty_address',
				__( 'Address is empty.', 'address-guard-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->validator->validate(
			$value,
			$type,
			array(
				'context' => 'checkout',
				'source'  => 'rest',
			)
		);

		return new WP_REST_Response( $result->to_public_array(), 200 );
	}

	/**
	 * Resolve request params from JSON body or form params.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array<string,mixed>
	 */
	private function request_params( WP_REST_Request $request ): array {
		$params = $request->get_json_params();
		if ( is_array( $params ) && ! empty( $params ) ) {
			return $params;
		}

		return $request->get_params();
	}
}
