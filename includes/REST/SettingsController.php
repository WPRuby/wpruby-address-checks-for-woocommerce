<?php
/**
 * REST API controller for plugin settings.
 *
 * @package WPRuby\AddressGuard
 */

namespace WPRuby\AddressGuard\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPRuby\AddressGuard\Infrastructure\Sanitizer;
use WPRuby\AddressGuard\Infrastructure\Settings;
use WPRuby\AddressGuard\WooCommerce\CheckoutCompatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsController
 *
 * Registers and handles settings REST routes for the admin Vue app.
 */
class SettingsController {

	const NAMESPACE  = 'address-guard/v1';
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes under the plugin namespace.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$auth = array( $this, 'check_permission' );

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => $auth,
				),
			)
		);
	}

	/**
	 * Permission callback for admin routes.
	 *
	 * @return true|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( self::CAPABILITY ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'address_guard_forbidden',
				__( 'You do not have permission to manage Address Guard settings.', 'address-guard-for-woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * GET /settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->payload_for_app(), 200 );
	}

	/**
	 * POST /settings
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_settings( WP_REST_Request $request ) {
		$raw = (array) $request->get_json_params();
		if ( empty( $raw ) ) {
			$raw = (array) $request->get_params();
		}

		$defaults = $this->settings->defaults();
		$messages = Sanitizer::messages( $raw['messages'] ?? array() );

		$clean = array(
			'plugin_enabled'             => Sanitizer::checkbox( $this->bool_in( $raw, 'plugin_enabled' ) ),
			'validation_mode'            => Sanitizer::validation_mode( $raw['validation_mode'] ?? 'warn' ),
			'validate_shipping_address'  => Sanitizer::checkbox( $this->bool_in( $raw, 'validate_shipping_address' ) ),
			'validate_billing_address'   => Sanitizer::checkbox( $this->bool_in( $raw, 'validate_billing_address' ) ),
			'check_missing_house_number' => Sanitizer::checkbox( $this->bool_in( $raw, 'check_missing_house_number' ) ),
			'check_po_box'               => Sanitizer::checkbox( $this->bool_in( $raw, 'check_po_box' ) ),
			'check_parcel_locker'        => Sanitizer::checkbox( $this->bool_in( $raw, 'check_parcel_locker' ) ),
			'check_postcode_format'      => Sanitizer::checkbox( $this->bool_in( $raw, 'check_postcode_format' ) ),
			'messages'                   => wp_parse_args( $messages, $defaults['messages'] ),
			'order_add_validation_notes'   => Sanitizer::checkbox( $this->bool_in( $raw, 'order_add_validation_notes' ) ),
		);

		$this->settings->save( $clean );

		return new WP_REST_Response(
			array(
				'settings' => $this->settings->for_app(),
				'meta'     => $this->meta_for_app(),
				'message'  => __( 'Settings saved.', 'address-guard-for-woocommerce' ),
			),
			200
		);
	}

	/**
	 * Return the full admin payload (settings + meta).
	 *
	 * @return array<string,mixed>
	 */
	private function payload_for_app(): array {
		return array(
			'settings' => $this->settings->for_app(),
			'meta'     => $this->meta_for_app(),
		);
	}

	/**
	 * Return read-only meta for the admin app.
	 *
	 * @return array<string,mixed>
	 */
	private function meta_for_app(): array {
		$compatibility = new CheckoutCompatibility();
		$checkout      = $compatibility->summary_for_app();

		return array(
			'checkout_blocks'         => $checkout['checkout_blocks'],
			'checkout_classic'        => $checkout['checkout_classic'],
			'checkout_detected'       => $checkout['checkout_detected'],
			'checkout_detected_label' => $checkout['detected_label'],
			'supports_blocks'         => $checkout['supports_blocks'],
			'supports_classic'        => $checkout['supports_classic'],
			'pro_url'                 => esc_url_raw( 'https://wpruby.com/plugin/woocommerce-address-guard-pro/' ),
		);
	}

	/**
	 * Read a boolean-ish value from a request payload.
	 *
	 * @param array<string,mixed> $raw Request payload.
	 * @param string              $key Setting key.
	 *
	 * @return mixed
	 */
	private function bool_in( array $raw, string $key ) {
		return array_key_exists( $key, $raw ) ? $raw[ $key ] : 'no';
	}
}
